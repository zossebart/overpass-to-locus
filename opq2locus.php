<?php

include 'misc.php';

$patterns = array();
$patterns[0] = '(%7B%7Bbbox%7D%7D)';
$patterns[1] = '(%7B%7Bcenter%7D%7D)';

$directpatterns = array();
$directpatterns[0] = '(%7B%7Bbbox%7D%7D)';

$replacements = array();
$replacements[0] = '{screenLatBottom}%2C{screenLonLeft}%2C{screenLatTop}%2C{screenLonRight}';
$replacements[1] = '{mapLat}%2C{mapLon}';

$south = $_GET['south'];
$west = $_GET['west'];
$north = $_GET['north'];
$east = $_GET['east'];

$directreplacements = array();
$directreplacements[0] = $south.'%2C'.$west.'%2C'.$north.'%2C'.$east;

$locusurlstart = "locus-actions://http/".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx-locus.php";
$directurlstart = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx.php";

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
$waytopoi = $_GET['waytopoi'];

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

$url = strip_comments($url);

$url = urlencode($url);

$locusurl = preg_replace($patterns, $replacements, $url);
$directurl = preg_replace($directpatterns, $directreplacements, $url);

$locusurl = $locusurlstart."?query=".$locusurl;
$directurl = $directurlstart."?query=".$directurl;

if($locusaction == "import")
	$locusurl = $locusurl."&act=import";

if($tbase == "locus")
	$locusurl = $locusurl."&timebase={timeUtc}";

if($naming != "")
	$endurl = "&naming=".$naming;

$shpmode = 0;

if($shpmode1 != "")
	$shpmode |= $shpmode1;

if($shpmode2 != "")
	$shpmode |= $shpmode2;

if($shpmode3 != "")
	$shpmode |= $shpmode3;
	// | $shpmode2 | $shpmode3;


if($shpmode != 0)
	$endurl = $endurl."&shpmode=".$shpmode;

if($zip == "yes")
	$endurl = $endurl."&zip=yes";

if($editlink != "")
	$locusurl = $locusurl."&editlink=".$editlink;

if($style != "")
	$endurl = $endurl."&style=".$style;

if($waytopoi != "")
	$endurl = $endurl."&waytopoi=".$waytopoi;

	//print("&timebase={timeUtc}");

$locusurl .= $endurl;
$directurl .= $endurl;

print("locus-url :<br> $locusurl<br><br>");
print("direct-url:<br> <a href=$directurl>$directurl</a><br><br>");

print("</body></html>");

?>
