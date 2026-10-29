<?php
declare(strict_types=1);

namespace MaxMind\Db\Reader {
class InvalidDatabaseException extends \Exception {}
}

namespace MaxMind\Db\Reader {
class UnsupportedPlatformException extends \RuntimeException {}
}

namespace MaxMind\Db\Reader {
class Util
{
    public static function read($stream, int $offset, int $numberOfBytes): string
    {
        if ($numberOfBytes === 0) {
            return '';
        }
        if (fseek($stream, $offset) === 0) {
            $value = fread($stream, $numberOfBytes);

            if ($value !== false && \strlen($value) === $numberOfBytes) {
                return $value;
            }
        }

        throw new InvalidDatabaseException(
            'The MaxMind DB file contains bad data'
        );
    }
}
}

namespace MaxMind\Db\Reader {
class Metadata
{
    public $binaryFormatMajorVersion;

    public $binaryFormatMinorVersion;

    public $buildEpoch;

    public $databaseType;

    public $description;

    public $ipVersion;

    public $languages;

    public $nodeByteSize;

    public $nodeCount;

    public $recordSize;

    public $searchTreeSize;

    public function __construct(array $metadata)
    {
        if (\func_num_args() !== 1) {
            throw new \ArgumentCountError(
                \sprintf('%s() expects exactly 1 parameter, %d given', __METHOD__, \func_num_args())
            );
        }

        $nodeCount = $metadata['node_count'] ?? null;
        if (\is_string($nodeCount) && preg_match('/\A(?:0|[1-9][0-9]*)\z/', $nodeCount)) {
            $maxInteger = (string) \PHP_INT_MAX;
            if (\strlen($nodeCount) > \strlen($maxInteger)
                || (\strlen($nodeCount) === \strlen($maxInteger) && strcmp($nodeCount, $maxInteger) > 0)
            ) {
                throw new UnsupportedPlatformException('The database node count exceeds the platform limit.');
            }
        }

        foreach ([
            'binary_format_major_version',
            'binary_format_minor_version',
            'ip_version',
            'node_count',
            'record_size',
        ] as $key) {
            if (!isset($metadata[$key]) || !\is_int($metadata[$key]) || $metadata[$key] < 0) {
                throw new InvalidDatabaseException("Metadata field $key must be an unsigned integer.");
            }
        }
        $buildEpoch = $metadata['build_epoch'] ?? null;
        if ((!\is_int($buildEpoch) || $buildEpoch < 0)
            && (!\is_string($buildEpoch) || !preg_match('/\A[0-9]+\z/', $buildEpoch))
        ) {
            throw new InvalidDatabaseException('Metadata build_epoch must be an unsigned integer.');
        }
        if (!\in_array($metadata['record_size'], [24, 28, 32], true)) {
            throw new InvalidDatabaseException('Metadata record_size must be 24, 28, or 32.');
        }
        if (!\in_array($metadata['ip_version'], [4, 6], true)) {
            throw new InvalidDatabaseException('Metadata ip_version must be 4 or 6.');
        }
        if (!isset($metadata['database_type']) || !\is_string($metadata['database_type'])) {
            throw new InvalidDatabaseException('Metadata database_type must be a string.');
        }
        foreach (['languages', 'description'] as $key) {
            if (!\array_key_exists($key, $metadata)) {
                $metadata[$key] = [];
            }
            if (!\is_array($metadata[$key])) {
                throw new InvalidDatabaseException("Metadata field $key must be an array of strings.");
            }
            foreach ($metadata[$key] as $value) {
                if (!\is_string($value)) {
                    throw new InvalidDatabaseException("Metadata field $key must contain only strings.");
                }
            }
        }

        $nodeByteSize = intdiv($metadata['record_size'], 4);
        if ($metadata['node_count'] > intdiv(\PHP_INT_MAX - 16, $nodeByteSize)) {
            throw new UnsupportedPlatformException('The database search tree exceeds the platform limit.');
        }

        $this->binaryFormatMajorVersion
            = $metadata['binary_format_major_version'];
        $this->binaryFormatMinorVersion
            = $metadata['binary_format_minor_version'];
        $this->buildEpoch = $metadata['build_epoch'];
        $this->databaseType = $metadata['database_type'];
        $this->languages = $metadata['languages'];
        $this->description = $metadata['description'];
        $this->ipVersion = $metadata['ip_version'];
        $this->nodeCount = $metadata['node_count'];
        $this->recordSize = $metadata['record_size'];
        $this->nodeByteSize = $nodeByteSize;
        $this->searchTreeSize = $this->nodeCount * $this->nodeByteSize;
    }
}
}

