<?php
require_once("../db.php");
$db->debug=0;//remove
$row = $db->GetRow("select * from ams_settings");
$client = $row["companyname"];
$apikey = $row["apikey"]; 
$licence = $row["licence"];

$content = @file_get_contents('http://www.skylar.co.ke/registerapp/data/clients/auth.php?apikey=' . $apikey . '&licence=' . $licence);
//echo 'http://www.skylar.co.ke/registerapp/data/clients/auth.php?apikey=' . $apikey . '&licence=' . $licence;	
$json = json_decode($content, true);

$res_json = json_encode(array(
  'success' => 1,
  'message' => 1
));

echo $res_json;
?>
    