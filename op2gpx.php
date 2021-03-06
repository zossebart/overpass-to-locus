<?php

$scriptver = "vX.x.X";
$info = "converted by op2gpx ".$scriptver;

//$trackspeed = 5; //in Km/h
//$secperm = 3.6/$trackspeed;

$brouter_server = "http://localhost:8080";

include 'styles.php';
include 'misc.php';

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

class bbox {
    public $minlat = 180;
    public $maxlat = -180;
    public $minlon = 180;
    public $maxlon = -180;
}

class linestyle {
    public $color = array(0, 0, 255);
    public $opacity = 0.59;
    public $width = 5.0;

    public function  __construct($c, $o, $w) {
    $this->color = $c;
    $this->opacity = $o;
    $this->width = $w;
    }    
}

//nodes
class node {
    public $id;
    public $name = "";
    public $desc = "";
    public $comment = "";    
    public $lat;
    public $lon;
    public $type = "";
    public $time = "";
    public $cusage = 0;
}

class wayseg {
    public $nodes = array();
    public $length = 0;
}

//ways
class way {
    public $id;
    public $type;
    public $comment = "";
    public $name = "";
    public $desc = "";
    public $wayseg = array();
    public $cusage = 0;
    public $length = 0;
    public $gaps = 0;
    public $style;
    public $nodecount = 0;
    public $withtime = 0;
}

//relations
class rel {
    public $id;
    public $type;
    public $comment = "";
    public $name = "";
    public $desc = "";
    public $way;
    public $pois = array();
    public $vias = array();
    public $shps = array();
    public $cusage = 0;
}

function expand_bbox($bbox, $element)
{
    if(get_class($element) == "node") {
        if($element->lat < $bbox->minlat)
            $bbox->minlat = $element->lat;
       if($element->lat > $bbox->maxlat)
            $bbox->maxlat = $element->lat;
        if($element->lon < $bbox->minlon)
            $bbox->minlon = $element->lon;
        if($element->lon > $bbox->maxlon)
            $bbox->maxlon = $element->lon;
    }

    if(get_class($element) == "way")
        foreach($element->wayseg as $segment)
            foreach($segment->nodes as $node)
                $bbox = expand_bbox($bbox, $node);

    if(get_class($element) == "rel") {
        foreach($element->pois as $node)
            $bbox = expand_bbox($bbox, $node);
        $bbox = expand_bbox($bbox, $element->way);
    }

    return $bbox;
}

function grow_bbox($bbox, $amount)
{
    $bbox->minlat -= $amount;
    $bbox->maxlat += $amount;
    $bbox->minlon -= $amount;
    $bbox->maxlon += $amount;

    return $bbox;
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

    //error_log("interpoint ".$outnode->lat." ".$outnode->lon);

    return $outnode;
}

function getgpxtags($element, $editlink)
{
    $returnstr = "\n<name>$element->name</name>";

    if($element->desc != "")
        $returnstr .= "\n<desc>$element->desc</desc>";

    if($element->id != "") {
        if(get_class($element) == "node"){
            if($element->type == "waycenter")
                $osmtype = "way";
            else if($element->type == "relcenter")
                $osmtype = "relation";
            else
                $osmtype = "node";
        }
        else
            $osmtype = $element->type;

        if($editlink == "osmid")
            $linkstr = "http://www.openstreetmap.org/edit?editor=id&amp;".$osmtype."=".$element->id;
        else if($editlink == "level0")
            $linkstr = "http://level0.osmz.ru/?url=".$osmtype."/".$element->id;
        else if($editlink == "vespucci"){
            $editbbox = new bbox;

            $editbbox = expand_bbox($editbbox, $element);
            $editbbox = grow_bbox($editbbox, 0.0004);

            $linkstr = "http://127.0.0.1:8111/load_and_zoom?left=".$editbbox->minlon."&amp;bottom=".$editbbox->minlat."&amp;right=".$editbbox->maxlon."&amp;top=".$editbbox->maxlat."&amp;select=".$osmtype.$element->id;
        }
        else
            $linkstr = "http://www.openstreetmap.org/$osmtype/$element->id";

        $returnstr .= "\n<link href=\"".$linkstr."\"><text>".$linkstr."</text></link>";
    }

    if($element->comment != "")
        $returnstr.="\n<cmt>$element->comment</cmt>";   

    if(get_class($element) == "node") {
        if($element->time != "")
            $returnstr.="\n<time>".$element->time."</time>";    

        $returnstr.="\n<ele>0.00</ele>";    
    }

    return $returnstr; 
}

