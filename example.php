<?php
/* * *************************************************************
 * Copyright notice
 *
 * (c) 2010-2014 Chi Hoang (info@chihoang.de)
 *  All rights reserved
 *
 * **************************************************************/
   
  require_once("ocvr.php");
 
$clients = array (
               array( "latlng"=>"50.1005233,8.6544487",
                      "zipcode"=>"12341",
                      "load"=>"2"),
               array( "latlng"=>"50.0907496,8.7839489",
                      "zipcode"=>"12342",
                      "load"=>"3"), 
               array( "latlng"=>"50.2002273,8.1004734",
                      "zipcode"=>"12343",
                      "load"=>"4"), 
               array( "latlng"=>"50.0951493,8.4117234",
                      "zipcode"=>"12344",
                      "load"=>"2"), 
               array( "latlng"=>"49.4765982,8.3508367",
                      "zipcode"=>"12345",
                      "load"=>"1"), 
               array( "latlng"=>"48.7827027,9.1828630",
                      "zipcode"=>"12347",
                      "load"=>"8"), 
               array( "latlng"=>"48.7686426,9.1686483",
                      "zipcode"=>"12348",
                      "load"=>"9"),
               array( "latlng"=>"48.7829101,9.2118466", 
                      "zipcode"=>"12349",
                      "load"=>"10"),
               array( "latlng"=>"48.9456327,8.9670738",
                      "zipcode"=>"12350",
                      "load"=>"7"));
                       
    $depot=array(array( "latlng"=>"49.7957240,6.6790269",
                       "zipcode"=>"55555"
                ));
                
    $route=new ocvr;
    echo "Please wait.\r\n<br>";
    $result=$route->main($depot,$clients);
    echo "Calculation time:".round($route->dur)."Sek.\r\n<br>";
    echo "Number of combinations:".round($route->nCr/1000)."K\r\n<br>";
    echo "Peak memory:".round($route->peakmem/1024)."MB\r\n<br>";
    echo "Max. load per truck:".MAXCAPACITY."\r\n<br>";
    echo "Number of trucks:".$result["no"]."\r\n<br>";
    foreach ($result["trucks"] as $key=>$arr) 
    {
         echo "Truck $key:".$arr."Load\r\n<br>" ;
    }
    echo "Totaldistance:".$result["totaldist"]."km\r\n<br>";  
?>