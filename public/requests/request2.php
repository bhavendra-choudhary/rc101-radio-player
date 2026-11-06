<html>

<head>
    <title><?php echo isset($sitename) ? htmlspecialchars($sitename) : 'Radio Station'; ?> - Online Request</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        .table-borderless>tbody>tr>td,
        .table-borderless>tbody>tr>th,
        .table-borderless>tfoot>tr>td,
        .table-borderless>tfoot>tr>th,
        .table-borderless>thead>tr>td,
        .table-borderless>thead>tr>th {
            border-top: 0;
        }

        #seachDIV td:hover {
            cursor: pointer;
            background: #e9efbf;
        }
    </style>
</head>

<body>
    <?php
    $line = array();
    $results_page = '';

    // Copyright 2010-2016 by Jeff Harris - Jeff@HookedOnThe.Net
    // Do not modify PHP code below this line.
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    $version = "version 3.3 - 2016-07-01";
    require_once("config.php");
    require_once("function.php");

    $fp = @fsockopen("$studiohost", $studioport, $errno, $errstr, 10); //open connection
    if ($fp == false)
        header("refresh: 0;");
    @fclose($fp);

    // Check banlist
    if (!empty($banfile)) {
        $banlist = @file($banfile);
        if (is_array($banlist))
            if (in_array($_SERVER["REMOTE_ADDR"] . "\r\n", $banlist)) {
                echo "<h1 align=center>Sorry, you are not allowed to use this request form. </h1>\r\n";
                exit;
            }
    }

    // Get current count from cookie
    if (!isset($_COOKIE["SPS1"])) {
        $day = time() + 86400;
        $value = $requestsperday . ":" . $day;
        $daycount = $requestsperday;
        //setcookie ("SPS1", $value, $day);
        $errormsg = "Cookies must be enabled in your browser in order to use this request feature. \r\n";
    } else {
        list($daycount, $day) = explode(":", $_COOKIE["SPS1"]);
    }
    if (!isset($_COOKIE["SPS2"])) {
        $hour = time() + 3600;
        $value = $requestsperhour . ":" . $hour;
        $hourcount = $requestsperhour;
        //setcookie ("SPS2", $value, $hour);
    } else {
        list($hourcount, $hour) = explode(":", $_COOKIE["SPS2"]);
    }

    $script = $_SERVER["SCRIPT_NAME"];
    if (!empty($libdir)) $path = $libdir . "/";

    $func = $_REQUEST["func"];

    if ($func == "Send Your Request") { // Insert request

        // Validate request
        if (empty($_POST["rq"])) {
            $errormsg .= "No request was selected. Please try again. \r\n";
            $func = "Search";
        } else if (($namefield == 2) && empty($_POST["name"])) {
            $errormsg .= "Your name is required. Please try again. \r\n";
            $func = "Search";
        } else if (($locationfield == 2) && empty($_POST["location"])) {
            $errormsg .= "Your location is required. Please try again. \r\n";
            $func = "Search";
        } else if (($daycount > 0) && ($hourcount > 0)) {

            // $rq = rawurldecode ($_POST["rq"]);
            // if (get_magic_quotes_gpc ()) 
            // $rq = stripslashes ($rq);

            // $name = rawurldecode ($_POST["name"]);
            // if (get_magic_quotes_gpc ()) 
            // $name = stripslashes ($name);

            // $location = rawurldecode ($_POST["location"]);
            // if (get_magic_quotes_gpc ()) 
            // $location = stripslashes ($location);

            if ($expire > 0) $exp = time() + ($expire * 86400);
            else $exp = 0;
            //setcookie ("SPS3", "$name|$location", $exp);

            // Insert request
            $command = "Insert Request=" . $rq . "|" . $_SERVER["REMOTE_ADDR"];
            if ($namefield || $locationfield)
                $command .= "|" . $name . "|" . $location;
            $command .= "\r\n";
            $fp = @fsockopen("$studiohost", $studioport, $errno, $errstr, 10); //open connection
            if ($fp !== false) {
                fwrite($fp, $command);
                $errormsg = fgets($fp);
                fclose($fp);
                if (stripos($errormsg, "has been submitted") !== false) {
                    $daycount -= 1;
                    //setcookie ("SPS1", "$daycount:$day", $day);
                    $hourcount -= 1;
                    //setcookie ("SPS2", "$hourcount:$hour", $hour);
                }
                $alt = "Thank you, We have received your request. It will be played within the hour.";
            } else {
                $errormsg .= "$errno: $errstr\r\n";
            }
        } else {  // Request limit reached
            $errormsg = "Sorry, you've reached your request limit. Please try again ";
            if ($daycount == 0)
                $errormsg .= timediff($day, 1, 2);
            else
                $errormsg .= timediff($hour, 1, 2);
            $errormsg .= ".\r\n";
        }
    }

    if ($func == "Search") { // Lookup request
        if (isset($_COOKIE["SPS3"])) {
            list($name, $location) = explode("|", $_COOKIE["SPS3"]);
        }

        if (isset($_REQUEST["searchtext"])) {
            $searchtext = rawurldecode($_REQUEST["searchtext"]);
            if (get_magic_quotes_gpc())
                $searchtext = stripslashes($searchtext);

            // Send search command
            $command = "Search=*" . $searchtext . "*\r\n";

            if (strpos($searchtext, "*|") !== false) {
                $command = "Search=" . $searchtext . "*\r\n";
                if ($buildlib != 0) {
                    $f = $path . str_replace("*|", "", $searchtext) . ".sdb";
                    $line = @file($f, FILE_IGNORE_NEW_LINES);
                }
            }
            if (!is_array($line)) {
                $fp = @fsockopen("$studiohost", $studioport, $errno, $errstr, 10); //open connection
                if ($fp !== false) {
                    fwrite($fp, $command);
                    $buffer = trim(fgets($fp));
                    while (!empty($buffer) && ($buffer != "Not Found")) {
                        $line[] = $buffer;
                        $buffer = trim(fgets($fp));
                    }
                    fclose($fp);
                } else {
                    $errormsg .= "$errno: $errstr\r\n";
                }
            }

            if (is_array($line)) {
                $results_page =
                    "<h3><table class='table'>\r\n" .
                    "<tr>\r\n" .
                    //							"<th style='text-align: center'>Select</th>\r\n".
                    //							"<th>Title-Artist</th>\r\n".
                    //							"<th>Artist</th>\r\n".
                    "</tr>\r\n";

                for ($i = 0; $i < count($line); $i++) {
                    if (empty($line[$i])) break;
                    list($artist, $title, $filename) = explode("|", $line[$i]);
                    $results_page .= "<tr><td> <input type=\"radio\" name=\"rq\" value=\"$filename\"> </td><td><b> $title</b>-<i>$artist</i></td><!--<td></td>--></tr>\r\n";
                }
            }
            $body .= '<div class="container"><div class="row"><div class="col-sm-12">';
            $body .= "<h2><P>Searching for: " . str_replace("*|", "", $searchtext) . "</P></h2>\r\n";

            if (!empty($results_page)) {
                $results_page .= "</table></h3>\r\n";

                $body .= "<h2><P>Select the song you want. </P></h2>\r\n";
                $body .= "<FORM METHOD=\"post\" action=\"" . $script . "\"><P>\r\n";
                $body .= $results_page;

                if ($namefield || $locationfield) {
                    $body .= "</P><P align='center'>";
                    $body .= "<h2><P>Submit Your Request </P></h2>\r\n";
                    $body .= "<table class='table table-borderless'>\r\n";
                    if ($namefield) {
                        $body .= "<tr><td align=right>";
                        if ($namefield == 2)
                            $body .= "*";
                        $body .= "Your Name: </td><td align=left><input type=text name=name maxlength=30 class='form-control' value=\"$name\"></td></tr>\r\n";
                    }
                    if ($locationfield) {
                        $body .= "<tr><td align=right>";
                        if ($locationfield == 2)
                            $body .= "*";
                        $body .= "Your Location: </td><td align=left><input type=text name=location class='form-control' maxlength=30 value=\"$location\"></td></tr>\r\n";
                    }
                    $body .= "</table>\r\n";
                }

                $body .= "</P><P align='center'>";
                $body .= "<INPUT TYPE=hidden NAME=searchtext VALUE='$searchtext'>\r\n";
                $body .= "<INPUT TYPE=hidden NAME=func VALUE='Send Your Request'>\r\n";
                $body .= "<INPUT TYPE=submit NAME=submit VALUE='Submit Your Request' class='btn btn-primary'>\r\n";
                $body .= "</P></FORM>\r\n";
                $body .= '</div></div></div>';
            } else { // No matches found
                $errormsg = "Sorry, no matches were found. \r\n";
            }
        }
    }
    ?>

    <!-- Copyright 2010 by Jeff Harris - Jeff@HookedOnThe.Net -->
    <!-- <?php echo $version; ?> -->
    <HTML>

    <HEAD>
        <TITLE><?php echo $sitename; ?> Requests</TITLE>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <?php
        if (!empty($css))
            echo "<LINK TYPE='text/css' REL='stylesheet' HREF='" . $css . "'>\r\n";
        ?>
    </HEAD>

    <BODY>
        <?php if (file_exists($header)) include($header); ?>

        <P style="padding:10px; text-align:center;">
            <?php //echo $errormsg; 
            ?>
            <?php
            if ($alt != "") echo '
