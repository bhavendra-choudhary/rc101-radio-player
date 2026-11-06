<?php
require_once("db_config.php");

// Connect to the database
$con = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Fetch the user by ID (change '2' to the desired user ID dynamically if needed)
$result = $con->query('SELECT * FROM users WHERE id = 2');
$user = $result->fetch_assoc();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<div class="container">
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-8 text-center">
            <form method="post" id="" action="<?php echo $script; ?>">
                <div class="form-group">
                    <label for="name">Start typing title or artist:</label>
                    <input class="form-control" type='text' id="searchtext" name="searchtext" size='50' maxsize='70' placeholder="Start typing Title OR Artist">
                </div>
                <input type="hidden" name="func" value="Search">
                <input type="submit" name="submit" placeholder="Search.." value="Search" class="btn btn-primary" style='width: 150px'>
            </form>
            <div id="seachDIV" class="col-sm-12" style="overflow: auto; max-height: 200px;margin-left: -15px;width: 100%;position: absolute; z-index: 99; background: #fff;"></div>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <hr>
    <!--<div class="col-sm-4" style="padding: 0px; margin-top: 25px;">-->
    <!--    <form method="post" id="searchform" action="<?php echo $script; ?>">-->
    <!--        <input type="hidden" id="searchkey" name="rq" value="C:\Users\Big Kahuna\Desktop\Jammin 92\Throwbacks\2000s\2000\Candy Shop - 50 Cent.mp3">-->
    <!--        <div class="form-group">-->
    <!--            <label for="name">Your Name</label>-->
    <!--            <input type="text" maxlength="30" class="form-control" id="name" placeholder="Name" value="<?php echo $_SESSION->first_name.' '.$user->last_name; ?>">-->
    <!--        </div>-->
    <!--        <div class="form-group">-->
    <!--            <label for="name">Your Location</label>-->
    <!--            <input type="text" maxlength="30" class="form-control" id="location" placeholder="Location" value="<?php if($user->location) echo $user->location; else echo $user->lati_longitude; ?>">-->
    <!--        </div>-->

    <!--        <input type="hidden" name="searchtext" value="Candy Shop">-->
    <!--        <input type="hidden" name="func" value="Send Your Request">-->
    <!--        <input type="submit" name="submit" value="Submit Your Request" class="btn btn-primary">-->
    <!--        </p>-->
    <!--    </form>-->
    <!--</div>-->
</div>