<?php
require_once ("config.php");
require_once ("function.php");
     //$links = getartistlinks ($howmany=3);

     //echo $links[0];
     //echo $links[1];
     //echo $links[2];
include ("reqform.php");
echo listrequests ($studiohost, $studioport);
?>