function outputwpt($node, $ignoreusage, $editlink)
{
    $returnstr = "";

    if(!$ignoreusage && $node->cusage > 0)
        return;

    //error_log("output a node");
    $returnstr .= "<wpt lat=\"$node->lat\" lon=\"$node->lon\">";
    $returnstr .= getgpxtags($node, $editlink);
    $returnstr .= "</wpt>\n";
        
    //print("<cmt>http://www.openstreetmap.org/node/$node->id</cmt>");
    return $returnstr;
}

function outputtrack($way, $editlink, $trackspeed)
{
    $returnstr = "";
    $waypts = array();
    //error_log("output a track");
    if($way->cusage > 0)
        return;

    if($way->nodecount < 2)
        return;

    if($trackspeed != ""){
        $time_utc = new DateTime(null, new DateTimeZone("UTC"));
        $secperm = 3.6 / $trackspeed;
    }

    //new track
    $returnstr .= "<trk>\t".getgpxtags($way, $editlink);

    // style
    if($way->style != ""){
        $returnstr .= "\n<extensions>\n<gpx_style:line>";

        $returnstr .= "\n<gpx_style:color>".sprintf("%02x%02x%02x", $way->style->color[0], $way->style->color[1], $way->style->color[2])."</gpx_style:color>";
        $returnstr .= "\n<gpx_style:opacity>".$way->style->opacity."</gpx_style:opacity>";
        $returnstr .= "\n<gpx_style:width>".$way->style->width."</gpx_style:width>";

        $returnstr .= "\n</gpx_style:line>\n</extensions>";        
    }

    foreach($way->wayseg as $segment)
    {
        $returnstr.="\t<trkseg>\n";

        $nodecount = 0;
        //output the nodes of the wayseg
        foreach($segment->nodes as $node)
        {
            if(is_object($node))
            {                
                //error_log("output a way-node");
                $returnstr.="\t\t<trkpt lat=\"$node->lat\" lon=\"$node->lon\"><ele>0.00</ele>";

                if($trackspeed != ""){
                    if($nodecount++ > 0){
                        $dist = haversineGreatCircleDistance($oldnode, $node);
                        $timediff = floor($dist * $secperm);
                        $time_utc->add(new DateInterval("PT".$timediff."S"));                    
                    }

                    $returnstr.="<time>".$time_utc->format(DateTime::ISO8601)."</time>";     
                    $oldnode = $node;                    
                }

                if($node->type == "wpt"){
                    $wpt = clone($node);
//                    if($trackspeed != "")
//                        $wpt->time = $time_utc->format(DateTime::ISO8601);
                    $waypts[] = $wpt;
                } 

                $returnstr.="</trkpt>\n";
            }
            else
                error_log("not an object! ".$nodecount);
        }
        $returnstr.="\t</trkseg>\n";
    }    
    //end of track
    $returnstr.="</trk>\n";
 
    // output waypoints
    foreach($waypts as $waypt)
        $returnstr .=  outputwpt($waypt, 1, $editlink);

    return $returnstr;
 }

function outputrel($rel, $editlink, $trackspeed)
{
    $returnstr = "";
    $returnstr .= outputtrack($rel->way, $editlink, $trackspeed);

    foreach($rel->pois as $poi){
        $returnstr .= outputwpt($poi, 0, $editlink);
    }

    return $returnstr;
}

function outputhttpheader($mime)
{
    if ($mime)
    {
        header('Content-Type: '.$mime);
    }
    else
    {
        header('Content-Type: application/force-download');
    }
    
    header('Content-Disposition: attachment; filename="op2gpx.gpx"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: private');
    header('Pragma: private');
}

