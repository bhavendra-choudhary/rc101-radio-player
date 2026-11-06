<?php 
$string = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

if ($buildlib > 0) {
$ts = @file_get_contents ($path."TS.sdb");
if ($ts > 0) $elapsed = time() - $ts;
else $elapsed = $buildlib*3600;
if ($elapsed >= ($buildlib*3600)) {
echo "<p>Local library is being rebuilt. This may take some time. <br>\r\n";
flush();
$result = buildlibrary ($libdir);
echo $result."</p>\r\n";
}
}

//echo "<p>List songs by artist names beginning with ";
//echo "<table><tr>\r\n";
//for ($i = 0; $i < strlen ($string)-13; $i++) {
//$l = substr ($string, $i, 1);
//echo "<td><a href='".$script."?func=Search&searchtext=".$l."*|'>".$l."</td>\r\n";
//}
////echo "<td><a href='".$script."?func=Search&searchtext=0*|'>Others</td>\r\n";
//echo "</tr>";
//echo "<tr>\r\n";
//for ($i = strlen ($string)-13; $i < strlen ($string); $i++) {
//    $l = substr($string, $i, 1);
//    echo "<td><a href='" . $script . "?func=Search&searchtext=" . $l . "*|'>" . $l . "</td>\r\n";
//}
//echo "</tr></table></p>";
//echo "<td><a href='".$script."?func=Search&searchtext=0*|'>Others</td>\r\n";

?>