namespace MaxMind\Db\Reader {

class Decoder
{
    private $fileStream;

    private $pointerBase;

    private $pointerTestHack;

    private $switchByteOrder;

    private $budget = 0;

    private $byteBudget = 0;

    private $position = -1;

    private const _EXTENDED = 0;
    private const _POINTER = 1;
    private const _UTF8_STRING = 2;
    private const _DOUBLE = 3;
    private const _BYTES = 4;
    private const _UINT16 = 5;
    private const _UINT32 = 6;
    private const _MAP = 7;
    private const _INT32 = 8;
    private const _UINT64 = 9;
    private const _UINT128 = 10;
    private const _ARRAY = 11;
    private const _BOOLEAN = 14;
    private const _FLOAT = 15;

    private const MAX_DEPTH = 512;
    private const MAX_VALUES = 1 << 16;

    private const MAX_PAYLOAD_BYTES = 1 << 21;

    private const MAX_SCALAR_BYTES = 16;

    public function __construct(
        $fileStream,
        int $pointerBase = 0,
        bool $pointerTestHack = false
    ) {
        $this->fileStream = $fileStream;
        $this->pointerBase = $pointerBase;

        $this->pointerTestHack = $pointerTestHack;

        $this->switchByteOrder = $this->isPlatformLittleEndian();
    }

    public function decode(int $offset): array
    {
        $this->budget = self::MAX_VALUES - 1;
        $this->byteBudget = self::MAX_PAYLOAD_BYTES;

        $this->position = -1;

        return $this->decodeWithBudget($offset, 0);
    }

    private function decodeWithBudget(int $offset, int $depth, bool $allowPointer = true): array
    {
        $ctrlByte = \ord($this->read($offset, 1));
        ++$offset;

        $type = $ctrlByte >> 5;

        if ($type === self::_POINTER) {
            if (!$allowPointer) {
                throw new InvalidDatabaseException(
                    'The MaxMind DB file contains a pointer to another pointer'
                );
            }

            [$pointer, $offset] = $this->decodePointer($ctrlByte, $offset);

            if ($this->pointerTestHack) {
                return [$pointer];
            }

            if ($depth >= self::MAX_DEPTH) {
                throw new InvalidDatabaseException(
                    'The MaxMind DB file exceeds the maximum depth'
                );
            }

            [$result] = $this->decodeWithBudget($pointer, $depth + 1, false);

            return [$result, $offset];
        }

        if ($type === self::_EXTENDED) {
            $nextByte = \ord($this->read($offset, 1));

            $type = $nextByte + 7;

            if ($type < 8) {
                throw new InvalidDatabaseException(
                    'Something went horribly wrong in the decoder. An extended type '
                    . 'resolved to a type number < 8 ('
                    . $type
                    . ')'
                );
            }

            ++$offset;
        }

        [$size, $offset] = $this->sizeFromCtrlByte($ctrlByte, $offset);

        return $this->decodeByType($type, $offset, $size, $depth);
    }

    private function decodeByType(int $type, int $offset, int $size, int $depth): array
    {
        switch ($type) {
            case self::_MAP:
                return $this->decodeMap($size, $offset, $depth);

            case self::_ARRAY:
                return $this->decodeArray($size, $offset, $depth);

            case self::_BOOLEAN:
                return [$this->decodeBoolean($size), $offset];

            case self::_BYTES:
            case self::_UTF8_STRING:
                if ($size > $this->byteBudget) {
                    throw new InvalidDatabaseException(
                        'The MaxMind DB file exceeds the maximum payload size'
                    );
                }
                $this->byteBudget -= $size;

                return [$this->read($offset, $size), $offset + $size];
        }

        if ($size > self::MAX_SCALAR_BYTES) {
            throw new InvalidDatabaseException(
                "The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)"
            );
        }

        $newOffset = $offset + $size;
        $bytes = $this->read($offset, $size);

        switch ($type) {
            case self::_DOUBLE:
                $this->verifySize(8, $size);

                return [$this->decodeDouble($bytes), $newOffset];

            case self::_FLOAT:
                $this->verifySize(4, $size);

                return [$this->decodeFloat($bytes), $newOffset];

            case self::_INT32:
                return [$this->decodeInt32($bytes, $size), $newOffset];

            case self::_UINT16:
            case self::_UINT32:
            case self::_UINT64:
            case self::_UINT128:
                return [$this->decodeUint($bytes, $size), $newOffset];

            default:
                throw new InvalidDatabaseException(
                    'Unknown or unexpected type: ' . $type
                );
        }
    }

