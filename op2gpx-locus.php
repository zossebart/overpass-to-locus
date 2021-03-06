<?php

include 'misc.php';

$redirecturl = "<![CDATA[http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx.php";

$pcount = 0;
$reroute = 0;
foreach($_GET as $idx => $val){
	if($pcount++ == 0)
		$redirecturl .= "?";
	else
		$redirecturl .= "&";

	if($idx != "act"){	// act is handled by this script later
		$redirecturl .= $idx."=";

		if($idx == "reroute")
			$reroute = 1;

		if($idx == "query"){
			// get timeout
			$timeout = get_query_timeout($val);
			$redirecturl .= urlencode($val); // query has to be re-urlencoded
		}
		else
			$redirecturl .= $val; // no special meaning, simply pass the param
	}
}
$redirecturl .= "]]>";

$timeout = get_timeout_with_margin($timeout, $reroute);

print("<?xml version=\"1.0\" encoding=\"utf-8\"?>
	<locusActions>
	<download>
	        <source timeout=\"$timeout\">
	        $redirecturl
	        </source>");

if(isset($_GET['zip']) && $_GET['zip'] == "yes")	        
	print("<dest><![CDATA[/mapItems/op2gpx.zip]]></dest>");
else
	print("<dest><![CDATA[/mapItems/op2gpx.gpx]]></dest>");

if(isset($_GET['act']) && $_GET['act'] == "import")
	print("<after>importData</after>");	
else
	print("<after>displayData</after>");

print("</download>
	</locusActions>");

?>