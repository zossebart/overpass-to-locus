<?php

$patterns = array();
$patterns[0] = '(%7B%7Bbbox%7D%7D)';
$patterns[1] = '(%7B%7Bcenter%7D%7D)';

$replacements = array();
$replacements[0] = '{screenLatBottom}%2C{screenLonLeft}%2C{screenLatTop}%2C{screenLonRight}';
$replacements[1] = '{mapLat}%2C{mapLon}';

$locusurl = "locus-actions://http/".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx-locus.php";

$input = $_GET['input'];
$locusaction = $_GET['locusaction'];
$naming = $_GET['naming'];
$tbase = $_GET['timebase'];
$shpmode1 = $_GET['shpmode1'];
$shpmode2 = $_GET['shpmode2'];
$shpmode3 = $_GET['shpmode3'];
$zip = $_GET['zip'];
$editlink = $_GET['editlink'];
$style = $_GET['style'];

//print html skel
print("<html><body>");
print("opq2locus got input:<br>$input<br><br>");

//construct the url
$url = $input;

if(!strpos($url, "out:json")){
	if(!preg_match('/\[(out|timeout|maxsize|bbox|date|diff|adiff)\:.*\]\;/', $url, $dummy))
		$url = ";".$url;	
	$url = "[out:json]".$url;
}

if(!strpos($url, "timeout:")){
	if(!preg_match('/\[(out|timeout|maxsize|bbox|date|diff|adiff)\:.*\]\;/', $url, $dummy))
		$url = ";".$url;		
	$url = "[timeout:25]".$url;
}

$url = urlencode($url);

$url = preg_replace($patterns, $replacements, $url);

$url = $locusurl."?query=".$url;

if($locusaction == "import")
	$url = $url."&act=import";

if($naming != "")
	$url = $url."&naming=".$naming;

if($tbase != "server")
	$url = $url."&timebase={timeUtc}";
	
$shpmode = 0;

if($shpmode1 != "")
	$shpmode |= $shpmode1;

if($shpmode2 != "")
	$shpmode |= $shpmode2;

if($shpmode3 != "")
	$shpmode |= $shpmode3;
	// | $shpmode2 | $shpmode3;


if($shpmode != 0)
	$url = $url."&shpmode=".$shpmode;

if($zip == "yes")
	$url = $url."&zip=yes";

if($editlink != "")
	$url = $url."&editlink=".$editlink;

if($style != "")
	$url = $url."&style=".$style;

	//print("&timebase={timeUtc}");

print("locus-url:<br> $url<br><br>");

print("</body></html>");

?>