function reroute($rel, $broute, $editlink, $trackspeed)
{
    $lonlats = "";

    foreach($rel->way->wayseg as $wayseg)
        foreach($wayseg->nodes as $node){
            if($node->type == "wpt"){
                if($lonlats != "")
                    $lonlats .= "|";
                $lonlats .= $node->lon.",".$node->lat;
            }
    }

    $default_socket_timeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 120);

    $response = file_get_contents($GLOBALS["brouter_server"].'/brouter?lonlats='.$lonlats.'&nogos=&profile='.$broute.'&alternativeidx=0&format=gpx');

    ini_set('default_socket_timeout', $default_socket_timeout);

    if($response){
        if(preg_match("/\<wpt/m", $response, $matches, PREG_OFFSET_CAPTURE)){
            $returnstr = substr($response, $matches[0][1]);

            if(preg_match("/\<\/gpx/m", $returnstr, $matches, PREG_OFFSET_CAPTURE))
                $returnstr = substr($returnstr, 0, $matches[0][1]);                

            //fill in our name, desc and cmt fields
            $rel->way->comment .= "\nrerouted with profile ".$broute;

            return preg_replace("/\<trk\>[.\s]*\<name\>.*\<\/name\>/m", "<trk>\t".getgpxtags($rel->way, $editlink), $returnstr);
        }
    }

    return outputrel($rel, $editlink, $trackspeed);
}

function outputgpx ($nodes, $ways, $rels, $url, $mime, $zipit, $broute, $editlink, $waytopoi, $trackspeed)
{
    $strgpxheader = "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\"?>
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
        </metadata>\n";
    $strgpxfooter = "</gpx>\n";

    error_log("++outputgpx");

    if($zipit){
        $zip = new ZipArchive;
        $zip->open('op2gpx.zip', ZipArchive::CREATE|ZipArchive::OVERWRITE);
    }

    $strdata = "";

    foreach($rels as $rel){
        if($rel->way->gaps == 0 && $broute != "")
            $strdata = reroute($rel, $broute, $editlink, $trackspeed);
        else
            $strdata .= outputrel($rel, $editlink, $trackspeed);    
  
        if($zipit && $strdata != ""){
            $zip->addFromString('op2gpx-rel'.$rel->id.'.gpx', $strgpxheader.$strdata.$strgpxfooter);
            $strdata = "";
        }                
    }

    if($zipit)
        $strdata = "";
    foreach($ways as $way){
        if ($waytopoi != ""){
            if($waytopoi < 0)
                $frac = 0;
            else if($waytopoi > 100)
                $frac = 1;
            else
                $frac = ($waytopoi / 100);

            generate_wayseg_point($way->wayseg[0], $frac, $newnode);

            $newnode->type = "waycenter";
            copy_meta($way, $newnode);

            $nodes[] = $newnode;
        }
        else
            $strdata .= outputtrack($way, $editlink, $trackspeed);
    }
    if($zipit && $strdata != "")
        $zip->addFromString('op2gpx-ways.gpx', $strgpxheader.$strdata.$strgpxfooter);

    if($zipit)
        $strdata = "";
    foreach($nodes as $node)
        $strdata .= outputwpt($node, 0, $editlink);
    if($zipit && $strdata != "")
        $zip->addFromString('op2gpx-nodes.gpx', $strgpxheader.$strdata.$strgpxfooter);

    if($zipit){
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=op2gpx.zip');
        header('Content-Length: ' . filesize('op2gpx.zip'));

        readfile('op2gpx.zip');
    }
    else{
        outputhttpheader($mime);        
        print($strgpxheader);        
        print($strdata);    
        print($strgpxfooter);
    }

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

function get_style($input, &$output, $style)
{
    if($style == "mtb")
        $spec = $GLOBALS["mtblinestyle"];
    else {
        $output->style = new linestyle(array(0,0,255), 0.59, 5.0);            
        return; //default style
    }

    if(isset($input))
        if(property_exists($input, 'tags'))
            foreach($spec as $checkspec){
                $ckey = $checkspec->key;
                if(array_key_exists($ckey, $input->tags))
                    if($checkspec->value == $input->tags->$ckey){
                        $output->style = $checkspec->style;
                        return;
                    }
            }

    $output->style = $spec["default"]->style;
}

function copy_meta($src, $dst)
{
    $dst->id = $src->id;
    $dst->name = $src->name;
    $dst->desc = $src->desc;
    $dst->comment = $src->comment;
}

function get_center_node($ele, $curele, $type)
{
    if (property_exists($ele, 'center')){
        $tmpnode = new node;

        $tmpnode->lat = $ele->center->lat;
        $tmpnode->lon = $ele->center->lon;
        $tmpnode->type = $type;

        copy_meta($curele, $tmpnode);

        return $tmpnode;
    }

    return NULL;
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
// also increments counter of "consumed" way-nodes in $nodesinput
function getways(&$jsoninput, $naming, $style, &$nodesinput, &$waysoutput)
{
    $consumedresponseways = array();

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
            
            if($style != "")
                get_style($ele, $curway, $style);

            //error_log("way name is ".$curway->name);

            //also save the id (helps build up the relations afterwards)
            $curway->id = $ele->id;

            $curway->nodecount = 0;
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

                        if($curway->nodecount++ > 0)
                            $curway->wayseg[0]->length += haversineGreatCircleDistance(lastnode($curway->wayseg[0]), $temp);

                        $curway->wayseg[0]->nodes[] = clone($temp);      
                        // increment usage count
                        $nodesinput[$allnodeskey]->cusage++;
                        break;                               
                    }
                }
            }

            //if there is a center, add it as node
            if (property_exists($ele, 'center'))
                $nodesinput[] = get_center_node($ele, $curway, "waycenter");

            $waysoutput[] = $curway; //add to ways array

            //remove it from response afterwards
            $consumedresponseways [] = $reskey;            
        }
    }

    //remove all the ways from response (to speed up relation scanning)
    foreach($consumedresponseways as $consumedkey)
    {
        //error_log("remove element ".$consumedkey);
        unset($jsoninput->elements[$consumedkey]);
    }
}

