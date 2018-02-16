<?php

$scriptver = "vX.x.X";
$info = "converted by op2gpx ".$scriptver;

$trackspeed = 5; //in Km/h
$secperm = 3.6/$trackspeed;

$nodekeynames = array(
    "name",
    "power",
    "natural",
    "highway",
    "amenity",
    "place",
    "shop",
    "barrier",
    "entrance",
    "tourism",
    "man_made",
    "building",
    "emergency",
    "wheelchair",
    "crossing");

//ToDo: refine way key names
 $waykeynames = array(
    "name",
    "power",
    "natural",
    "highway",
    "amenity",
    "place",
    "shop",
    "barrier",
    "entrance",
    "tourism",
    "man_made",
    "building",
    "emergency",
    "wheelchair",
    "crossing");

//ToDo: extend realtion key names
 $relkeynames = array(
    "name",
    "route");

//error_log("+++++++++++ op2gpx.php +++++++++++");

class node {
    public $id;
    public $name = "";
    public $desc = "";
    public $comment = "";    
    public $lat;
    public $lon;
    public $type = "";
    public $time = "";
}

class wayseg {
    public $nodes = array();
    public $length = 0;
}

class way {
    public $id;
    public $type;
    public $comment = "";
    public $name = "";
    public $desc = "";
    public $wayseg = array();
}