    private function read(int $offset, int $numberOfBytes): string
    {
        if ($numberOfBytes === 0) {
            return '';
        }

        $stream = $this->fileStream;
        if ($offset !== $this->position && fseek($stream, $offset) !== 0) {
            $this->position = -1;

            throw new InvalidDatabaseException('The MaxMind DB file contains bad data');
        }

        $value = fread($stream, $numberOfBytes);
        if ($value === false || \strlen($value) !== $numberOfBytes) {
            $this->position = -1;

            throw new InvalidDatabaseException('The MaxMind DB file contains bad data');
        }
        $this->position = $offset + $numberOfBytes;

        return $value;
    }

    private function verifySize(int $expected, int $actual): void
    {
        if ($expected !== $actual) {
            throw new InvalidDatabaseException(
                "The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)"
            );
        }
    }

    private function enterContainer(
        int $size,
        int $depth,
        int $valuesPerEntry = 1
    ): void {
        if ($depth >= self::MAX_DEPTH) {
            throw new InvalidDatabaseException(
                'The MaxMind DB file exceeds the maximum depth'
            );
        }
        if ($size > intdiv($this->budget, $valuesPerEntry)) {
            throw new InvalidDatabaseException(
                'The MaxMind DB file exceeds the maximum number of values'
            );
        }
        $this->budget -= $size * $valuesPerEntry;
    }

    private function decodeArray(int $size, int $offset, int $depth): array
    {
        $this->enterContainer($size, $depth);

        $array = [];

        for ($i = 0; $i < $size; ++$i) {
            [$value, $offset] = $this->decodeWithBudget($offset, $depth + 1);
            $array[] = $value;
        }

        return [$array, $offset];
    }

    private function decodeBoolean(int $size): bool
    {
        return $size !== 0;
    }

    private function decodeDouble(string $bytes): float
    {
        $rc = unpack('E', $bytes);
        if ($rc === false) {
            throw new InvalidDatabaseException(
                'Could not unpack a double value from the given bytes.'
            );
        }
        [, $double] = $rc;

        return $double;
    }

    private function decodeFloat(string $bytes): float
    {
        $rc = unpack('G', $bytes);
        if ($rc === false) {
            throw new InvalidDatabaseException(
                'Could not unpack a float value from the given bytes.'
            );
        }
        [, $float] = $rc;

        return $float;
    }

    private function decodeInt32(string $bytes, int $size): int
    {
        switch ($size) {
            case 0:
                return 0;

            case 1:
            case 2:
            case 3:
                $bytes = str_pad($bytes, 4, "\x00", \STR_PAD_LEFT);

                break;

            case 4:
                break;

            default:
                throw new InvalidDatabaseException(
                    "The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)"
                );
        }

        $rc = unpack('l', $this->maybeSwitchByteOrder($bytes));
        if ($rc === false) {
            throw new InvalidDatabaseException(
                'Could not unpack a 32bit integer value from the given bytes.'
            );
        }
        [, $int] = $rc;

        return $int;
    }

    private function decodeMap(int $size, int $offset, int $depth): array
    {
        $this->enterContainer($size, $depth, 2);

        $map = [];

        for ($i = 0; $i < $size; ++$i) {
            [$key, $offset] = $this->decodeWithBudget($offset, $depth + 1);
            if (!\is_string($key)) {
                throw new InvalidDatabaseException('A map key must be a string.');
            }
            [$value, $offset] = $this->decodeWithBudget($offset, $depth + 1);
            $map[$key] = $value;
        }

        return [$map, $offset];
    }