// scans $jsoninput for relations and adds them to $relsoutput
// also increments counter of "consumed" rel-nodes in $nodesinput
// also increments counter of "consumed" way in $waysinput 
function getrels(&$jsoninput, $naming, $shpmode, $broute, &$nodesinput, &$waysinput, &$relsoutput)
{
    foreach($jsoninput->elements as $ele)
    {   
        //var_dump($ele);
    
        if ($ele->type=="relation")
        {
            $currel = new rel;
            $currel->way = new way;
            $currel->pois = array();

            $currel->way->type = "relation";

            //save the name in the way for now (rel->name unused)
            get_name_desc($ele, $ele->type, $naming, $currel->way);
            error_log("relation name is ".$currel->way->name);

            $currel->id = $ele->id;
            $currel->way->id = $ele->id;
            $currel->way->type = "relation";

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
                            $currel->pois[] = $temp;

                            // increment usage counter
                            $nodesinput[$elemkey]->cusage++;
                            break;                               
                        }      
                    }
                }

                if($relmember->type=="way")
                {
                    //search through $allways for the relation member way
                    foreach($waysinput as $elemkey => $temp)
                    {
                        //error_log("check rel way ref ".$relmember->ref." against ".$temp->id." (elem key is ".$elemkey);

                        if($temp->id==$relmember->ref)
                        {//found the relation way
                            //add it to the current way's waysegemnts
                            //error_log("found relation-way");       

                            $currel->way->wayseg[] = clone($temp->wayseg[0]);
                            $currel->way->nodecount += $temp->nodecount;
                            // increment usage count
                            $waysinput[$elemkey]->cusage++;
                            break;                               
                        }      
                    }
                }                
            }

            if($currel->way->nodecount > 1){
                //try to fix segment directions
                $currel->way = fixwaysegs($currel->way);

                //insert locus shaping points
                if($broute == "" && $shpmode & 1)
                    $currel->way = insertwaysegstartpoints($currel->way);                
                if(/*$broute != "" || */$shpmode & 2)
                    $currel->way = insertwaysegmidpoints($currel->way);
                if($broute != "" || $shpmode & 4)   
                    $currel->way = insertwaysegsemistartpoints($currel->way);
                if($broute != "")
                    $currel->way = insertnumwaysegpoints($currel->way, 3);

                if($broute != ""){
                    $currel->way->wayseg[0]->nodes[0]->type = "wpt";
                    $currel->way->wayseg[0]->nodes[0]->name = "shapingpoint";                

                    $waysegs = count($currel->way->wayseg);
                    $lwayseg_nodes = count($currel->way->wayseg[$waysegs - 1]->nodes);

                    $currel->way->wayseg[$waysegs - 1]->nodes[$lwayseg_nodes - 1]->type = "wpt";
                    $currel->way->wayseg[$waysegs - 1]->nodes[$lwayseg_nodes - 1]->name = "shapingpoint";
                }
            }
            
            //if there is a center, add it as node
            if (property_exists($ele, 'center'))
                $nodesinput[] = get_center_node($ele, $currel->way, "relcenter");

            //add to ways array
            $relsoutput[] = $currel;
        }
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
    $outputway->gaps = 0;

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
                $outputway->gaps++;
            }
        }
    }

    //print out some stats to log
    //error_log("fixed ".$fixedsegs."/".$numsegs.", found ".$gaps." gaps");
    
    //add debugging info to resulting way
    $outputway->comment = $outputway->comment."\nfixed ".$fixedsegs."/".$numsegs."(".$fix1."|".$fix2."|".$fix3.")".", found ".$outputway->gaps." gaps";

    //$outputway->name = $outputway->name."-fixed";

    return $outputway;
}

