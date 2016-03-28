<?php
$host = '*';
$user= '*';
$pass= '*';
$database= '*';

$db = new mysqli($host,$user,$pass,$database);

if($db->connect_errno){
        die("Error: Failed to connect to Database");
}
?>
