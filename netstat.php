<?php
$publiczna = false;
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!$publiczna && !isset($_SESSION['loggedin']))
{
	header('Location: index3.php');
	exit();
}

function catchExceptions($err){
	global $config;
	echo "Error with your request!  Please try again later.  " .
		"If the problem persists contact <a href=\"".$config['contact']."\">".$config['contact']."</a>.";
	error_log($err->__toString(),0);
	exit(1);
}

error_reporting(0);
set_exception_handler('catchExceptions');

function defaultConfig(){
	$config=array();
	$config['version'] = '~ git master ~ ';
	$config['description'] = "Status usług serwera hostingowego (netstat.php)";

	$config['configfile'] = 'netstat.conf.php';

	$config['title'] = "Stube";
	$config['headline'] = "Status usług serwera hostingowego";

	$config['alertfile'] = 'netstat.txt';

		$h = preg_replace('/[^A-Za-z0-9.\-]/', '', (string)($_SERVER['SERVER_NAME'] ?? 'localhost'));
		if ($h === '') { $h = 'localhost'; }

		$config['checks'] = array(
			'Usługi widziane od wewnątrz serwera (localhost) |headline',
			'localhost |   80 | HTTP &ndash; serwer WWW (port 80)',
			'localhost |  443 | HTTPS &ndash; serwer WWW z SSL (port 443)',
			'localhost |   21 | FTP (port 21)',
			'localhost |   22 | SSH (port 22)',
			'localhost |   25 | SMTP &ndash; poczta wychodząca (port 25)',
			'localhost |  110 | POP3 &ndash; poczta przychodząca (port 110)',
			'localhost |  143 | IMAP &ndash; poczta przychodząca (port 143)',
			'localhost | 3306 | MySQL / MariaDB (port 3306)',
			'localhost | 2222 | DirectAdmin &ndash; panel hostingu (port 2222)',
			'localhost | ping | ICMP ping (ping)',
			"Te same usługi widziane z zewnątrz ($h) |headline",
			"$h |   80 | HTTP &ndash; serwer WWW (port 80)",
			"$h |  443 | HTTPS &ndash; serwer WWW z SSL (port 443)",
			"$h |   21 | FTP (port 21)",
			"$h |   22 | SSH (port 22)",
			"$h | 3306 | MySQL / MariaDB (port 3306)",
			'Przykłady połączeń wychodzących z serwera |headline',
			'pbs.edu.pl |  80 | pbs.edu.pl &ndash; HTTP (port 80)',
			'pbs.edu.pl | 443 | pbs.edu.pl &ndash; HTTPS (port 443)',
			'www.google.com | 443 | www.google.com &ndash; HTTPS (port 443)'
		);

	$config['ping_command'] = 'ping -c3 -w1 -q';
	$config['ping6_command'] = 'ping6 -c3 -w1 -q';

	$config['timeout'] = 2;

	$config['progressindicator'] = TRUE;

	$config['online'] = 'Online';
	$config['offline'] = 'Offline';

	$config['datetime'] = 'Y-m-d H:i:s T';

	$config['rssfeed'] = TRUE;
	$config['rssfeedurl'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?rss';
	$config['rsstitle'] = "RSS alert feed of {$config['title']}";
	$config['rssheader'] = '<link rel="alternate" type="application/rss+xml" '."title=\"{$config['rsstitle']}\" href=\"{$config['rssfeedurl']}\" />";
	$config['rsslink'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?noprogress';
	$config['rssdatetime'] = 'o-m-d H:i:s T';

	$config['htmlheader'] = <<<EOH
<!doctype html><html lang="pl">
<head>
<title>{$config['title']}</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="{$config['description']}" />
{$config['rssheader']}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" type="text/css" href="twoj_css.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" src="twoj_js.js"></script>
<style type="text/css">
div#container { max-width: 44em; margin: 0 auto; position: relative; }
.datetime { font-size: 87%; font-weight: bolder; text-align: center; margin-bottom: 1.5em; }
.version { font-size: 73%; text-align: center; }
h1 { color: #2c3e50; border-bottom: 1px solid #999999; text-align: center; margin-top: 0.5em; margin-bottom: 1em; font-size: 1.6rem; }
div#alert { border: 1px solid red; padding: 0.2em 1.5em; margin: 1em 0; }
div#progress { position: fixed; top: 50px; left: 0; background: orange; color: black; padding: 0.2em 1em 0.2em 1em; z-index: 2000; }
.status_table { border: 1px solid #333333; border-collapse: collapse; width: 100%; background: #ffffff; }
.status_table td { color: #333333; border: 1px solid #444444; padding: 0.4em; }
.status_table td.headline { font-weight: bolder; background-color: #CFCCCC; padding: 0.4em 0.4em 0.3em 1.5em; }
.hidden { display: none !important; }
.{$config['online']} { background-color: #D9FFB3; padding-left: 0.8em !important; }
.{$config['offline']} { background-color: #FFB6B6; padding-left: 0.8em !important; }
</style>
</head>

<body onload="myLoadHeader()">
<div id='myHeader'> </div>
<main>
<section class="sekcja1">
<div id="container">
EOH;

	ob_start();
	include __DIR__ . '/footer.php';
	$config['htmlfooter'] = "</div>\n</section>\n</main>\n" . ob_get_clean() . "\n</body>\n</html>";

	$config['cachetime'] = 5*60;
	$config['cachepath'] = getcwd() .'/files';

	$config['contact'] = 'N/A';
	return $config;
}

function parseConfig($file, $config){
	if (file_exists($file) && is_readable($file)){
		@include($file);
		foreach($config as $key=>$var){
			if(isset($$key)){
				$config[$key]=$$key;
			}
		}
	}

	return $config;
}

function genConfig(){
	global $config;
	if(file_exists($config['configfile'])){
			die("Config file alread exists!\n");
	}
	$fi=fopen($_SERVER['PHP_SELF'],'r');
	if($fi===false) die("Error opening ".$_SERVER['PHP_SELF']);

	$fo=fopen($config['configfile'],'w');
	if($fo===false) die("Error opening ".$config['configfile']);
	fwrite($fo, "<?php\n");
	fwrite($fo, "/*\n");
	fwrite($fo, " * This file contains the commented defaults.  Feel free to delete\n");
	fwrite($fo, " * the unused options. \n");
	fwrite($fo, " */\n");

	while(($l=fgets($fi,400))!== false){
		if (trim($l)=='$config=array();'){
			break;
		}
	}
	while(($l=fgets($fi))!== false){
		if(trim($l)=='}' || trim($l)=='return $config;'){
			break;
		}
		fwrite($fo,preg_replace('/\$config\[[\'"]([^\'"]+)[\'"]\]/','\$$1',"//".trim($l))."\n");
	}
	fclose($fi);
	fclose($fo);
}

function genStatusUpdate(){
	global $config;

	$date=date('Y-m-d H:i O');
	$message=readline("Enter the message you would like to use:");
	$statusstr=<<<EOT
<p><b>Status as of $date</b><br />
$message
</p>
EOT;

	if((file_exists($config['alertfile']) && is_writable($config['alertfile'])) || is_writable(dirname($config['alertfile']))){
		$fp=fopen($config['alertfile'],'w');
		if($fp===false) die("Error writing alert file!");
		fwrite($fp, $statusstr);
		fclose($fp);
	}else{
	  die("File Exists/Not Writable");
	}

}

function rss(){
	global $config;
	if(!$config['rssfeed']){
		exit;
	}
	header("Content-Type: application/rss+xml");
	echo "<?xml version=\"1.0\"?><rss version=\"2.0\">\n<channel>\n";
	echo "<title>{$config['rsstitle']}</title>\n<link>{$config['rsslink']}</link>\n";
	echo "<description>{$config['rsstitle']}</description>\n";
	echo "<language>en</language>\n";
	if (file_exists($config['alertfile']) && is_readable($config['alertfile']))
	{
		echo "<item>\n<title>Alert ".date($config['rssdatetime'], filemtime($config['alertfile']))
		. " for {$config['rsstitle']}</title>\n";
		echo '<pubDate>'.date("r", filemtime($config['alertfile']))."</pubDate>\n";
		echo "<link>{$config['rsslink']}</link>\n";
		echo '<description><![CDATA[';
		@include($config['alertfile']);
		echo "]]></description>\n</item>\n";
	}
	echo "</channel>\n</rss>\n";
}

function html($cache){
	global $config;
	echo $config['htmlheader'];

	echo "<h1>{$config['headline']}</h1>\n";
	if ($config['datetime']) echo '<p class="datetime">stan na ' . date($config['datetime']) . "</p>\n";

	if (file_exists($config['alertfile']) && is_readable($config['alertfile']))
	{
		clearstatcache();
		if (filesize($config['alertfile']) > 2)
		{
			echo "<div id=\"alert\">\n";
			@include($config['alertfile']);
			echo "</div>\n";
		}
	}

	if (($_REQUEST['noprogress'] !== NULL) || ($argv[1] == 'noprogress'))
	{
		$config['progressindicator'] = FALSE;
	}
	if ($config['progressindicator'])
	{
		echo '<script type="text/javascript">
		document.write("<div id=\"progress\">Checks in progress ...</div>");'
		."</script>\n";
	}

	echo "<table class=\"status_table\">\n";

	foreach ($config['checks'] as $check)
	{
		$status = $config['offline'];
		$diagnostics = '';
		$output = TRUE;
		list($host,$port,$description) = explode('|',"$check||");
		$host = trim($host);
		$port = trim($port);

		switch ($port)
		{
			case '':
				$output = FALSE; break;
			case (substr($port,0,1)=='-'):
				$output = FALSE; break;
			case 'headline':
				echo '<tr><td class="headline" colspan="2">'
					. '<span class="hidden">&nbsp;<br />==&nbsp;</span>'
					. $host
					. '<span class="hidden">&nbsp;==</span>'
					. "</td></tr>\n";
				$output = FALSE; break;
			case 'ping':
				if (!function_exists('exec')) { $diagnostics = 'exec() jest wyłączone na tym hostingu'; break; }
				$ping=exec("{$config['ping_command']} $host",$pingoutput,$pingreturn);
			case 'ping6':
				if (!function_exists('exec')) { $diagnostics = 'exec() jest wyłączone na tym hostingu'; break; }
				if(!isset($ping)){
					$ping=exec("{$config['ping6_command']} $host",$pingoutput,$pingreturn);
				}
				if(strlen($ping)>10)
				{
					$status = $config['online']; $diagnostics = "$ping :: $pingreturn";
				}
				else $diagnostics = "$ping :: $pingreturn";
				unset($pingoutput);
				unset($ping);
				break;
			default:
				$time_start = microtime(true);
				$fp = @fsockopen($host, $port, $errno, $errstr, $config['timeout']);
				$time_end = microtime(true);
				$time = number_format(($time_end - $time_start)*1000,1);

				if ($fp)
				{
					$status = $config['online'];
					$diagnostics = "$time ms";
					fclose($fp);
				}
				else if ($errno<0) { $diagnostics = "errno=$errno; Host unknown?"; }
				else { $diagnostics = $errstr; }
		}

		if ($output)
		echo "<tr><td>$description</td><td class=\"$status\" title=\"$diagnostics\">$status</td></tr>\n";
		$cache->flush();
	}

	echo "</table>\n";

		echo '<div class="alert-note" style="margin-top:1.5em; font-size:90%; line-height:1.5;">'
		   . '<strong>Dlaczego część usług może być niewidoczna?</strong><ul>'
		   . '<li>Skrypt sprawdza wyłącznie porty <em>TCP</em> (fsockopen) &ndash; usług działających na UDP (np. DNS na porcie 53) w ten sposób nie wykryje.</li>'
		   . '<li>Firewall hostingu może blokować porty od zewnątrz &ndash; ta sama usługa bywa "Online" od wewnątrz (localhost) i "Offline" z zewnątrz.</li>'
		   . '<li>Usługa może nasłuchiwać tylko na 127.0.0.1 albo na gnieździe uniksowym (np. MySQL przez socket), albo na nietypowym porcie.</li>'
		   . '<li>Na hostingu współdzielonym serwer jest odizolowany (kontener/wirtualizacja), więc to, co widać z poziomu konta, nie musi być pełną listą usług maszyny.</li>'
		   . '</ul></div>';

	if ($config['progressindicator']) {
		echo <<<EOT
<script>
progressindicator = document.getElementById("progress");
progressindicator.innerHTML = "asdf";
progressindicator.style.visibility = 'hidden';
</script>
EOT;
	}

	if (!empty($config['version'])) echo "<p class=\"version\">{$config['version']}</p>\n";
	echo "{$config['htmlfooter']}\n";

}

class cache{

	private $file;

	private $time;

	private $filetime;

	private $buffer='';

	private $enabled=false;

	public function __construct($file, $config){
		if($config['cachepath']!=null && $config['cachepath']!=false && $config['cachetime']>0){
			$this->file=$config['cachepath'].'/'.$file;
			$this->time=$config['cachetime'];

			$output_buffering = ini_get("output_buffering");
			if($config['progressindicator']==TRUE && $output_buffering > 0){
				error_log("warning: progressindicator incompatible with php.ini output_buffering > 0. Caching disabled! Create a .htaccess with the contents: php_value output_buffering \"0\"");
				return;
			}
			if((file_exists($this->file) && is_writable($this->file)) || is_writable(dirname($this->file))){
					$this->enabled=true;
			}
		}
	}

	private function isCached(){
		$this->filetime=@filemtime($this->file);
		if(file_exists($this->file) && (time() - $this->time <$this->filetime)){
			return true;
		}
		return false;
	}

	public function start(){
		if(!$this->enabled)return;
		if($this->isCached()){
			@include $this->file;
			echo "<!-- Cached ".date('jS F Y H:i', $this->filetime)."-->";
			exit;
		}
		ob_start();
	}

	public function flush(){
		if($this->enabled){
			$this->buffer.=ob_get_contents();
			ob_flush();
		}
		flush();
	}

	public function end(){
		if(!$this->enabled)return;
		if((file_exists($this->file) && is_writable($this->file)) || is_writable(dirname($this->file))){
			$fp=fopen($this->file,'w');
			fwrite($fp, $this->buffer . ob_get_contents());
			fclose($fp);
			ob_end_flush();
		}
	}
}

$config = defaultConfig();

if($argv[1] == 'genconfig'){
	genConfig();
	exit;
}

$config = parseConfig($config['configfile'], $config);

if($_REQUEST['rss'] !== NULL or $argv[1] == 'rss'){
	$cache=new cache('rss.xml',$config);
	$cache->start();
	rss();
	$cache->end();
}else if($argv[1] == 'setstatus'){
	genStatusUpdate();
}else if($argv[1] == '-h' || $argv[1] == '--help'){
	echo <<<EOT
{$config['version']} Help
	rss - Output RSS
	setstatus - netstat.txt generation helper
	genconfig - generate sample netstat.conf.php

EOT;
}else{
	$cache=new cache('index.html',$config);
	$cache->start();
	html($cache);
	$cache->end();
}

?>
