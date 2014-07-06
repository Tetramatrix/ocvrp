<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2010-2014 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
require_once("hilbert.php");
require_once("mercator.php");
         
define("MAXCAPACITY",14);
define("DEPOTZIPCODE","55555");
define("MAXCOMBOS", 5);
define("LIMIT1",220);

class ocvr
{
    var $distmatrix=array();
    var $addr2m=array();
    var $dur;
    
    function bitprint($u) 
    {
        $s = array();
        for ($n=0; $u; $n++, $u >>= 1)
        {
            if ($u&1)
            {
                $s [] = $n;
            }
        }
        return $s;
    }
    
    function bitcount($u)
    {
        for ($n=0; $u; $n++, $u = $u&($u-1));
        return $n;
    }
    
    function comb($c,$n) 
    {
        $s = array();
        for ($u=0; $u<1<<$n; $u++)
        {
            if ($this->bitcount($u) == $c)
            {
                $s [] = $this->bitprint($u);
            }
        }
        return $s;
    }

    function getDist($zipcode1,$zipcode2)
    {
        return($this->distmatrix[$this->addr2m[$zipcode1]][$this->addr2m[$zipcode2]]);
    }
    
    function sortNestedArrayAssoc($a)
    {
        ksort($a);
        foreach ($a as $key => $value)
        {
          if (is_array($value) && $key=="trucks")
          {
            $this->sortNestedArrayAssoc($value);
          }
        }
      }
          
    function fullArrayDiff($arrayA, $arrayB) 
    { 
        sort( $arrayA ); 
        sort( $arrayB ); 
        return $arrayA == $arrayB; 
    }

