<?php

//if(isset($_GET['data']))$data = $_GET['data']; else $data="";
if(isset($_GET['url']))$url = $_GET['url']; else $url="";
if(isset($_GET['query']))$query = $_GET['query']; else $query="";
if(isset($_GET['act']))$action = $_GET['act']; else $action="";
if(isset($_GET['naming']))$naming = $_GET['naming']; else $naming="";
if(isset($_GET['timebase']))$timebase = $_GET['timebase']; else $timebase="";

$redirecturl = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx.php";

$redirecturl = "<![CDATA[http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/op2gpx.php";

if($query!=""){
	error_log("got query: $query");
	$redirecturl = $redirecturl."?query=".urlencode($query); 	//op query -> urlencode		
}
else if($url!=""){
	error_log("got url: $url");
	$redirecturl = $redirecturl."?url=".$url;				//full op url with urlencoded query
}
//error_log($url);
//error_log("action: '$action'");

if($naming!="")
	$redirecturl = $redirecturl."&naming=".$naming;

if($timebase!="")
	$redirecturl = $redirecturl."&timebase=".$timebase;	

$redirecturl .= "]]>";

//error_log($redirecturl);

print("<?xml version=\"1.0\" encoding=\"utf-8\"?>
	<locusActions>
	<download>
	        <source>
	        $redirecturl
	        </source>
	        <dest><![CDATA[/mapItems/op2gpx.gpx]]></dest>");

if($action == "import")
	print("<after>importData</after>");	
else
	print("<after>displayData</after>");

print("</download>
	</locusActions>");

?>