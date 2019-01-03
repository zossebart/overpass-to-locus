<?php

class stylespec {
	public $key;
	public $value;
	public $style;

	public function  __construct($k, $v, $s) {
    $this->key = $k;
    $this->value = $v;
    $this->style = $s;
	}
}



$mtblinestyle["default"] = new stylespec("", "", new linestyle(array(0,127,0), 0.75, 4.0));
$mtblinestyle[] = new stylespec("mtb:scale", "0", new linestyle(array(0,0,255), 0.75, 4.0));
$mtblinestyle[] = new stylespec("mtb:scale", "1", new linestyle(array(127,0,255), 0.75, 4.0)); 
$mtblinestyle[] = new stylespec("mtb:scale", "2", new linestyle(array(255,0,0), 0.75, 4.0));
$mtblinestyle[] = new stylespec("mtb:scale", "3", new linestyle(array(127,0,0), 0.75, 4.0));
$mtblinestyle[] = new stylespec("mtb:scale", "4", new linestyle(array(0,0,0), 0.75, 4.0));
$mtblinestyle[] = new stylespec("mtb:scale", "5", new linestyle(array(127,127,127), 0.75, 4.0));

 ?>