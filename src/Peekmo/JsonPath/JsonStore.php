<?php

namespace Peekmo\JsonPath;

/* JSONStore 0.5 - JSON structure as storage
*
* Copyright (c) 2007 Stefan Goessner (goessner.net)
* Licensed under the MIT (MIT-LICENSE.txt) licence.
*
* Modified by Axel Anceau
*/

class JsonStore
{
    private static $emptyArray = array();

    function toString($obj)
    {
        return json_encode($obj);
    }

    function asObj($jsonstr)
    {
        return json_decode($jsonstr);
    }

    function& get(&$obj, $expr)
    {
        if ((($exprs = JsonStore::_normalizedFirst($obj, $expr)) !== false) &&
            (is_array($exprs) || $exprs instanceof Traversable)) {
            $values = array();

            foreach ($exprs as $expr) {
                $o =& $obj;
                $keys = preg_split("/([\"'])?\]\[([\"'])?/", preg_replace(array("/^\\$\[[\"']?/", "/[\"']?\]$/"), "", $expr));

                for ($i=0; $i<count($keys); $i++) {
                    $o =& $o[$keys[$i]];
                }

                $values[] = &$o;
            }

            return $values;
        }

        return self::$emptyArray;
    }

    function set(&$obj, $expr, $value)
    {
        if ($res =& JsonStore::get($obj, $expr)) {
            foreach ($res as &$r) {
                $r = $value;
            }

          return true;
        }

      return false;
    }

    function add(&$obj, $parentexpr, $value, $name="")
    {
      if($parents =& JsonStore::get($obj, $parentexpr)) {
        foreach ($parents as &$parent) {
            $parent = is_array($parent) ? $parent : array();

            if ($name != "") {
                $parent[$name] = $value;
            } else {
                $parent[] = $value;
            }
        }

        return true;
      }

      return false;
    }

    function remove(&$obj, $expr)
    {
        if ((($exprs = JsonStore::_normalizedFirst($obj, $expr)) !== false) &&
             (is_array($exprs) || $exprs instanceof Traversable)) {
            foreach ($exprs as &$expr) {
                $o =& $obj;
                $keys = preg_split("/([\"'])?\]\[([\"'])?/", preg_replace(array("/^\\$\[[\"']?/", "/[\"']?\]$/"), "", $expr));
                for ($i=0; $i<count($keys)-1; $i++) {
                    $o =& $o[$keys[$i]];
                }

                unset($o[$keys[$i]]);
            }

            return true;
        }

        return false;
    }

    function _normalizedFirst($o, $expr)
    {
        if ($expr == "") {
            return false;
        } else if (preg_match("/^\$(\[([0-9*]+|'[-a-zA-Z0-9_ ]+')\])*$/", $expr)) {
            print("normalized: " . $expr);
            return $expr;
        } else {
            $jsonPath = new JsonPath();
            $res = $jsonPath->jsonPath($o, $expr, array("resultType" => "PATH"));
            return $res;
        }
    }
}
?>