    private function decodePointer(int $ctrlByte, int $offset): array
    {
        $pointerSize = (($ctrlByte >> 3) & 0x3) + 1;

        $buffer = $this->read($offset, $pointerSize);
        $offset += $pointerSize;

        switch ($pointerSize) {
            case 1:
                $packed = \chr($ctrlByte & 0x7) . $buffer;
                $rc = unpack('n', $packed);
                if ($rc === false) {
                    throw new InvalidDatabaseException(
                        'Could not unpack an unsigned short value from the given bytes (pointerSize is 1).'
                    );
                }
                [, $pointer] = $rc;
                $pointer += $this->pointerBase;

                break;

            case 2:
                $packed = "\x00" . \chr($ctrlByte & 0x7) . $buffer;
                $rc = unpack('N', $packed);
                if ($rc === false) {
                    throw new InvalidDatabaseException(
                        'Could not unpack an unsigned long value from the given bytes (pointerSize is 2).'
                    );
                }
                [, $pointer] = $rc;
                $pointer += $this->pointerBase + 2048;

                break;

            case 3:
                $packed = \chr($ctrlByte & 0x7) . $buffer;

                $rc = unpack('N', $packed);
                if ($rc === false) {
                    throw new InvalidDatabaseException(
                        'Could not unpack an unsigned long value from the given bytes (pointerSize is 3).'
                    );
                }
                [, $pointer] = $rc;
                $pointer += $this->pointerBase + 526336;

                break;

            case 4:
                $pointerOffset = $this->decodeUint($buffer, $pointerSize);

                $pointerBase = $this->pointerBase;

                if (\PHP_INT_MAX - $pointerBase >= $pointerOffset) {
                    $pointer = $pointerOffset + $pointerBase;
                } else {
                    throw new UnsupportedPlatformException(
                        'The database offset is too large to be represented on your platform.'
                    );
                }

                break;

            default:
                throw new InvalidDatabaseException(
                    'Unexpected pointer size ' . $pointerSize
                );
        }

        return [$pointer, $offset];
    }

    private function decodeUint(string $bytes, int $byteLength)
    {
        if ($byteLength === 0) {
            return 0;
        }

        $useRealInts = $byteLength <= \PHP_INT_SIZE - 1
            || ($byteLength === \PHP_INT_SIZE && (\ord($bytes[0]) & 0x80) === 0);

        if ($useRealInts) {
            $integer = 0;
            for ($i = 0; $i < $byteLength; ++$i) {
                $part = \ord($bytes[$i]);
                $integer = ($integer << 8) + $part;
            }

            return $integer;
        }

        $integerAsString = '0';
        for ($i = 0; $i < $byteLength; ++$i) {
            $part = \ord($bytes[$i]);

            if (\extension_loaded('gmp')) {
                $integerAsString = gmp_strval(gmp_add(gmp_mul($integerAsString, '256'), $part));
            } elseif (\extension_loaded('bcmath')) {
                $integerAsString = bcadd(bcmul($integerAsString, '256'), (string) $part);
            } else {
                throw new UnsupportedPlatformException(
                    'The gmp or bcmath extension must be installed to read this database.'
                );
            }
        }

        return $integerAsString;
    }

    private function sizeFromCtrlByte(int $ctrlByte, int $offset): array
    {
        $size = $ctrlByte & 0x1F;

        if ($size < 29) {
            return [$size, $offset];
        }

        $bytesToRead = $size - 28;
        $bytes = $this->read($offset, $bytesToRead);

        if ($size === 29) {
            $size = 29 + \ord($bytes);
        } elseif ($size === 30) {
            $rc = unpack('n', $bytes);
            if ($rc === false) {
                throw new InvalidDatabaseException(
                    'Could not unpack an unsigned short value from the given bytes.'
                );
            }
            [, $adjust] = $rc;
            $size = 285 + $adjust;
        } else {
            $rc = unpack('N', "\x00" . $bytes);
            if ($rc === false) {
                throw new InvalidDatabaseException(
                    'Could not unpack an unsigned long value from the given bytes.'
                );
            }
            [, $adjust] = $rc;
            $size = $adjust + 65821;
        }

        return [$size, $offset + $bytesToRead];
    }

    private function maybeSwitchByteOrder(string $bytes): string
    {
        return $this->switchByteOrder ? strrev($bytes) : $bytes;
    }

    private function isPlatformLittleEndian(): bool
    {
        $testint = 0x00FF;
        $packed = pack('S', $testint);
        $rc = unpack('v', $packed);
        if ($rc === false) {
            throw new \Error(
                'Could not unpack an unsigned short value from the given bytes.'
            );
        }

        return $testint === current($rc);
    }
}
}

