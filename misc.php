<?php

//[timeout:120]

function get_query_timeout($query)
{
	if(preg_match('/\[timeout:(\d*)\]/', $query, $regmatch))
		return $regmatch[1];
	else
		return 120;	//overpass default
}

function get_timeout_with_margin($timeout, $reroute)
{
	$ret = ($timeout * 1.15) + 5;
	if($reroute != "0")
		$ret += 20;
	return ceil( $ret );
}

?>