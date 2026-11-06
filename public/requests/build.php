<?php

// Copyright 2013 by Jeff Harris - Jeff@HookedOnThe.Net
// Do not modify PHP code below this line. 
$version = "version 1.1 - 2013-02-24";

require_once ("config.php");
require_once ("function.php");
?>
<!-- Copyright 2013 by Jeff Harris - Jeff@HookedOnThe.Net -->
<!-- <?php echo $version; ?> -->
<HTML><HEAD>
<TITLE><?php echo $sitename; ?> Requests</TITLE>
<?php 
if (!empty ($css)) 
echo "<LINK TYPE='text/css' REL='stylesheet' HREF='".$css."'>\r\n";
?>
</HEAD><BODY>

<?php @include ("header.inc.php"); ?>

<h2 align=center>Library Builder</H2>

<?php 
echo "<p>Local library is being rebuilt. This may take some time. <br>\r\n";
$result = buildlibrary ($libdir);
echo $result."</p>\r\n";
?>

<?php @include ("footer.inc.php"); ?>
</body>
</html>