namespace MaxMind\Db {
use MaxMind\Db\Reader\Decoder;
use MaxMind\Db\Reader\InvalidDatabaseException;
use MaxMind\Db\Reader\Metadata;
use MaxMind\Db\Reader\UnsupportedPlatformException;
use MaxMind\Db\Reader\Util;

class Reader
{
    private static $DATA_SECTION_SEPARATOR_SIZE = 16;

    private static $METADATA_START_MARKER = "\xAB\xCD\xEFMaxMind.com";

    private static $METADATA_START_MARKER_LENGTH = 14;

    private static $METADATA_MAX_SIZE = 131072;

    private $decoder;

    private $fileHandle;

    private $lookupInProgress = false;

    private $fileSize;

    private $ipV4Start;

    private $metadata;

    public function __construct(string $database)
    {
        if (\func_num_args() !== 1) {
            throw new \ArgumentCountError(
                \sprintf('%s() expects exactly 1 parameter, %d given', __METHOD__, \func_num_args())
            );
        }

        if (is_dir($database)) {
            throw new InvalidDatabaseException(
                "Error opening database file ($database). Is this a valid MaxMind DB file?"
            );
        }

        $fileHandle = @fopen($database, 'rb');
        if ($fileHandle === false) {
            throw new \InvalidArgumentException(
                "The file \"$database\" does not exist or is not readable."
            );
        }
        $this->fileHandle = $fileHandle;

        $fstat = fstat($fileHandle);
        if ($fstat === false) {
            throw new \UnexpectedValueException(
                "Error determining the size of \"$database\"."
            );
        }
        $this->fileSize = $fstat['size'];

        $start = $this->findMetadataStart($database);
        $metadataDecoder = new Decoder($this->fileHandle, $start);
        [$metadataArray] = $metadataDecoder->decode($start);
        if (!\is_array($metadataArray)) {
            throw new InvalidDatabaseException('The database metadata must be a map.');
        }
        $this->metadata = new Metadata($metadataArray);
        $this->decoder = new Decoder(
            $this->fileHandle,
            $this->metadata->searchTreeSize + self::$DATA_SECTION_SEPARATOR_SIZE
        );
        $this->ipV4Start = $this->ipV4StartNode();
    }

    public function get(string $ipAddress)
    {
        if (\func_num_args() !== 1) {
            throw new \ArgumentCountError(
                \sprintf('%s() expects exactly 1 parameter, %d given', __METHOD__, \func_num_args())
            );
        }
        [$record] = $this->getWithPrefixLen($ipAddress);

        return $record;
    }

    public function getWithPrefixLen(string $ipAddress): array
    {
        if (\func_num_args() !== 1) {
            throw new \ArgumentCountError(
                \sprintf('%s() expects exactly 1 parameter, %d given', __METHOD__, \func_num_args())
            );
        }

        if (!\is_resource($this->fileHandle)) {
            throw new \BadMethodCallException(
                'Attempt to read from a closed MaxMind DB.'
            );
        }

        if ($this->lookupInProgress) {
            throw new \BadMethodCallException(
                'A lookup is already in progress on this reader. Use a separate reader for nested lookups.'
            );
        }
        $this->lookupInProgress = true;

        try {
            [$pointer, $prefixLen] = $this->findAddressInTree($ipAddress);
            if ($pointer === 0) {
                return [null, $prefixLen];
            }

            return [$this->resolveDataPointer($pointer), $prefixLen];
        } finally {
            $this->lookupInProgress = false;
        }
    }

    private function findAddressInTree(string $ipAddress): array
    {
        $packedAddr = @inet_pton($ipAddress);
        if ($packedAddr === false) {
            throw new \InvalidArgumentException(
                "The value \"$ipAddress\" is not a valid IP address."
            );
        }

        $rawAddress = unpack('C*', $packedAddr);
        if ($rawAddress === false) {
            throw new InvalidDatabaseException(
                'Could not unpack the unsigned char of the packed in_addr representation.'
            );
        }

        $bitCount = \count($rawAddress) * 8;

        $node = 0;

        $metadata = $this->metadata;

        if ($metadata->ipVersion === 6) {
            if ($bitCount === 32) {
                $node = $this->ipV4Start;
            }
        } elseif ($metadata->ipVersion === 4 && $bitCount === 128) {
            throw new \InvalidArgumentException(
                "Error looking up $ipAddress. You attempted to look up an"
                . ' IPv6 address in an IPv4-only database.'
            );
        }

        $nodeCount = $metadata->nodeCount;

        for ($i = 0; $i < $bitCount && $node < $nodeCount; ++$i) {
            $tempBit = 0xFF & $rawAddress[($i >> 3) + 1];
            $bit = 1 & ($tempBit >> 7 - ($i % 8));

            $node = $this->readNode($node, $bit);
        }
        if ($node === $nodeCount) {
            return [0, $i];
        }
        if ($node > $nodeCount) {
            return [$node, $i];
        }

        throw new InvalidDatabaseException(
            'Invalid or corrupt database. Maximum search depth reached without finding a leaf node'
        );
    }

