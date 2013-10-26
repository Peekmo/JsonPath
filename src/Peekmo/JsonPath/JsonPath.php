<?php

namespace Peekmo\JsonPath;


/* JSONPath 0.8.3 - XPath for JSON
*
* Copyright (c) 2007 Stefan Goessner (goessner.net)
* Licensed under the MIT (MIT-LICENSE.txt) licence.
*
* Modified by Axel Anceau
*/


class JsonPath 
{
    private $obj = null;
    private $resultType = "Value";
    private $result = array();
    private $subx = array();

    public function jsonPath($obj, $expr, $args=null) 
    {
        if (is_object($obj)) {
            throw new \Exception('You sent an object, not an array.');
        }

        $this->resultType = ($args ? $args['resultType'] : "VALUE");
        $x = $this->normalize($expr);

        $this->obj = $obj;
        if ($expr && $obj && ($this->resultType == "VALUE" || $this->resultType == "PATH")) {
            $this->trace(preg_replace("/^\\$;/", "", $x), $obj, "$");
            if (count($this->result))
                return $this->result;

            return false;
        }
    }

    // normalize path expression
    private function normalize($x) 
    {
        $x = preg_replace_callback(array("/[\['](\??\(.*?\))[\]']/", "/\['(.*?)'\]/"), array(&$this, "_callback_01"), $x);
        $x = preg_replace(array("/'?\.'?|\['?/", "/;;;|;;/", "/;$|'?\]|'$/"),
            array(";", ";..;", ""),
            $x);
        $x = preg_replace_callback("/#([0-9]+)/", array(&$this, "_callback_02"), $x);
        $this->result = array();  // result array was temporarily used as a buffer ..
        return $x;
    }

    private function _callback_01($m) 
    { 
        return "[#".(array_push($this->result, $m[1])-1)."]"; 
    }

    private function _callback_02($m) 
    { 
        return $this->result[$m[1]]; 
    }

    private function asPath($path) 
    {
        $x = explode(";", $path);
        $p = "$";
        for ($i=1,$n=count($x); $i<$n; $i++)
            $p .= preg_match("/^[0-9*]+$/", $x[$i]) ? ("[".$x[$i]."]") : ("['".$x[$i]."']");
        return $p;
    }

    private function store($p, $v) 
    {
        if ($p) array_push($this->result, ($this->resultType == "PATH" ? $this->asPath($p) : $v));
        return !!$p;
    }

    private function trace($expr, $val, $path) 
    {
        if ($expr !== "") {
            $x = explode(";", $expr);
            $loc = array_shift($x);
            $x = implode(";", $x);

            if (is_array($val) && array_key_exists($loc, $val))
                $this->trace($x, $val[$loc], $path.";".$loc);
            else if ($loc == "*")
                $this->walk($loc, $x, $val, $path, array(&$this, "_callback_03"));
            else if ($loc === "..") {
                $this->trace($x, $val, $path);
                $this->walk($loc, $x, $val, $path, array(&$this, "_callback_04"));
            }
            else if (preg_match("/^\(.*?\)$/", $loc)) // [(expr)]
            $this->trace($this->evalx($loc, $val, substr($path,strrpos($path,";")+1)).";".$x, $val, $path);
            else if (preg_match("/^\?\(.*?\)$/", $loc))  // [?(expr)]
            $this->walk($loc, $x, $val, $path, array(&$this, "_callback_05"));
            else if (preg_match("/^(-?[0-9]*):(-?[0-9]*):?(-?[0-9]*)$/", $loc)) // [start:end:step]  phyton slice syntax
            $this->slice($loc, $x, $val, $path);
            else if (preg_match("/,/", $loc)) // [name1,name2,...]
            for ($s=preg_split("/'?,'?/", $loc),$i=0,$n=count($s); $i<$n; $i++)
                $this->trace($s[$i].";".$x, $val, $path);
            }
            else
                $this->store($path, $val);
    }

    private function _callback_03($m,$l,$x,$v,$p) 
    { 
        $this->trace($m.";".$x,$v,$p); 
    }


    private function _callback_04($m,$l,$x,$v,$p) 
    { 
        if (is_array($v[$m])) {
            $this->trace("..;".$x,$v[$m],$p.";".$m); 
        }
    }


    private function _callback_05($m,$l,$x,$v,$p) 
    { 
    //print_r(array('m' => $m, 'l' => $l, 'x' => $x, 'v' => $v, 'p' => $p)); 
        if ($this->evalx(preg_replace("/^\?\((.*?)\)$/","$1",$l),$v[$m])) {
            $this->trace($m.";".$x,$v,$p); 
        }
    }

    private function walk($loc, $expr, $val, $path, $f) 
    {
        foreach($val as $m => $v) {
            call_user_func($f, $m, $loc, $expr, $val, $path);
        }
    }

    private function slice($loc, $expr, $v, $path) 
    {
        $s = explode(":", preg_replace("/^(-?[0-9]*):(-?[0-9]*):?(-?[0-9]*)$/", "$1:$2:$3", $loc));
        $len=count($v);
        $start=(int)$s[0]?$s[0]:0; 
        $end=(int)$s[1]?$s[1]:$len; 
        $step=(int)$s[2]?$s[2]:1;
        $start = ($start < 0) ? max(0,$start+$len) : min($len,$start);
        $end   = ($end < 0)   ? max(0,$end+$len)   : min($len,$end);
        for ($i=$start; $i<$end; $i+=$step) {
            $this->trace($i.";".$expr, $v, $path);
        }
    }

    /**
    * @param string $x filter
    * @param array  $v node
    * */
    private function evalx($x, $v, $vname) 
    {
        $name = "";
        $o = $this->toObject($v);

        $expr = preg_replace(array("/\\$/","/@/"), array("\$this->obj","\$o"), $x);
        $expr = preg_replace("#\.#", "->", $expr);
        $res = eval("\$name = $expr;");

        if ($res === FALSE) {
            print("(jsonPath) SyntaxError: " . $expr);
        } else {
            return $name;
        }
    }

    private function toObject($array)
    {
        $o = (object)'';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->toObject($value);
            }

            $o->$key = $value;
        }

        return $o;
    }
}
?>