<div  style="margin-left: auto; margin-right: auto;width: max-content; max-width: 92%;border:1px solid green;padding:10px;">
<table>
<tr><td>
<img src="check.jpg" style="width: 50px; margin-right: 10px;" />
</td><td>
' . $alt . '</td></tr>
</table></div>';
            ?>
        </P>

        <?php echo $body; ?>



        <?php if (($daycount > 0) && ($hourcount > 0)) { ?>
            <h2>Make A Request</h2>
            <!--<div class="container"><div class="row"><div class="col-sm-12">
<P>
    requests remaining: <?php echo $hourcount; ?>  for this hour
<?php
            if ($daycount < $hourcount) echo "but only ";
            else echo " ,";
            echo $daycount . " for today. ";
?>
</P>
</div></div></div>-->
            <?php include_once("reqform.php"); ?>

            <?php @include_once("reqline.php"); ?>

        <?php } else { ?>
            <P>
                Due to time restrictions, licensing rights or if your request has played recently, you may not hear your request right away.

                <?php
                // 
                // if ($daycount == 0) 
                // echo timediff ($day, 1, 2).".";
                // else
                //echo timediff ($hour, 1, 2).".";
                ?>
                Thank you for listening!
            </P>
        <?php }
        ?>
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <p>
                    <h3><?php echo listrequests($studiohost, $studioport); ?></h3>

                    </p>
                </div>
            </div>
        </div>
        <!--<P align=center><BR><BR>-->
        <!--<A HREF="--><?php //echo $home 
                        ?><!--">Home</A>-->
        <!--</P>-->
        <?php if (file_exists($footer)) include($footer); ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
                $("#searchtext").keyup(function() {
                    var searchtext = $(this).val();
                    console.log(searchtext);
                    if (searchtext != "") {
                        $.ajax({
                            url: './pre_load.php',
                            data: {
                                searchtext: searchtext
                            },
                            success: function(data) {
                                console.log(data);
                                //var res=JSON.parse(data);
                                if ($("#searchtext").val() != "") {
                                    $("#seachDIV").html(data);
                                }
                            },
                            type: 'POST'
                        });
                    } else {
                        $("#seachDIV").html("");
                    }
                });
                $("#searchtext").blur(function() {
                    //$("#seachDIV").html("");
                });
                $("body").on("click", ":not(#seachDIV)", function() {
                    $("#seachDIV").html("");
                });
                $("#seachDIV").on("click", "td", function() {
                    if ($(this).attr("data") != "no") {
                        $("#searchtext").val($(this).attr("data"));
                        $("#searchkey").val($(this).attr("file"));
                        $("#searchform").fadeIn();
                    } else {
                        $("#searchform").hide();
                    }
                    //$("#searchtext").val($(this).text());
                });
            });
        </script>
    </BODY>

    </HTML>