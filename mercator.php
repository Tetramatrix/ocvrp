<?php
/* * *************************************************************
 * Copyright notice
 *
 * (c) 2014 Chi Hoang (info@phpdevpad.de)
 *  All rights reserved
 *
 * **************************************************************/
 
class visualize
{
   var $path;
   var $pObj;
   
   function visualize($path,$pObj)
   {
      $this->path=$path;
      $this->pObj=$pObj;
   }
   
   function erropen()
   {
      print "Cannot open file";
      exit;
   }
   
   function errwrite()
   {
      print "Cannot write file";
      exit;
   }
   
   function genimage()
   {
         // Generate the image variables
      $im = imagecreate($this->pObj->mapWidth,$this->pObj->mapHeight);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $black = imagecolorallocate($im,0x00,0x00,0x00);
      $gray_lite = imagecolorallocate ($im,0xee,0xee,0xee);
      $gray_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
      $firebrick = imagecolorallocate ($im,0xb2,0x22,0x22);
      $blue = imagecolorallocate ($im,0x00,0x00,0xff);
      $darkorange = imagecolorallocate ($im,0xff,0x8c,0x00);
      $red = imagecolorallocate ($im,0xff,0x00,0x00);
      
      // Fill in the background of the image
      imagefilledrectangle($im, 0, 0, $this->pObj->mapWidth, $this->pObj->mapHeight, $white);
      foreach ($this->pObj->proj as $key => $arr)
      {
          list($x,$y)=$arr;
          imagefilledellipse($im, $x, $y, 2, 2, $red);
      }
      
      ob_start();
      imagepng($im);
      $imagevariable = ob_get_contents();
      ob_end_clean();

         // write to file
      $filename = $this->path."proj_". rand(0,1000).".png";
      $fp = fopen($filename, "w");
      fwrite($fp, $imagevariable);
      if(!$fp)
      {
         $this->errwrite();   
      }
      fclose($fp);
   }
}

class mercator {

    var $mapWidth;
    var $mapHeight;
    var $mapLonLeft;
    var $mapLatBottom;
    var $mapLonRight;
    var $mapLatTop;
    var $set;
    var $proj;
    
    function __construct ($mapWidth=2000,$mapHeight=2000) 
    {
        $this->mapWidth    = $mapWidth; 
        $this->mapHeight   = $mapHeight; 
        $this->mapLonLeft  = 1000; 
        $this->mapLatBottom= 1000; 
        $this->mapLonRight =    0; 
        $this->mapLatTop   =    0; 
        $this->set=array(); 
        $this->proj=array();
    }
  
    function projection($geocoord) 
    {
        foreach ($geocoord as $key => $arr) 
        { 
            list($lat,$lon) = explode(",",$arr); 
            $this->mapLonLeft = min($this->mapLonLeft,$lon); 
            $this->mapLonRight = max($this->mapLonRight,$lon); 
            $this->mapLatBottom = min( $this->mapLatBottom,$lat); 
            $this->mapLatTop = max( $this->mapLatTop,$lat); 
            $this->set[]=array($lon,$lat); 
        } 

        $mapLonDelta =  $this->mapLonRight - $this->mapLonLeft; 
        $mapLatDelta =  $this->mapLatTop - $this->mapLatBottom; 

        $mapLatTopY= $this->mapLatTop*(M_PI/180); 
        $worldMapWidth=(($this->mapWidth/$mapLonDelta)*360)/(2*M_PI); 
        $LatBottomSin=min(max(sin($this->mapLatBottom*(M_PI/180)),-0.9999),0.9999); 
        $mapOffsetY=$worldMapWidth/2 * log((1+$LatBottomSin)/(1-$LatBottomSin)); 
        $LatTopSin=min(max(sin($this->mapLatTop*(M_PI/180)),-0.9999),0.9999); 
        $mapOffsetTopY=$worldMapWidth/2 * log((1+$LatTopSin)/(1-$LatTopSin)); 
        $mapHeightD=$mapOffsetTopY-$mapOffsetY; 
        $mapRatioH=$this->mapHeight/$mapHeightD; 
        $newWidth=$this->mapWidth*($mapHeightD/$this->mapHeight); 
        $mapRatioW=$this->mapWidth/$newWidth; 

        foreach ($this->set as $key => $arr) 
        { 
            list($lon,$lat) = $arr; 
            $tx = ($lon - $this->mapLonLeft) * ($newWidth/$mapLonDelta)*$mapRatioW; 
            $f = sin($lat*M_PI/180); 
            $ty = ($mapHeightD-(($worldMapWidth/2 * log((1+$f)/(1-$f)))-$mapOffsetY)); 
            $this->proj[]=array($tx,$ty);
        }    
        $this->mapHeight=$mapHeightD;
        return $this->proj;
    }  
}
?>