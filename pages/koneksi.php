<?php
    // $con = mysqli_connect("localhost","sedt2135_sedayafurn","@Wsncrk06!","sedt2135_sedaya");
    $con = mysqli_connect("localhost","root","root","sedaya");
    //check connection
    if (mysqli_connect_error()){
        echo "failed to connect to MySQL : " . mysqli_connect_error();
        exit();
    }


?>
