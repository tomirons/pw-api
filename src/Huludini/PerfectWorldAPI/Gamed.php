<?php

namespace Huludini\PerfectWorldAPI;

/**
 * Class Gamed
 *
 * @package huludini/pw-api
 */
class Gamed
{
    public $ip;
    
    public $version;
    
    public $cycle = false;

    public function __construct()
    {
        $this->cycle = false;
    }

    public function deleteHeader($data)
    {
        $length = 0;
        $this->unpackCuint($data, $length);
        $this->unpackCuint($data, $length);
        $length += 8;
        $data = substr($data, $length);

        return $data;
    }

    public function createHeader($opcode, $data)
    {
        return $this->cuint($opcode).$this->cuint(strlen($data)).$data;
    }

    public function packString($data)
    {
        $data = iconv("UTF-8", "UTF-16LE", $data);
        return $this->cuint(strlen($data)).$data;
    }
    public function packLongOctet($data)
    {
        return  pack("n",strlen($data)+32768).$data;
    }

    public function packOctet($data)
    {
        $data = pack("H*", (string)$data);
        return $this->cuint(strlen($data)).$data;
    }

    public function packInt($data)
    {
        return pack("N", $data);
    }

    public function packByte($data)
    {
        return pack("C", $data);
    }

    public function packFloat($data)
    {
        return strrev(pack("f", $data));
    }

    public function packShort($data)
    {
        return pack("n", $data);
    }

    public function packLong($data)
    {
        $left = 0xffffffff00000000;
        $right = 0x00000000ffffffff;
        $l = ($data & $left) >> 32;
        $r = $data & $right;
        return pack('NN', $l, $r);
    }

    public function hex2octet($tmp)
    {
        $t = 8-strlen($tmp);
        for($i = 0 ; $i < $t; $i++){
            $tmp = '0' . $tmp;
        }
        return $tmp;
    }

    public function reverseOctet($str)
    {
        $octet = '';
        $length = strlen($str)/2;
        for($i = 0; $i < $length; $i++){
            $tmp = substr($str, -2);
            $octet .= $tmp;
            $str = substr($str, 0, -2);
        }
        return $octet;
    }

    public function hex2int($value)
    {
        $value = str_split($value, 2);
        $value = $value[3] . $value[2] . $value[1] . $value[0];
        $value = hexdec($value);

        return $value;
    }

    public function getTime($str)
    {
        return hexdec($str);
    }

    public function getIp($str)
    {
        return long2ip(hexdec($str));
    }

    public function putIp($str)
    {
        $ip = ip2long($str);
        $ip = dechex($ip);
        $ip = hexdec($this->reverseOctet($ip));

        return $ip;
    }

    public function cuint($data)
    {
        if($data < 64)
            return strrev(pack("C", $data));
        else if($data < 16384)
            return strrev(pack("S", ($data | 0x8000)));
        else if($data < 536870912)
            return strrev(pack("I", ($data | 0xC0000000)));
        return strrev(pack("c", -32) . pack("i", $data));
    }

    public function unpackLong($data)
    {
        //$data = pack("H*", $data);
        $set = unpack('N2', $data);
        return $set[1] << 32 | $set[2];
    }

    public function unpackOctet($data, &$tmp)
    {
        $p=0;
        $size = $this->unpackCuint($data,$p);
        $octet= bin2hex(substr($data,$p,$size));
        $tmp=$tmp+$p+$size;
        return $octet;
    }

    public function unpackString($data, &$tmp)
    {
        $size = (hexdec(bin2hex(substr($data, $tmp,1))) >=	128) ? 2 : 1;
        $octetlen = (hexdec(bin2hex(substr($data, $tmp, $size))) >=	128) ? hexdec(bin2hex(substr($data, $tmp, $size)))-32768 : hexdec(bin2hex(substr($data, $tmp, $size)));
        $pp = $tmp;
        $tmp +=	$size + $octetlen;

        return mb_convert_encoding(substr($data, $pp+$size, $octetlen), "UTF-8", "UTF-16LE");
    }