function generate_wayseg_point($wayseg, $fraction, &$outputnode)
{
    $curlen = 0;
    $nodecount = 0;

    if($fraction < 0 || $fraction > 1)
        return (-1);

    $targetlen = $wayseg->length * $fraction;

    if($fraction == 0)
        $outputnode = clone(firstnode($wayseg));
    else if($fraction == 1)
        $outputnode = clone(lastnode($wayseg));
    else
        foreach($wayseg->nodes as $node){
            if($nodecount++ > 0){
                $partlen = haversineGreatCircleDistance($oldnode, $node);
                $curlen += $partlen;
            }

            if($curlen > $targetlen){
                $frac = ($curlen - $targetlen) / $partlen;
                $outputnode = interPoint($node, $oldnode, $partlen, $frac);
                break;
            }                

            $oldnode = $node;
        }

    return $nodecount;
}

function insertwaysegpoint($inputwayseg, $fraction)
{
    $outputwayseg = $inputwayseg;

    $nodecount = generate_wayseg_point($inputwayseg, $fraction, $newnode);
    if($nodecount != -1){
        $newnode->type = "wpt";
        $newnode->name = "shapingpoint";

        array_splice( $outputwayseg->nodes, $nodecount - 1, 0, array($newnode));
    }

    return $outputwayseg;
}

function insertwaysegstartpoints($inputway)
{
    $outputway = $inputway;

    for($ws=0; $ws < count($inputway->wayseg); $ws++)
    {
        $outputway->wayseg[$ws]->nodes[0]->type = "wpt";
        $outputway->wayseg[$ws]->nodes[0]->name = "shapingpoint";                        
    }

    //$outputway->withtime = 1;    
    return $outputway;
}

function insertwaysegmidpoints($inputway)
{
    return insertnumwaysegpoints($inputway, 1);
}

function insertnumwaysegpoints($inputway, $numpoints)
{
    $outputway = $inputway;

    if($numpoints > 0)
        foreach($inputway->wayseg as $segment)
            for($i = 0; $i < $numpoints; $i++)
                $segment = insertwaysegpoint($segment, ($i + 1) * (1 / ($numpoints + 1)));

    return $outputway;
}

function insertwaysegsemistartpoints($inputway)
{
    $outputway = $inputway;

    foreach($inputway->wayseg as $segment)
    {
        $frac = 10 / $segment->length;
        if($frac > 1)
            $frac = 0.95;

        $segment = insertwaysegpoint($segment, $frac);
    }

    //$outputway->withtime = 1;
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
if(isset($_GET['zip']))$zipit = $_GET['zip']; else $zipit="";
if(isset($_GET['reroute']))$broute = $_GET['reroute']; else $broute="";
if(isset($_GET['editlink']))$editlink = $_GET['editlink']; else $editlink="";
if(isset($_GET['style']))$style = $_GET['style']; else $style="";
if(isset($_GET['waytopoi']))$waytopoi = $_GET['waytopoi']; else $waytopoi="";
if(isset($_GET['trackspeed']))$trackspeed = $_GET['trackspeed']; else $trackspeed="";

$query = urldecode($query);
error_log($query);

$query = strip_comments($query);

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

$timeout = get_query_timeout($query);

$default_socket_timeout = ini_get('default_socket_timeout');
ini_set('default_socket_timeout', $timeout + 5);

//get the response from overpass api
$response = file_get_contents($url);

ini_set('default_socket_timeout', $default_socket_timeout);

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
        $allrels = array();

        //1. get all nodes of the response    
        getnodes($json, $naming, $allnodes);

        //2. get all ways of the response (consumes nodes from $allnodes)    
        getways($json, $naming, $style, $allnodes, $allways);

        //3. get all relations of the response 
        //(consumes nodes from $allnodes and ways from $allways)    
        getrels($json, $naming, $shpmode, $broute, $allnodes, $allways, $allrels);

        //now construct the gpx output and return it
        outputgpx ($allnodes, $allways, $allrels, $url, $mime, $zipit, $broute, $editlink, $waytopoi, $trackspeed);
    }
}

?>