    /**
    * Calculates the great-circle distance between two points, with
    * the Haversine formula.
    * @param float $latitudeFrom Latitude of start point in [deg decimal]
    * @param float $longitudeFrom Longitude of start point in [deg decimal]
    * @param float $latitudeTo Latitude of target point in [deg decimal]
    * @param float $longitudeTo Longitude of target point in [deg decimal]
    * @param float $earthRadius Mean earth radius in [m]
    * @return float Distance between points in [m] (same as earthRadius)
    */
    function haversineGreatCircleDistance (
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    /**
    * Generate all the combinations of $num elements in the given array
    *
    * @param array  $array   Given array
    * @param int    $num     Number of elements ot chossen
    * @param int    $start   Starter of the iteration
    * @return array          Result array
    *
    */
    function combine($array, $num, $start = 0)
    {
        $level=1;$results=$result=array();
    
        for($i=$start,$end=count($array);$i<$end;$i++)
        {
            if($level < $num)
            {
                $result[] = $array[$i];
                $start++;
                $level++;
                $ldm=0;
                foreach ($result as $arr)
                {
                    $ldm+=$arr["load"];
                }
                if (count($results)<LIMIT1)
                {
                    if ($ldm<MAXCAPACITY && $start<count($array))
                    {
                        $results = array_merge($results, $this->combine($array, $num, $start));
                    }
                    elseif($ldm>MAXCAPACITY)
                    {
                        array_pop($result);    
                        $level--;
                    }
                } elseif($ldm>MAXCAPACITY)
                {
                    array_pop($result);
                    $level--;
                }    
            }
            else
            {
                $result[] = $array[$i];
                $ldm=0;
                foreach ($result as $arr)
                {
                    $ldm+=$arr["load"];
                }
                if ($ldm<=MAXCAPACITY)
                {
                    $results[] = $result;
                }        
                array_pop($result);
            }
        }
        return $results;
    }

    function mathFact( $s ) 
    { 
      $r = (int) $s; 
      if ( $r < 2 ) 
        $r = 1; 
      else 
      { 
        for ( $i = $r-1; $i > 1; $i-- ) 
          $r = $r * $i; 
      } 
      return( $r ); 
    }

    function combinationsByLenth($arr, $len, $clients)
    {
        $combinations = [];
        $select = array_fill(0, $len, 0);
        $selectCount = count($select);
        $arrCount = count($arr);
        $oldnCR = min(700000,pow($arrCount, $selectCount));
        $oldServerMax = max(100000,pow($arrCount, $selectCount)*0.85);
        
        $nCr = $this->mathFact($arrCount)/($this->mathFact($selectCount)*$this->mathFact($arrCount-$selectCount));
        if ( is_nan($nCr) )
        {
            $nCr = 800000;
        }
        $this->nCr += $nCr;
        $possibleCombinations=min(700000,$nCr*10);        
        $serverMax = ceil(max(100000,$nCr*10*0.70));
        
        if ($serverMax>$possibleCombinations)
        {
            $serverMax=$possibleCombinations;
        }
        
        while ($serverMax-- > 0)
        {
            $c=$dupe=0;$inkey=$combination = array();
            foreach ($select as $index)
            {
                $combination[] = $arr[$index];
                for ($i=0,$end=$arr[$index]["no"];$i<$end;$i++)
                {
                    if (!in_array($arr[$index][$i]["zipcode"],$inkey))
                    {
                        $c++;
                        $inkey[]=$arr[$index][$i]["zipcode"];
                    } else
                    {
                        $dupe=1;
                        break;
                    }    
                }
                if($dupe==1) break;
            }
    
            if($dupe==0 && $c==$clients)
            {        
                $sort=array();
                foreach ( $combination as $key => $val )
                {
                    $sort [ $key ] = $val [ "distKM" ];
                }
                array_multisort ( $sort, SORT_ASC, SORT_NUMERIC, $combination);
                
                foreach ($combinations as $key => $val)
                {
                    if ($combination==$val)
                    {
                        $dupe=1;
                        break;
                    }
                }
                if ($dupe==0)
                {
                    $combinations[] = $combination;    
                }    
            }
    
            for ($i=$selectCount-1; $i >= 0; $i--)
            {
                if ($select[$i] !== ($arrCount - 1))
                {
                    $select[$i]++;
                    break;
                } else
                {
                    $select[$i] = 0;
                }
            }
        }
        return $combinations;
    }

    function combinationsByMinMax2($arr, $min, $max, $clients)
    {
        $combinations = [];
        for ($i=$min; $i <= $max; $i++)
        {
            $combinations = array_merge($combinations, $this->combinationsByLenth($arr, $i, $clients));
        }
        return $combinations;
    }
        
    function main($depot,$clients)
    {        
        if( !ini_get('safe_mode') )
        {
            ini_set("max_execution_time","10000");
            ini_set("memory_limit","800M");
        }
        set_time_limit(10000);

        $this->nCr=0;
        $this->begin = microtime(true);

        $t = new hilbert();
        $map = new mercator();
        $temp=array_merge($depot,$clients);        
        
        foreach ($temp as $key => $arr)
        {
             $coordinates[]=$arr["latlng"];
        }
        $proj=$map->projection($coordinates);
        foreach ($temp as $key => $arr)
        {
            list($tx,$ty)=$proj[$key];
            $quadkey=$t->point_to_quadkey( round($tx),round($ty), pow(2, 4),"hilbert_map_1");
            $temp[$key]["quadkey"]=substr($quadkey,5,strlen($quadkey));
            $temp[$key]["sindex"]=$t->point_to_hilbert(round($tx),round($ty), pow(2, 4),"hilbert_map_1");
        }        
                 
        $i=$j=0;
        foreach ($temp as $key => $arr) 
        {
            foreach ($temp as $ikey => $iarr) 
            {
                if ($key!=$ikey) 
                {
                    list($lat1,$lng1)=explode(",",$arr["latlng"]);
                    list($lat2,$lng2)=explode(",",$iarr["latlng"]);
                    $d=floor($this->haversineGreatCircleDistance($lat1,$lng1,$lat2,$lng2)/1000);
                    $this->distmatrix[$i][$j]=$d;
                } else {
                     $this->distmatrix[$i][$j]=0;
                }
                $j++;
            }   
            $this->addr2m[$arr["zipcode"]]=$i;
            $i++;
            $j=0;
        }
        $depot[0]=array_shift($temp);
        $clients=$temp;
        $end=count($clients);
        
        if ($end<15)
        {
            $cluster=1;
        }
        elseif ($end<20) 
        {
            $cluster=2;
        }
        elseif ($end<25) 
        {
            $cluster=3;
        }
        for ($i=0;$i<$end; ++$i)
        {
            $geocode = substr($clients [ $i ] [ 'quadkey' ], 0, $cluster);
            $grp[$geocode][] = $clients [ $i ];
        }
        
        $grp_keys=array_keys($grp);
        $i=0;
        $workarr=array();
        for ($i=0;$end=sizeof($grp),$i<$end;++$i)
        {
            $combinations = array();
            for ($j=1;$end2=count($grp[$grp_keys[$i]]),$j<=$end2; $j++)
            {
                $combination = $this->combine($grp[$grp_keys[$i]], $j, 0);
                $combinations[] = $combination;
            }            
            $aux = call_user_func_array('array_merge', $combinations);
            unset($combinations);
            $workarr=array_merge($workarr,$aux);
        }
        flush();
        
        $sortArrDist=$sortArrLDM = array(); 
        foreach($workarr as $key => $arr)
        {
            $ldm=0;$distArr=array();
            foreach ($arr as $ikey => $iarr)
            {
                $ldm+=$iarr["load"];
                $distArr[]=(int)$iarr["sindex"];
            }
            $workarr[$key]["no"] = count($distArr);
            $sortArrLDM[$key]=$workarr[$key]["max"] = $ldm;
            $workarr[$key]["distArr"] = $distArr;
        } 
        array_multisort($sortArrLDM, SORT_DESC, SORT_NUMERIC, $workarr);
        
        if (count($workarr)>=500)
        {
            array_splice($workarr, 500);
        }
        unset($sortArrLDM);
        
        //16991,11141:18631:54293:000000000000000000132302113233010:516520900
        //54293    Trier    6.6790269    49.7957240    132302113233010    516520900
        foreach ($workarr as $key => $arr)
        {
            if ($arr["no"]>1)
            {        
                arsort($arr["distArr"]);
                $sk=array_keys($arr["distArr"]);
                $tmp=array();
                $path=0;
                
                for($i=0;$end=count($sk),$i<$end;++$i)
                {
                    $tmp[]=$i;
                    if(count($tmp)==2 || $i==$end-1)
                    {
                        $path+=$this->getDist($arr[$sk[$tmp[0]]]["zipcode"],$arr[$sk[$tmp[1]]]["zipcode"]);
                        $null=array_shift($tmp);
                    }    
                }
                if ($depot[0]["sindex"] < $arr[$sk[0]]["sindex"]
                     && $depot[0]["sindex"] < $arr[$sk[$arr["no"]-1]]["sindex"])
                {
                    $path+=$this->getDist(DEPOTZIPCODE,$arr[$sk[$arr["no"]-1]]["zipcode"]);
                }
                elseif ($depot[0]["sindex"] > $arr[$sk[0]]["sindex"])
                {
                    $path+=$this->getDist(DEPOTZIPCODE,$arr[$sk[0]]["zipcode"]);
                }
                else
                {
                    $path+=$this->getDist(DEPOTZIPCODE,$arr[$sk[0]]["zipcode"]);
                }
                
                $workarr[$key]["distKM"]=$path;
            } else
            {
                $workarr[$key]["distKM"]=$this->getDist(DEPOTZIPCODE,$arr[0]["zipcode"]);
            }    
        }
    
        $sortArrDist=$sortArrLDM = array(); 
        foreach($workarr as $key => $arr)
        {
            $sortArrDist[$key] = $arr["distKM"];
            $sortArrLDM[$key] = $arr["max"];    
        } 
        array_multisort($sortArrLDM, SORT_DESC, SORT_NUMERIC, $sortArrDist, SORT_ASC, SORT_NUMERIC, $workarr);
        unset($sortArrLDM);
        unset($sortArrDist);
        
        if (count($workarr)>=500)
        {
            array_splice($workarr, 500);
        }
        
        $c=count($clients);    
        $workarr2 = $this->combinationsByMinMax2($workarr,2, min(MAXCOMBOS,ceil($c*0.7)), $c);
        flush();
        
        $this->peakmem=memory_get_peak_usage();        
        
        $sortArrDist=array(); 
        foreach($workarr2 as $key => $arr)
        {
            $totaldist=0;
            $trucks=array();
            foreach ($arr as $ikey => $iarr)
            {
                if (isset($iarr["distKM"]) && isset($iarr["max"]))
                {
                    $totaldist+=$iarr["distKM"];
                    $trucks[]=$iarr["max"];
                }
            }            
            $sortArrDist[$key]=$workarr2[$key]["totaldist"]=$totaldist;
            $workarr2[$key]["trucks"]=$trucks;
            $workarr2[$key]["no"]=count($trucks);
        } 
        array_multisort($sortArrDist, SORT_ASC, SORT_NUMERIC, $workarr2);
        unset($sortArrDist);
        
        if (count($workarr2)>=500)
        {
            array_splice($workarr2, 500);
        }
        
        $sortArrDist=array();
        foreach($workarr2 as $key => $arr)
        {
            $sortArrDist[$key] = $arr["totaldist"];
        }
        array_multisort($sortArrDist, SORT_ASC, SORT_NUMERIC, $workarr2);
        array_splice($workarr2, 20);
        
        $distsum=$trucksum=0;
        foreach ($workarr2 as $key => $arr)
        {
            $distsum+=$arr["totaldist"];
            $trucksum+=count($arr["trucks"]);
        }
        $distavg=$distsum/count($workarr2);
        $cdist=$distavg*0.08;
        $truckavg=$trucksum/count($workarr2);
        
        $temp=$workarr2;
        foreach ($workarr2 as $key => $arr)
        {
            if ($arr["totaldist"] > $distavg-$cdist 
                        || count($arr["trucks"]) < round($truckavg) )
            {
                unset($workarr2[$key]);    
            }
        }
        if(!count($workarr2)>=1)
        {
            $workarr2=$temp;
            foreach ($workarr2 as $key => $arr)
            {
                if ($arr["totaldist"] > $distavg 
                        || count($arr["trucks"]) < round($truckavg) )
                {
                    unset($workarr2[$key]);    
                }
            }        
        }
        if(!count($workarr2)>=1)
        {
            $workarr2=$temp;
        }
        
        $sortArrDist=array();
        foreach($workarr2 as $key => $arr)
        {
            $sortArrDist[$key] = $arr["totaldist"];
        }
        array_multisort($sortArrDist, SORT_ASC, SORT_NUMERIC, $workarr2);
        $workarr2=array_values($workarr2);

        if (count($workarr2[0]["trucks"])<count($workarr2[1]["trucks"]))
        {
            array_shift($workarr2);    
        }
        
        $this->dur = microtime(true)-$this->begin;
        return $workarr2[0];
    }
}
?>