    public function unpackCuint($data, &$p)
    {
        if ( settings( 'server_version', '101' ) != '07' )
        {
            $hex = hexdec(bin2hex(substr($data, $p, 1)));
            $min = 0;
            if($hex < 0x80){
                $size = 1;
            }else if($hex < 0xC0){
                $size = 2;
                $min = 0x8000;
            }else if($hex < 0xE0){
                $size = 4;
                $min = 0xC0000000;
            }else{
                $p++;
                $size = 4;
            }
            $data = (hexdec(bin2hex(substr($data, $p, $size))));
            $unpackCuint = $data-$min;
            $p += $size;
            return $unpackCuint;
        }else{
            $byte = unpack("Carray",substr($data,$p,1));
            if($byte['array'] < 0x80){
                $p++;
            }else if($byte['array'] < 0xC0){
                $byte = unpack("Sarray", strrev(substr($data, $p, 2)));
                $byte['array'] -= 0x8000;
                $p += 2;
            }else if($byte['array'] < 0xE0){
                $byte = unpack("Iarray", strrev(substr($data, $p, 4)));
                $byte['array'] -= 0xC0000000;
                $p += 4;
            }else{
                $prom = strrev(substr($data, $p, 5));
                $byte = unpack("Iarray", strrev($prom));
                $p += 4;
            }
            return $byte['array'];
        }
    }

    public function SendToGamedBD( $data )
    {
        return $this->SendToSocket( $data, config( 'pw-api.ports.gamedbd' ) );
    }

    public function SendToDelivery( $data )
    {
        return $this->SendToSocket( $data, config( 'pw-api.ports.gdeliveryd' ), true );
    }

    public function SendToProvider( $data )
    {
        return $this->SendToSocket( $data, config( 'pw-api.ports.gacd' ) );
    }

    public function SendToSocket( $data, $port, $RecvAfterSend = false, $buf = null )
    {
        if ( @fsockopen( settings( 'server_ip', '127.0.0.1' ), $port, $errCode, $errStr, 1 ) )
        {
            $sock = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
            socket_connect( $sock, settings( 'server_ip', '127.0.0.1' ), $port );


            if ( config( 'pw-api.s_block' ) ) socket_set_block( $sock );
            if ( $RecvAfterSend ) socket_recv( $sock, $tmp, 8192, 0 );
            socket_send( $sock, $data, strlen( $data ), 0 );
            switch( config( 'pw-api.s_readtype' ) )
            {
                case 1:
                    socket_recv( $sock, $buf, config( 'pw-api.maxbuffer' ), 0 );
                    break;
                case 2:
                    $buffer = socket_read( $sock, 1024, PHP_BINARY_READ );
                    while( strlen( $buffer ) == 1024 )
                    {
                        $buf .= $buffer;
                        $buffer = socket_read( $sock, 1024, PHP_BINARY_READ );
                    }
                    $buf .= $buffer;
                    break;
                case 3:
                    $tmp = 0;
                    $buf .= socket_read( $sock, 1024, PHP_BINARY_READ );
                    if ( strlen( $buf ) >= 8 )
                    {
                        $this->unpackCuint( $buf, $tmp );
                        $length = $this->unpackCuint( $buf, $tmp );
                        while( strlen( $buf ) < $length )
                        {
                            $buf .= socket_read( $sock, 1024, PHP_BINARY_READ );
                        }
                    }
                    break;
            }
            if ( config( 'pw-api.s_block' ) ) socket_set_nonblock( $sock );
            socket_close( $sock );
            return $buf;
        }
        else
        {
            //flash()->error( trans( 'pw-api-messages.server_connect_failed' ) );
            return FALSE;
        }
    }

