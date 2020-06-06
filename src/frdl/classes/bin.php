<?php
/**
 * original class bserialize:
 * Copyright (c) 2009, PHPServer
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Cesar Rodas nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY CESAR RODAS ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL CESAR RODAS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
/**
 * Original based on:
 *   bserialization
 *   Author: Cesar Rodas
 *   Source: http://www.phpclasses.org/package/5242-PHP-Serialize-and-unserialize-values-in-binary-format.html
 *
 * H. Modifications of class bin/bserialize:
 *  - using abstract class serializer
 *  - moved error constants to class
 * Copyright Webfan.de, Till Wehowski
 * All rights reserved.
 */
namespace frdl\webfan\Serialize\Binary;
use frdl\webfan\Serialize;




class bin extends \frdl\webfan\Serialize\aSerializer
{

   const VERSION = '6.0.0.0';
   
   const STR_LEN = "[Encoding error] String has an invalid length flag";
   const ARR_LEN = "[Encoding error] Array has an invalid length flag";
   const OBJ_LEN = "[Encoding error] Object has an invalid length flag";
   const UNKNOWN_TYPE = "Don't know how to serialize/unserialize %s";

   const V_NULL = 0x00;
   
   const V_ZERO = 0x01;
   const V_1INT_POS = 0x10;
   const V_1INT_NEG = 0x11;
   const V_2INT_POS = 0x12;
   const V_2INT_NEG = 0x13;
   const V_4INT_POS = 0x14;
   const V_4INT_NEG = 0x15;
   const V_FLOAT_POS = 0x20;
   const V_FLOAT_NEG = 0x21;
   const V_BOOL_TRUE = 0x30;
   const V_BOOL_FALSE = 0x31;
   const V_ARRAY = 0x40;
   const V_OBJECT = 0x50;
   const V_STRING = 0x60;


    function __construct() {
      $this->init_time = time();
    }

    public function unserialize(&$var) {
        $i=0;
        return $this->_unserialize($var,false,$i);
    }

    protected function _unserialize(&$var,$just_first=false,&$start) {

       try{
            $len = strlen($var);
          }catch(Exception $e) {
                                  trigger_error($e->getMessage(). ' in '.__CLASS__.'::'.__METHOD__.' line '.__LINE__,E_USER_WARNING);
								  return;
                               }
        $out = null;
        for($i = &$start; $i < $len; $i++) {
            $type = ord($var[$i++]);
            switch ($type) {
                case self::V_ZERO:
                    $out = 0;
                    break;
                case self::V_1INT_POS:
                case self::V_1INT_NEG:
                    $out = ord($var[$i]);
                    if ($type==self::V_1INT_NEG) $out *= -1;
                    $i++;
                    break;
                case self::V_2INT_POS:
                case self::V_2INT_NEG:
                    $out = $this->__toint(substr($var,$i,2),2);
                    if ($type == self::V_2INT_NEG) $out *= -1;
                    $i += 2;
                    break;
                case self::V_4INT_POS:
                case self::V_4INT_NEG:
                    $out = $this->__toint(substr($var,$i,4),4);
                    if ($type == self::V_4INT_NEG) $out *= -1;
                    $i += 4;
                    break;
                case self::V_FLOAT_POS:
                case self::V_FLOAT_NEG:
                    $out = $this->__tofloat(substr($var,$i,6));
                    if ($type == self::V_FLOAT_NEG) $out *= -1;
                    $i += 6;
                    break;
                case self::V_BOOL_TRUE:
                    $out = true;
                    break;
                case self::V_BOOL_FALSE:
                    $out = false;
                    break;
                case self::V_STRING:
                    $xlen = $this->_unserialize($var,true,$i);
                    if (!is_numeric($xlen)) {
                        trigger_error(self::STR_LEN . ' '.__CLASS__.'::'.__METHOD__.' line '.__LINE__);
                        return;
                    }
                    $out = substr($var,$i,$xlen);
                    $i += $xlen;
                    break;
                case self::V_ARRAY:
                    $xlen = $this->_unserialize($var,true,$i);
                    if (!is_numeric($xlen)) {
                        trigger_error(self::ARR_LEN. ' '.__CLASS__.'::'.__METHOD__.' line '.__LINE__);
                        return;
                    }
                    $out = array();
                    $tmp = substr($var,$i,$xlen);
                    $itmp = 0;
                    while ($itmp < $xlen) {
                        $key    = $this->_unserialize($tmp,true,$itmp);
                        $value  = $this->_unserialize($tmp,true,$itmp);
                        $out[$key] = $value;
                    }
                    $i += $xlen;
                    break;
                case self::V_OBJECT:
                    $class_name = $this->_unserialize($var,true,$i);
                    $xlen = $this->_unserialize($var,true,$i);
                    if (!is_numeric($xlen)) {
                        trigger_error(self::OBJ_LEN. ' '.__CLASS__.'::'.__METHOD__.' line '.__LINE__);
                        return;
                    }
                    /**/
                    $class_name = class_exists($class_name) ? $class_name : stdClass;
                    $out = new $class_name;
                    /**/
                    $tmp = substr($var,$i,$xlen);
                    $itmp = 0;
                    while ($itmp < $xlen) {
                        $key    = $this->_unserialize($tmp,true,$itmp);
                        $value  = $this->_unserialize($tmp,true,$itmp);
                        $out->$key = $value;
                    }
                    $i += $xlen;

                    break;
					
				case self::V_NULL:	
                default:
                    //trigger_error(self::UNKNOWN_TYPE. ' '.__CLASS__.'::'.__METHOD__.' line '.__LINE__,E_USER_WARNING);
                   $out = null;
            }
            if (!is_null($out)) {
                break;
            }
        }
        return $out;
    }

