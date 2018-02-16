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
$shpmode = $_GET['shpmode'];

//print html skel
print("<html><body>");
print("opq2locus got input:<br>$input<br><br>");

//construct the url
$url = $input;

if(!strpos($url, "out:json"))
	$url = "[out:json]".$url;

$url = urlencode($url);

$url = preg_replace($patterns, $replacements, $url);

$url = $locusurl."?query=".$url;

if($locusaction == "import")
	$url = $url."&act=import";

if($naming != "")
	$url = $url."&naming=".$naming;

if($tbase != "server")
	$url = $url."&timebase={timeUtc}";
	
if($shpmode != "")
	$url = $url."&shpmode=".$shpmode;

	//print("&timebase={timeUtc}");

print("locus-url:<br> $url<br><br>");

print("</body></html>");

?>