    public function unmarshal(&$rb, $struct)
    {
        $data = array();
        foreach($struct as $key => $val){
            if(is_array($val)){
                if($this->cycle){
                    if($this->cycle > 0){
                        for($i = 0; $i < $this->cycle; $i++){
                            $data[$key][$i] = $this->unmarshal($rb, $val);
                            if(!$data[$key][$i]) return false;
                        }
                    }
                    $this->cycle = false;
                }else{
                    $data[$key] = $this->unmarshal($rb, $val);
                    if(!$data[$key]) return false;
                }
            }else{
                $tmp = 0;
                switch($val){
                    case 'int':
                        $un = unpack("N", substr($rb, 0, 4));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                    case 'int64':
                        $un = unpack("N", substr($rb, 0, 8));
                        $rb = substr($rb, 8);
                        $data[$key] = $un[1];
                        break;
                    case 'long':
                        $data[$key] = $this->unpackLong(substr($rb, 0, 8));
                        $rb = substr($rb, 8);
                        break;
                    case 'lint':
                        //$un = unpack("L", substr($rb,0,4));
                        $un = unpack("V", substr($rb, 0, 4));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                    case 'byte':
                        $un = unpack("C", substr($rb, 0, 1));
                        $rb = substr($rb, 1);
                        $data[$key] = $un[1];
                        break;
                    case 'cuint':
                        $cui = $this->unpackCuint($rb, $tmp);
                        $rb = substr($rb, $tmp);
                        if($cui > 0) $this->cycle = $cui;
                        else $this->cycle = -1;
                        break;
                    case 'octets':
                        $data[$key] = $this->unpackOctet($rb, $tmp);
                        $rb = substr($rb, $tmp);
                        break;
                    case 'name':
                        $data[$key] = $this->unpackString($rb, $tmp);
                        $rb = substr($rb, $tmp);
                        break;
                    case 'short':
                        $un = unpack("n", substr($rb, 0, 2));
                        $rb = substr($rb, 2);
                        $data[$key] = $un[1];
                        break;
                    case 'lshort':
                        $un = unpack("v", substr($rb, 0, 2));
                        $rb = substr($rb, 2);
                        $data[$key] = $un[1];
                        break;
                    case 'float2':
                        $un = unpack("f", substr($rb, 0, 4));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                    case 'float':
                        $un = unpack("f", strrev(substr($rb, 0, 4)));
                        $rb = substr($rb, 4);
                        $data[$key] = $un[1];
                        break;
                }
                if($val != 'cuint' and is_null($data[$key])) return false;
            }
        }
        return $data;
    }

    public function marshal($pack, $struct)
    {
        $this->cycle = false;
        $data = '';
        foreach($struct as $key => $val){
            if(substr($key, 0, 1) == "@") continue;
            if(is_array($val)){
                if($this->cycle){
                    if($this->cycle > 0){
                        $count = $this->cycle;
                        for($i = 0; $i < $count; $i++){
                            $data .= $this->marshal($pack[$key][$i], $val);
                        }
                    }
                    $this->cycle = false;
                }else{
                    $data .= $this->marshal($pack[$key], $val);
                }
            }else{
                switch($val){
                    case 'int':
                        $data .= $this->packInt((int)$pack[$key]);
                        break;
                    case 'byte':
                        $data .= $this->packByte($pack[$key]);
                        break;
                    case 'cuint':
                        $arrkey = substr($key, 0, -5);
                        $cui = isset($pack[$arrkey]) ? count($pack[$arrkey]) : 0;
                        $this->cycle = ($cui > 0) ? $cui : -1;
                        $data .= $this->cuint($cui);
                        break;
                    case 'octets':
                        if($pack[$key] === array()) $pack[$key] = '';
                        $data .= $this->packOctet($pack[$key]);
                        break;
                    case 'name':
                        if($pack[$key] === array()) $pack[$key] = '';
                        $data .= $this->packString($pack[$key]);
                        break;
                    case 'short':
                        $data .= $this->packShort($pack[$key]);
                        break;
                    case 'float':
                        $data .= $this->packFloat($pack[$key]);
                        break;
                    case 'cat1':
                    case 'cat2':
                    case 'cat4':
                        $data .= $pack[$key];
                        break;
                }
            }
        }

        return $data;
    }

    public function MaxOnlineUserID( $arr )
    {
        $max = $arr[0]['userid'];
        for($i=1;$i<count($arr);$i++){
            if($arr[$i]['userid'] > $max){
                $max = $arr[$i]['userid'];
            }
        }

        return $max+1;
    }

    public function getArrayValue($array = array(), $index = null)
    {
        return $array[$index];
    }
}