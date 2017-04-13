<?php

$patterns = array();
$patterns[0] = '(%7B%7Bbbox%7D%7D)';
$patterns[1] = '/{{bbox}}/';

$replacements = array();
$replacements[0] = '{screenLatBottom}%2C{screenLonLeft}%2C{screenLatTop}%2C{screenLonRight}';
$replacements[1] = '{screenLatBottom}%2C{screenLonLeft}%2C{screenLatTop}%2C{screenLonRight}';

$locusurl = "locus-actions://http/".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx-locus.php";

$input = $_GET['input'];
$locusaction = $_GET['locusaction'];
$naming = $_GET['naming'];

//print html skel
print("<html><body>");
print("opq2locus got input:<br>$input<br><br>");

//construct the url
$url = $input;

$url = preg_replace($patterns, $replacements, $url);

$url = str_replace(' ', '%20', $url);
$url = str_replace('`', '%20', $url);
$url = str_replace('&', '%26', $url);

$url = $locusurl."?query=".$url;

if($locusaction == "import")
	$url = $url."&act=import";

if($naming != "")
	$url = $url."&naming=".$naming;

print("locus-url:<br> $url<br><br>");

print("</body></html>");

?>