    private function ipV4StartNode(): int
    {
        if ($this->metadata->ipVersion === 4) {
            return 0;
        }

        $node = 0;

        for ($i = 0; $i < 96 && $node < $this->metadata->nodeCount; ++$i) {
            $node = $this->readNode($node, 0);
        }

        return $node;
    }

    private function readNode(int $nodeNumber, int $index): int
    {
        $baseOffset = $nodeNumber * $this->metadata->nodeByteSize;

        switch ($this->metadata->recordSize) {
            case 24:
                $bytes = Util::read($this->fileHandle, $baseOffset + $index * 3, 3);
                $rc = unpack('N', "\x00" . $bytes);
                if ($rc === false) {
                    throw new InvalidDatabaseException(
                        'Could not unpack the unsigned long of the node.'
                    );
                }
                [, $node] = $rc;

                return $node;

            case 28:
                $bytes = Util::read($this->fileHandle, $baseOffset + 3 * $index, 4);
                if ($index === 0) {
                    $middle = (0xF0 & \ord($bytes[3])) >> 4;
                } else {
                    $middle = 0x0F & \ord($bytes[0]);
                }
                $rc = unpack('N', \chr($middle) . substr($bytes, $index, 3));
                if ($rc === false) {
                    throw new InvalidDatabaseException(
                        'Could not unpack the unsigned long of the node.'
                    );
                }
                [, $node] = $rc;

                return $node;

            case 32:
                $bytes = Util::read($this->fileHandle, $baseOffset + $index * 4, 4);
                $rc = unpack('N', $bytes);
                if ($rc === false) {
                    throw new InvalidDatabaseException(
                        'Could not unpack the unsigned long of the node.'
                    );
                }
                [, $node] = $rc;

                return $node;

            default:
                throw new InvalidDatabaseException(
                    'Unknown record size: '
                    . $this->metadata->recordSize
                );
        }
    }

    private function resolveDataPointer(int $pointer)
    {
        $resolved = $pointer - $this->metadata->nodeCount
            + $this->metadata->searchTreeSize;
        if ($resolved >= $this->fileSize) {
            throw new InvalidDatabaseException(
                "The MaxMind DB file's search tree is corrupt"
            );
        }

        [$data] = $this->decoder->decode($resolved);

        return $data;
    }

    private function findMetadataStart(string $filename): int
    {
        $handle = $this->fileHandle;
        $fileSize = $this->fileSize;
        $marker = self::$METADATA_START_MARKER;
        $markerLength = self::$METADATA_START_MARKER_LENGTH;

        $minStart = $fileSize - min(self::$METADATA_MAX_SIZE, $fileSize);

        for ($offset = $fileSize - $markerLength; $offset >= $minStart; --$offset) {
            if (fseek($handle, $offset) !== 0) {
                break;
            }

            $value = fread($handle, $markerLength);
            if ($value === $marker) {
                return $offset + $markerLength;
            }
        }

        throw new InvalidDatabaseException(
            "Error opening database file ($filename). "
            . 'Is this a valid MaxMind DB file?'
        );
    }

    public function metadata(): Metadata
    {
        if (\func_num_args()) {
            throw new \ArgumentCountError(
                \sprintf('%s() expects exactly 0 parameters, %d given', __METHOD__, \func_num_args())
            );
        }

        if (!\is_resource($this->fileHandle)) {
            throw new \BadMethodCallException(
                'Attempt to read from a closed MaxMind DB.'
            );
        }

        return clone $this->metadata;
    }

    public function close(): void
    {
        if (\func_num_args()) {
            throw new \ArgumentCountError(
                \sprintf('%s() expects exactly 0 parameters, %d given', __METHOD__, \func_num_args())
            );
        }

        if (!\is_resource($this->fileHandle)) {
            throw new \BadMethodCallException(
                'Attempt to close a closed MaxMind DB.'
            );
        }
        fclose($this->fileHandle);
    }
}
}