   public function serialize($var) {
        $str = "";
        if (is_integer($var) && $var==0) {
            return chr(self::V_ZERO);
        }
        switch( ($type=gettype($var)) ) {
            case "string":
                $str .= chr(self::V_STRING);
                $str .= $this->serialize((int)strlen($var));
                $str .= $var;
                break;
            case "float":
            case "double":
                $str .= chr($var > 0 ? self::V_FLOAT_POS : self::V_FLOAT_NEG);
                $str .= $this->__fromfloat($var);
                break;
            case "integer":
            case "numeric":
                $t = abs($var);
                if ($t < 255) {
                    $str .= chr($var > 0 ? self::V_1INT_POS : self::V_1INT_NEG);
                    $str .= chr($t);
                } else if ($t < 65536) {
                    $str .= chr($var > 0 ? self::V_2INT_POS : self::V_2INT_NEG);
                    $str .= $this->__fromint($var,2);
                } else {
                    $str .= chr($var > 0 ? self::V_4INT_POS : self::V_4INT_NEG);
                    $str .= $this->__fromint($var);
                }
                break;
            case "boolean":
                $str .= chr($var ? self::V_BOOL_TRUE : self::V_BOOL_FALSE);
                break;
            case "array":
                $str .= chr(self::V_ARRAY);
                $tmp = "";
                foreach($var as $key => $value) {
                    $tmp .= $this->serialize($key);
                    $tmp .= $this->serialize($value);
                }
                $str .= $this->serialize(strlen($tmp));
                $str .= $tmp;
                break;
            case "object":
                $str .= chr(self::V_OBJECT);
                $str .= $this->serialize(get_class($var));
                $tmp = "";
                foreach(get_object_vars($var) as $key => $value) {
                    $tmp .= $this->serialize($key);
                    $tmp .= $this->serialize($value);
                }
                $str .= $this->serialize(strlen($tmp));
                $str .= $tmp;
                break;
			case "null" :
            default:
		  $str .= chr(self::V_NULL);  
             //   trigger_error(self::UNKNOWN_TYPE. ' '.__CLASS__.'::'.__METHOD__.' line '.__LINE__,E_USER_WARNING);
                break;
        }
        return $str;
    }

    protected function __toint($string,$blen=4) {
        $out  = 0;
        $n    = ($blen-1) * 8;
        for($bits=0; $bits < $blen; $bits++) {
            $out |= ord($string[$bits]) << $n;
            $n -= 8;
        }
        return $out;
    }

    protected function __fromint($int,$blen=4) {
        $int = (int)($int < 0) ? (-1*$int) : $int;
        $bytes=str_repeat(" ",$blen);
        $n    = ($blen-1) * 8;
        for($bits=0; $bits < $blen; $bits++) {
            $bytes[$bits] = chr($int  >> $n);
            $int -= intval($bytes[$bits]) << intval($n);
            $n -= 8;
        }
        return $bytes;
    }

    protected function __fromfloat($float) {
        $str  = $this->__fromint($float);
        $str .= $this->__fromint( round(($float-(int)$float)*1000) , 2 );
        return $str;
    }

    protected function __tofloat($string) {
        $float  = $this->__toint(substr($string,0,4));
        $float += $this->__toint(substr($string,4,2),2)/1000;
        return $float;
    }
}
