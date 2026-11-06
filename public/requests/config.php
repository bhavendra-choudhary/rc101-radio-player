<?php
// Request Form Connection Settings
$studiohost = getenv('STUDIO_HOST') ?: "76.239.45.61";
$studioport = getenv('STUDIO_PORT') ?: 80;
$logfile = 'connection_log.txt';

$connection = @fsockopen($studiohost, (int)$studioport, $errno, $errstr, 5);
$log = date('Y-m-d H:i:s') . " - Attempting to connect to $studiohost on port $studioport\n";

if ($connection) {
    $log .= "Connection successful.\n";
    fclose($connection);
} else {
    $log .= "Connection failed: $errstr ($errno)\n";
}
@file_put_contents($logfile, $log, FILE_APPEND);

// Request Form Basic Settings
$namefield = 0;                         // Request listener name: 0=no, 1=yes, 2=required
$locationfield = 0;                     // Request location: 0=no, 1=yes, 2=required
$expire = 1;                            // Number of days to remember name and location. 0=just for the session
$requestsperhour = 10;
$requestsperday = 30;

$sitename = getenv('APP_NAME') ?: "Radio Station";
$home = "/";                            // Used to link to your homepage
$css = "style.css";
$header = "header.php";
$footer = "footer.php";
$script = "request.php";
$banfile = "banned.php";

// Confirmation messages for successful request submission
$confirmationMessage = "Your song request has been received. Usually it will play within the next song or two, unless it has been played recently.";
$successTitle = "Thank You!";

// Paths/URLs for images used in the form.
$logo_path    = "/Landscape_Logo.png";  // Main site logo
$good_image   = "/images/good.png";     // Icon for successful requests
$redx_image   = "/images/redx.png";     // Icon for error messages
$song_image   = "/images/rc101songicon.png"; // Icon for song in upcoming requests
$artist_image = "/images/rc101artisticon.png"; // Icon for artist in upcoming requests

$header_shading = "#eee";
$libdir = "library";
$buildlib = 0;
?>