function haversineGreatCircleDistance($node1, $node2)
{
    $earthRadius = 6371000;
    // convert from degrees to radians
    $latFrom = deg2rad($node1->lat);
    $lonFrom = deg2rad($node1->lon);
    $latTo = deg2rad($node2->lat);
    $lonTo = deg2rad($node2->lon);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function interPoint($node1, $node2, $dist, $fraction)
{
    $earthRadius = 6371000;
    $outnode = new node;
    // convert from degrees to radians
    $latFrom = deg2rad($node1->lat);
    $lonFrom = deg2rad($node1->lon);
    $latTo = deg2rad($node2->lat);
    $lonTo = deg2rad($node2->lon);

    $c = $dist / $earthRadius;
    //$c = $dist;

    $a = sin((1 - $fraction) * $c) / sin($c);
    $b = sin($fraction * $c) / sin($c);

    $x = $a * cos($latFrom) * cos($lonFrom) + $b * cos($latTo) * cos($lonTo);
    $y = $a * cos($latFrom) * sin($lonFrom) + $b * cos($latTo) * sin($lonTo);
    $z = $a * sin($latFrom) + $b * sin($latTo);

    $outnode->lat = rad2deg(atan2($z, sqrt($x * $x + $y * $y)));
    $outnode->lon = rad2deg(atan2($y, $x));

    error_log("interpoint ".$outnode->lat." ".$outnode->lon);

    return $outnode;
}

function outputwpts($nodes)
{
    foreach($nodes as $node)        
    {
        //error_log("output a node");
        print("<wpt lat=\"$node->lat\" lon=\"$node->lon\">
            \t<name>$node->name</name>
            \t<desc>$node->desc</desc>
            \t<ele>0.00</ele>
            \t<link href=\"http://www.openstreetmap.org/node/$node->id\"><text>http://www.openstreetmap.org/node/$node->id</text></link>
            \t<cmt>$node->comment</cmt>");

        if($node->time != "")
            print("<time>".$node->time."</time>");    

        print("</wpt>\n");
        
        //print("<cmt>http://www.openstreetmap.org/node/$node->id</cmt>");
    }
}

function outputtracks($ways, $withtime, &$waypts)
{
    //error_log("output a track");
    foreach ($ways as $way) {

        if($withtime != "")
            $time_utc = new DateTime(null, new DateTimeZone("UTC"));

        //new track
        print("<trk>
            \t<name>$way->name</name>
            \t<desc>$way->desc</desc>
            \t<link href=\"http://www.openstreetmap.org/$way->type/$way->id\"><text>http://www.openstreetmap.org/$way->type/$way->id</text></link>\n");
 
        if($way->comment != "")
            print("<cmt>$way->comment</cmt>");

        foreach($way->wayseg as $segment)
        {
            print("\t<trkseg>\n");

            $nodecount = 0;
            //output the nodes of the wayseg
            foreach($segment->nodes as $node)
            {
                if(is_object($node))
                {                
                //error_log("output a way-node");
                print("\t\t<trkpt lat=\"$node->lat\" lon=\"$node->lon\"><ele>0.00</ele>");

                if($withtime != ""){
                    $secperm = $GLOBALS["secperm"];
                    if($nodecount++ > 0){
                        $dist = haversineGreatCircleDistance($oldnode, $node);
                        $timediff = floor($dist * $secperm);
                        $time_utc->add(new DateInterval("PT".$timediff."S"));                    
                    }

                    print("<time>".$time_utc->format(DateTime::ISO8601)."</time>");     

                    if($node->type == "wpt"){
                        $wpt = clone($node);
                        $wpt->time = $time_utc->format(DateTime::ISO8601);
                        $waypts[] = $wpt;
                    } 

                    $oldnode = $node;                    
                }

                print("</trkpt>\n");
                }
                else
                    error_log("not an object! ".$nodecount);
            }
            print("\t</trkseg>\n");
        }    
        //end of track
        print("</trk>\n");
    }
 }

function outputgpx ($nodes, $ways, $url, $mime)
{
    error_log("++outputgpx");

    if ($mime)
    {
        header('Content-Type: '.$mime);
    }
    else
    {
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="op2gpx.gpx"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private');
        header('Pragma: private');
    }

    //$url = urldecode($url);

    print("<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\"?>
        <gpx version=\"1.1\" creator=\"op2gpx\"
        xmlns=\"http://www.topografix.com/GPX/1/1\"
        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
        xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd\"
        xmlns:gpx_style=\"http://www.topografix.com/GPX/gpx_style/0/2\"
        xmlns:gpxx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\"
        xmlns:gpxtrkx=\"http://www.garmin.com/xmlschemas/TrackStatsExtension/v1\"
        xmlns:gpxtpx=\"http://www.garmin.com/xmlschemas/TrackPointExtension/v2\"
        xmlns:locus=\"http://www.locusmap.eu\">
        <metadata>
        \t<desc>$url</desc>
        </metadata>\n");

    outputtracks($ways, 1, $waypts);
    outputwpts($waypts);
    outputwpts($nodes);

    //end of gpx
    print("</gpx>\n");

    error_log("--outputgpx");
}

//input: json
//type: node, way, rel
//naming: id, tags, mtb?
//output: element to set name and desc of
function get_name_desc($input, $type, $naming, &$output)
{
    if(isset($input) && isset($type)){
        if(property_exists($input, 'tags')){
            if($naming != "id"){
                //get name
                if($type == "node")
                    $namekeys = $GLOBALS["nodekeynames"];
                if($type == "way")
                    $namekeys = $GLOBALS["waykeynames"];                
                if($type == "relation")
                    $namekeys = $GLOBALS["relkeynames"];                

                foreach($namekeys as $nodename){
                    if(array_key_exists($nodename, $input->tags)){
                        if($nodename != "name")
                            $output->name = $nodename . "=";    
                        $output->name .= $input->tags->$nodename;
                        break;
                    }
                }
            }

            //fill desc
            foreach($input->tags as $key => $value)
                $output->desc .= $key . "=" . $value . "\n";
        }

        //if no name was found, use id
        if($output->name == "")
            $output->name = $input->id;
    }    
}

// scans $jsoninput for nodes and adds them to $nodesoutput
// also removes them from $jsoninput for speedup of later scans
function getnodes(&$jsoninput, $naming, &$nodesoutput)
{
    $consumedresponsenodes = array();

    foreach($jsoninput->elements as $reskey => $ele)
    {   
        $curnode = new node;
        //var_dump($ele);
    
        if($ele->type=="node")
        {
            get_name_desc($ele, $ele->type, $naming, $curnode);

            //also save the id (helps build up the ways afterwards)
            $curnode->id = $ele->id;

            $curnode->lat = $ele->lat;
            $curnode->lon = $ele->lon;

            $curnode->type = "wpt"; //add it as a waypoint first

            //add it to the array
            $nodesoutput[] = $curnode;
            //remove it from response afterwards
            $consumedresponsenodes [] = $reskey;
        }    
    }
    //remove all the nodes from response (to speed up ways and relation scanning)
    foreach($consumedresponsenodes as $consumedkey)
    {
        //error_log("remove element ".$consumedkey);
        unset($jsoninput->elements[$consumedkey]);
    }
}

// scans $jsoninput for ways and adds them to $waysoutput
// also removes them from $jsoninput for speedup of later scans
// also removes way-nodes from $nodesinput
function getways(&$jsoninput, $naming, &$nodesinput, &$waysoutput)
{
    $consumedresponseways = array();
    $consumednodes = array();

    foreach($jsoninput->elements as $reskey => $ele)
    {   
        //var_dump($ele);
    
        if ($ele->type=="way")
        {
            $curway = new way;
            $curway->type = "way";
            $curway->wayseg[0] = new wayseg;
            $curway->wayseg[0]->length = 0;
            //error_log("->way found");

            get_name_desc($ele, $ele->type, $naming, $curway);

            //error_log("way name is ".$curway->name);

            //also save the id (helps build up the relations afterwards)
            $curway->id = $ele->id;

            $nodecount = 0;
            //now add all the nodes building up the way            
            foreach ($ele->nodes as $waynode)
            {
                //error_log("checking for way node ".$waynode);
                //search through all nodes for the way node
                foreach($nodesinput as $allnodeskey => $temp)
                {
                    //error_log("check ".$waynode." against ".$temp->id." (allnodes key is ".$allnodeskey.") elem type is ".gettype($temp));

                    if($temp->id==$waynode)
                    {
                        //error_log("found way-node ".$temp->lon.", ".$temp->lat);                        
                        $temp->type = "trkpt";

                        if($nodecount++ > 0)
                            $curway->wayseg[0]->length += haversineGreatCircleDistance(lastnode($curway->wayseg[0]), $temp);

                        $curway->wayseg[0]->nodes[] = $temp;      
                        $consumednodes[] = $allnodeskey; //save the array key of the used node, to remove later  
                        break;                               
                    }
                }
            }
            //add to ways array
            $waysoutput[] = $curway;
            //remove it from response afterwards
            $consumedresponseways [] = $reskey;            
        }
    }
    //remove nodes consumed by ways from $nodesinput
    //(they should not be returned as separate POIs)
    foreach($consumednodes as $consumedkey)
    {
        //error_log("remove element ".$consumedkey);
        unset($nodesinput[$consumedkey]);
    }
    //remove all the ways from response (to speed up relation scanning)
    foreach($consumedresponseways as $consumedkey)
    {
        //error_log("remove element ".$consumedkey);
        unset($jsoninput->elements[$consumedkey]);
    }
}

// scans $jsoninput for relations and adds them as ways to $waysoutput
// also removes relation-nodes from $nodesinput
// also removes relation-ways from $waysoutput 
function getrels(&$jsoninput, $naming, $shpmode, &$nodesinput, &$waysoutput)
{
    $consumednodes = array();
    $consumedways = array();

    foreach($jsoninput->elements as $ele)
    {   
        //var_dump($ele);
    
        if ($ele->type=="relation")
        {
            $curway = new way;
            $curway->type = "relation";
            //error_log("->relation found");

            get_name_desc($ele, $ele->type, $naming, $curway);

            error_log("relation name is ".$curway->name);

            $curway->id = $ele->id;

            //now add all the nodes and ways contained in the relation
            if(property_exists($ele, 'members'))
            foreach ($ele->members as $relmember)
            {
                //error_log("checking for relation member ".$relmember->ref);
                if($relmember->type=="node")
                {
                    //search through $allnodes for the relation member node
                    foreach($nodesinput as $elemkey => $temp)
                    {
                        //error_log("check relmember ref".$relmember->ref." against ".$temp->id." (elem key is ".$elemkey.") elem type is ".gettype($temp));

                        if($temp->id==$relmember->ref)
                        {//found a relation node
                            //error_log("found relation-node ".$temp->lon.", ".$temp->lat);                        
                            //$allnodes[] = $temp;     //don't include nodes for now
                            $consumednodes[] = $elemkey; //save the array key of the used node, to remove later  
                            break;                               
                        }      
                    }
                }

                if($relmember->type=="way")
                {
                    //search through $allways for the relation member way
                    foreach($waysoutput as $elemkey => $temp)
                    {
                        //error_log("check rel way ref ".$relmember->ref." against ".$temp->id." (elem key is ".$elemkey);

                        if($temp->id==$relmember->ref)
                        {//found the relation way
                            //add it to the current way's waysegemnts
                            //error_log("found relation-way");       

                            $curway->wayseg[] = clone($temp->wayseg[0]);

                            $consumedways[] = $elemkey; //save the array key of the used way, to remove later  
                            break;                               
                        }      
                    }
                }                
            }
            //try to fix segment directions
            $curway = fixwaysegs($curway);
            if($shpmode > 0)
                $curway = insertwaysegstartpoints($curway);                
            if($shpmode > 1)            
                $curway = insertwaysegmidpoints($curway);


            //add to ways array
            $waysoutput[] = $curway;
        }
    }

    //remove nodes consumed by relations from $nodesinput
    //(they should not be returned as separate POIs)
    foreach($consumednodes as $consumedkey)
    {
        //error_log("remove element ".$consumedkey);
        unset($nodesinput[$consumedkey]);
    }

    //remove ways consumed by relations
    //(they should not be returned as separate ways)
    foreach($consumedways as $consumedkey)
    {
        //error_log("remove element ".$consumedkey);
        unset($waysoutput[$consumedkey]);
    }    
}

function firstnode($waysegment)
{
    return(reset($waysegment->nodes));
}

function lastnode($waysegment)
{
    return(end($waysegment->nodes));
}

function nodes_match($node1, $node2)
{
    if($node1->id == $node2->id)
        return true;    
    else
        return false;
}

// trys to fix directions of way segments (constructed from a relation)
// to all go in the same direction
function fixwaysegs($inputway)
{
    $outputway = $inputway;
    $numsegs = count($outputway->wayseg);
    //variables for debugging
    $fixedsegs = 0;
    $gaps = 0;

    $fix1 = 0;
    $fix2 = 0;
    $fix3 = 0;

    if($numsegs > 1)
    {
        $metaseg_count = 2;
        for($i=1; $i<$numsegs; $i++)
        {
            if(nodes_match(lastnode($outputway->wayseg[$i-1]), firstnode($outputway->wayseg[$i])))
            {
                //segs are already continuous, nothing to do!
                $metaseg_count++;
            }
            else if(nodes_match(lastnode($outputway->wayseg[$i-1]), lastnode($outputway->wayseg[$i])))
            {
                //current seg has to be reverted
                $outputway->wayseg[$i]->nodes = array_reverse($outputway->wayseg[$i]->nodes);
                $metaseg_count++;
                $fixedsegs++;
                $fix1++;
            }
            //if at the beginning of a meta-seg, we can still reverse the first seg
            else if($metaseg_count == 2)
            {
                if(nodes_match(firstnode($outputway->wayseg[$i-1]), firstnode($outputway->wayseg[$i])))
                {
                    //reverse the direction of the first seg
                    $outputway->wayseg[$i-1]->nodes = array_reverse($outputway->wayseg[$i-1]->nodes);
                    $metaseg_count++;
                    $fixedsegs++;
                    $fix2++;
                }
                else if(nodes_match(firstnode($outputway->wayseg[$i-1]), lastnode($outputway->wayseg[$i])))
                {
                    //reverse the direction of both segs
                    $outputway->wayseg[$i-1]->nodes = array_reverse($outputway->wayseg[$i-1]->nodes);
                    $outputway->wayseg[$i]->nodes = array_reverse($outputway->wayseg[$i]->nodes);
                    $metaseg_count++;               
                    $fixedsegs+=2;
                    $fix3++;
                }
            }
            else //we have found a gap -> reset metaseg
            {
                //error_log("gap ".($i-1)."-".$i);
                $i--;
                $metaseg_count = 2;
                $gaps++;
            }
        }
    }

    //print out some stats to log
    //error_log("fixed ".$fixedsegs."/".$numsegs.", found ".$gaps." gaps");
    
    //add debugging info to resulting way
    $outputway->comment = $outputway->comment."\nfixed ".$fixedsegs."/".$numsegs."(".$fix1."|".$fix2."|".$fix3.")".", found ".$gaps." gaps";

    //$outputway->name = $outputway->name."-fixed";

    return $outputway;
}


function insertwaysegstartpoints($inputway)
{
    $outputway = $inputway;

    for($ws=0; $ws < count($outputway->wayseg); $ws++)
    {
        $outputway->wayseg[$ws]->nodes[0]->type = "wpt";
        $outputway->wayseg[$ws]->nodes[0]->name = "shapingpoint";                        
    }
    return $outputway;
}


function insertwaysegmidpoints($inputway)
{
    $outputway = $inputway;

    foreach($inputway->wayseg as $segment)
    {
        $curlen = 0;
        $nodecount = 0;
        foreach($segment->nodes as $node)
        {
            if($nodecount++ > 0){
                $partlen = haversineGreatCircleDistance($oldnode, $node);
                $curlen += $partlen;                
            }

            if($curlen > ($segment->length / 2))
            {
                //error_log("insert midpoint before node ".$nodecount." (ovlen ".$segment->length." cur ".$curlen);

                $fraction = ($curlen - ($segment->length / 2)) / $partlen;

                //error_log("dnodes ".$partlen." frac ".$fraction);

                $newnode = interPoint($node, $oldnode, $partlen, $fraction);
                $newnode->type = "wpt";
                $newnode->name = "shapingpoint";                

                array_splice( $segment->nodes, $nodecount - 1, 0, array($newnode));
                break;
            }
            $oldnode = $node;
        }
    }
    return $outputway;
}

// converts relative date constructs from overpass wizard ({{date:xxx}})
// to an ISO8601 date string (relative to server time) 
function modify_url_dates(&$url, $basetime)
{
    $matches = array();

    $ret = preg_match_all( '{\{\{date:(([0-9]+)\s(year|month|week|day|hour|minute|second)s?)\}\}}', 
                    $url , $matches, 0 , 0);

    if($ret){
        if($basetime == "server")
            $date_utc = new DateTime(null, new DateTimeZone("UTC"));
        else
            $date_utc = new DateTime($basetime, new DateTimeZone("UTC"));            

        for($matchindex=0; $matchindex < count($matches[0]); $matchindex++)
        {   
            $date_mod = clone $date_utc;            
            $date_mod->modify("-".$matches[1][$matchindex]);

            $url = str_replace($matches[0][$matchindex], $date_mod->format(DateTime::ISO8601), $url);
        }
    }
    else
        error_log("no date string found!");    
}

if(isset($_GET['mime']))$mime = $_GET['mime']; else $mime="";
if(isset($_GET['url']))$url = $_GET['url']; else $url="";
if(isset($_GET['naming']))$naming = $_GET['naming']; else $naming="tags";
if(isset($_GET['timebase']))$timebase = $_GET['timebase']; else $timebase="server";
//if(isset($_GET['data']))$data = $_GET['data']; else $data="";
if(isset($_GET['query']))$query = $_GET['query']; else $query="";
if(isset($_GET['shpmode']))$shpmode = $_GET['shpmode']; else $shpmode="";

$query = urldecode($query);
error_log($query);

modify_url_dates($query, $timebase);
error_log($query);


//if ($data)
if ($query)
{
	$url = "https://overpass-api.de/api/interpreter?data=".urlencode($query);	
}
else
{
	$url = str_replace(' ', '%20', $url);
	$url = str_replace('`', '%20', $url);
}

error_log($url);
//modify_url($url);
//error_log($url);

//get the response from overpass api
$response = file_get_contents($url);
if(!$response)
{
    http_response_code(404);
}
else
{
    //decode the json response
    $json = json_decode($response);

    if($json)
    {
        $allnodes = array();
        $allways = array();

        //1. get all nodes of the response    
        getnodes($json, $naming, $allnodes);

        //2. get all ways of the response (consumes nodes from $allnodes)    
        getways($json, $naming, $allnodes, $allways);

        //3. get all relations of the response 
        //(consumes nodes from $allnodes and ways from $allways)    
        getrels($json, $naming, $shpmode, $allnodes, $allways);

        //now construct the gpx output and return it
        outputgpx ($allnodes, $allways, $url, $mime);
    }
}

?>
