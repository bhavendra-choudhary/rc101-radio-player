<?php
// I made the file for debugging if you want you can delete
$filePath = "G:\\Music\\Jammin 92\\Playlist\\Hold 1\\Temp Adds\\POPSTAR - DJ Khaled f. Drake PO Clean Edit.mp3";

if (file_exists($filePath)) {
    echo "File exists!";
} else {
    echo "File not found.";
}
?>
