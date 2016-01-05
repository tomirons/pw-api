<?php

namespace Huludini\PerfectWorldAPI;

/**
 * Class API
 *
 * @package huludini/pw-api
 */
class API
{
    public $online;

    public $data = [];
    
    public $gamed;

    public function __construct()
    {
        // Set some default values
        $this->gamed = new Gamed();
        $this->online = $this->serverOnline();

        // Check if there is a protocol file for the set game version
        $version = settings( 'server_version', '101' );

        if ( file_exists( __DIR__ . '/../../protocols/pw_v' . $version . '.php' ) )
        {
            require( __DIR__ . '/../../protocols/pw_v' . $version . '.php' );
            if ( isset( $PROTOCOL ) )
            {
                $this->data = $PROTOCOL;
            }
        }
        else
        {
            throw new \Exception( trans( 'pw-api-messages.no_version', ['version' => $version] ) );
        }
    }


    /**
     * Returns the array of role data by structure
     * @params string $role
     * @return array
     */
    public function getRole($role)
    {
        if ( settings( 'server_version' ) == '07' )
        {
            $user['base'] = $this->getRoleBase($role);
            $user['status'] = $this->getRoleStatus($role);
            $user['pocket'] = $this->getRoleInventory($role);
            //$user['pets'] = $this->getRolePetBadge($role);
            $user['equipment'] = $this->getRoleEquipment($role);
            $user['storehouse'] = $this->getRoleStoreHouse($role);
            $user['task'] = $this->getRoleTask($role);
        }
        else
        {
            $pack = pack("N*", -1, $role);
            $pack = $this->gamed->createHeader( $this->data['code']['getRole'], $pack );
            $send = $this->gamed->SendToGamedBD( $pack );
            $data = $this->gamed->deleteHeader( $send );
            $user = $this->gamed->unmarshal( $data, $this->data['role'] );

            if( !is_array( $user ) )
            {
                $user['base'] = $this->getRoleBase($role);
                $user['status'] = $this->getRoleStatus($role);
                $user['pocket'] = $this->getRoleInventory($role);
                //$user['pets'] = $this->getRolePetBadge($role);
                $user['equipment'] = $this->getRoleEquipment($role);
                $user['storehouse'] = $this->getRoleStoreHouse($role);
                $user['task'] = $this->getRoleTask($role);
            }
        }

        return $user;
    }

    public function getRoleBase($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleBase'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['base']);

        return $user;
    }

    public function getRoleStatus($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleStatus'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['status']);

        return $user;
    }

    public function getRoleInventory($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleInventory'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['pocket']);

        return $user;
    }

    public function getRoleEquipment($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleEquipment'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['equipment']);

        return $user;
    }

    public function getRolePetBadge($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader(3088, $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['pocket']['petbadge']);

        return $user;
    }

    public function getRoleStorehouse($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleStoreHouse'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $store = $this->gamed->unmarshal($data, $this->data['role']['storehouse']);

        return $store;
    }

    public function getRoleTask($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader($this->data['code']['getRoleTask'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, $this->data['role']['task']);

        return $user;
    }

    /**
     * Returns the array of role data by structure
     * @params string $role
     * @return array
     */
    public function getJdRole($role)
    {
        /*$pack = pack("N*", -1, $role);
        $pack = $this->gamed->createHeader(config('code.getRole'), $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        $user = $this->gamed->unmarshal($data, config('role'));

        return $user;*/
    }

    /**
     * Returns the array of user roles by structure
     * @params string $user
     * @return array
     */
    public function getRoles( $user )
    {
        $pack = pack( "N*", -1, $user );
        $pack = $this->gamed->createHeader( $this->data['code']['getUserRoles'], $pack );
        $send = $this->gamed->SendToGamedBD( $pack );
        $data = $this->gamed->deleteHeader( $send );
        $roles = $this->gamed->unmarshal( $data, $this->data['user']['roles'] );

        return $roles;
    }

    /**
     * Returns the array of user data by structure
     * @params int $id
     * @return array
     */
    public function getUser($id)
    {
        $pack = pack("N*", -1, $id, 1, 1);
        $data = $this->gamed->cuint($this->data['code']['getUser']).$this->gamed->cuint(strlen($pack)).$pack;
        $send = $this->gamed->SendToGamedBD($data);
        $strlarge = unpack("H", substr($send, 2, 1 ));
        if(substr($strlarge[1], 0, 1) == 8)
        {
            $tmp =	12;
        }
        else
        {
            $tmp = 11;
        }
        $send = substr($send, $tmp);
        $user = $this->gamed->unmarshal($send, $this->data['user']['info']);
        $user['login_ip'] = $this->gamed->getIp($this->gamed->reverseOctet(substr($user['login_record'], 8, 8)));
        $user['login_time'] = $this->gamed->getTime(substr($user['login_record'], 0, 8));

        return $user;
    }

    /**
     * Returns the array of user data by structure
     * @params string $user
     * @return array
     */
    public function getJdUser($id)
    {
        /*$pack = pack("N*", -1, $id);
        $data = $this->gamed->SendToGamedBD($this->gamed->createHeader(config('code.getUser'), $pack));
        $send = $this->gamed->SendToGamedBD($data);
        return $this->gamed->unmarshal($data, config('user.info'));*/
    }

    /**
     * Saves a data of character by structure
     * @params string $role
     * @params array $params
     * @return boolean
     */
    public function putRole($role, $params)
    {
        if(isset($params['equipment']['eqp']['id']))
        {
            $tmp = $params['equipment']['eqp'];
            $params['equipment']['eqp'] = array();
            $params['equipment']['eqp'][] = $tmp;
        }
        if(isset($params['pocket']['inv']['id']))
        {
            $tmp = $params['pocket']['inv'];
            $params['pocket']['inv'] = array();
            $params['pocket']['inv'][] = $tmp;
        }
        if(isset($params['storehouse']['store']['id']))
        {
            $tmp = $params['storehouse']['store'];
            $params['storehouse']['store'] = array();
            $params['storehouse']['store'][] = $tmp;
        }
        if(isset($params['task']['task_inventory']['id']))
        {
            $tmp = $params['task']['task_inventory'];
            $params['task']['task_inventory'] = array();
            $params['task']['task_inventory'][] = $tmp;
        }
        if(isset($params['storehouse']['dress']['id']))
        {
            $tmp = $params['storehouse']['dress'];
            $params['storehouse']['dress'] = array();
            $params['storehouse']['dress'][] = $tmp;
        }
        if(isset($params['storehouse']['material']['id']))
        {
            $tmp = $params['storehouse']['material'];
            $params['storehouse']['material'] = array();
            $params['storehouse']['material'][] = $tmp;
        }
        if ( settings( 'server_version' ) != '07' )
        {
            $pack = pack( "NNC*", -1, $role, 1).$this->gamed->marshal( $params, $this->data['role'] );

            return $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRole'], $pack ) );
        }
        else
        {
            $pack = pack( "NNC*", -1, $role ) . $this->gamed->marshal( $params["base"], $this->data['role']['base'] );
            $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRoleBase'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . $this->gamed->marshal( $params["status"], $this->data['role']['status'] );
            $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRoleStatus'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . $this->gamed->marshal( $params["pocket"], $this->data['role']['pocket'] );
            $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRoleInventory'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . $this->gamed->marshal( $params["equipment"], $this->data['role']['equipment'] );
            $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRoleEquipment'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . $this->gamed->marshal( $params["storehouse"], $this->data['role']['storehouse'] );
            $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRoleStoreHouse'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . $this->gamed->marshal( $params["task"], $this->data['role']['task'] );

            return $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['putRoleTask'], $pack ) );
        }
    }

    /**
     * Saves a data of character by structure
     * @params string $role
     * @params array $params
     * @return boolean
     */
    public function putJdRole($role, $params)
    {
        /*$pack = pack("NNC*", -1, $role, 1).$this->gamed->marshal($params, config('role'));
        $this->gamed->SendToGamedBD($this->gamed->createHeader(config('code.putRole'), $pack));

        return true;*/
    }

    /**
     * Send mail to the game mail
     * @params string $receiver
     * @params string $title
     * @params string $context
     * @params array $item
     * @params string $money
     * @return boolean
     */
    public function sendMail( $receiver, $title, $context, $item = array(), $money )
    {
        if( $item === array() )
        {
            $item = array(
                'id' => 0,
                'pos' => 0,
                'count' => 0,
                'max_count' => 0,
                'data' => '',
                'proctype' => 0,
                'expire_date' => 0,
                'guid1' => 0,
                'guid2' => 0,
                'mask' => 0
            );
        }

        $pack = pack( "NNCN", 344, 1025, 3, $receiver ) . $this->gamed->packString( $title ) . $this->gamed->packString( $context );
        $pack .= $this->gamed->marshal( $item, $this->data['role']['pocket']['inv'] );
        $pack .= pack("N", $money);

        return $this->gamed->SendToDelivery( $this->gamed->createHeader( $this->data['code']['sendMail'], $pack ) );
    }

    /**
     * Send message to the game chat on the special channel
     * @params string $role
     * @params string $msg
     * @params string $chanel
     * @return boolean
     */
    public function WorldChat( $role, $msg, $channel )
    {
        $pack = pack("CCN", $channel, 0, $role) . $this->gamed->packString( $msg ) . $this->gamed->packOctet( '' );

        return $this->gamed->SendToProvider( $this->gamed->createHeader( $this->data['code']['worldChat'], $pack ) );
    }

    /**
     * The ban of Account
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function forbidAcc( $role, $time, $reason )
    {
        $pack = pack( "N*", -1, 0, $role, $time ) . $this->gamed->packString( $reason );

        return $this->gamed->SendToDelivery( $this->gamed->createHeader( $this->data['code']['forbidAcc'], $pack ) );
    }

    /**
     * The ban of character
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function forbidRole( $role, $time, $reason )
    {
        $pack = pack( "N*", -1, 0, $role, $time ) . $this->gamed->packString( $reason );

        return $this->gamed->SendToDelivery( $this->gamed->createHeader( $this->data['code']['forbidRole'], $pack ) );
    }

    /**
     * The ban chat of account
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function muteAcc( $role, $time, $reason )
    {
        $pack = pack( "N*", -1, 0, $role, $time ) . $this->gamed->packString( $reason );

        return $this->gamed->SendToDelivery( $this->gamed->createHeader( $this->data['code']['muteAcc'], $pack ) );
    }

    /**
     * The ban chat of character
     * @params string $role
     * @params miax $time
     * @params string $reason
     * @return boolean
     */
    public function muteRole( $role, $time, $reason )
    {
        $pack = pack( "N*", -1, 0, $role, $time ) . $this->gamed->packString( $reason );

        return $this->gamed->SendToDelivery( $this->gamed->createHeader( $this->data['code']['muteRole'], $pack ) );
    }

    /**
     * Returns the ID of role
     * @params string $rolename
     * @return string
     */
    public function getRoleid($rolename)
    {
        $pack = pack("N", -1).$this->gamed->packString($rolename).pack("C", 1);
        $data = $this->gamed->deleteHeader($this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['getRoleid'], $pack)));
        $var = unpack("l", $data);
        if($var[1] !== -1)
        {
            $var = unpack("N", $data);
        }
        return $var[1];
    }

    /**
     * Renaming a character
     * @params string $role
     * @params string $newname
     * @return boolean
     */
    public function renameRole( $role, $oldname, $newname )
    {
        $pack = pack( "N*", -1, $role ) . $this->gamed->packString( $oldname ) . $this->gamed->packString( $newname );

        return $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['renameRole'], $pack ) );
    }

    /**
     * Returns the array with online roles by structure
     * @return array
     */
    public function getOnlineList()
    {
        $online = [];
        if ( $this->online )
        {
            $id = 0;
            $pack = pack( 'N*', -1, 1, $id ) . $this->gamed->packString( '1' );
            $pack = $this->gamed->createHeader( 352, $pack );
            $send = $this->gamed->SendToDelivery( $pack );
            $data = $this->gamed->deleteHeader($send);
            $data = $this->gamed->unmarshal( $data, $this->data['RoleList'] );

            if ( isset( $data['users'] ) )
            {
                foreach ( $data['users'] as $user )
                {
                    $online[] = $user;
                    //$id = $this->gamed->MaxOnlineUserID( $data['users'] );
                }
            }
        }
        return $online;
    }

    /**
     * Returns the array with friends by structure
     * @param string $role
     * @return array
     */
    public function getRoleFriends( $role )
    {
        $tmp = 0;
        $pack = pack( "N*", -1, $role );
        $data = $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['getRoleFriend'], $pack ) );
        $this->gamed->unpackCuint( $data, $tmp );
        $this->gamed->unpackCuint( $data, $tmp );
        $data = substr( $data, $tmp+5 );

        return $this->gamed->unmarshal( $data, $this->data['FriendList'] );
    }

    public function addFaction($roleid, $name, $fid)
    {
        $pack = pack("N*", -1).$this->gamed->packString($name).pack("NN", $roleid, $fid);
        $pack = $this->gamed->createHeader($this->data['code']['AddFaction'], $pack);
        return $this->gamed->SendToGamedBD($pack);
    }

    public function delFaction($fid)
    {
        $pack = pack("N*", -1, $fid);
        $pack = $this->gamed->createHeader($this->data['code']['DelFaction'], $pack);
        return $this->gamed->SendToGamedBD($pack);
    }

    public function upgradeFaction($roleid, $fid, $level)
    {
        $pack = pack("N*", -1, $fid, $roleid, 0).pack("C", $level);
        $pack = $this->gamed->createHeader($this->data['code']['FactionUpgrade'], $pack);
        return $this->gamed->SendToGamedBD($pack);
    }

    public function getFactionInfo($id)
    {
        $pack = pack("N*", -1, $id);
        $pack = $this->gamed->createHeader($this->data['code']['getFactionInfo'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        return $this->gamed->unmarshal($data, $this->data['FactionInfo']);
    }

    public function getFactionDetail($id)
    {
        $pack = pack("N*", -1, $id);
        $pack = $this->gamed->createHeader($this->data['code']['getFactionDetail'], $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        return $this->gamed->unmarshal($data, $this->data['FactionDetail']);
    }

    public function getFactionFortressDetail($id)
    {
        /*$pack = pack("N*", -1, $id);
        $pack = $this->gamed->createHeader(config('code.GFactionFortressDetail'), $pack);
        $send = $this->gamed->SendToGamedBD($pack);
        $data = $this->gamed->deleteHeader($send);
        return $this->gamed->unmarshal($data, config('FactionFortressDetail'));*/
    }

    public function getTerritories()
    {
        $pack = pack( "N*", -1, 1 );
        $data = $this->gamed->SendToGamedBD( $this->gamed->createHeader( $this->data['code']['getTerritory'], $pack ) );
        $length = 0;
        $this->gamed->unpackCuint( $data, $length );
        $this->gamed->unpackCuint( $data, $length );
        $length += 6;
        $data = substr($data, $length);
        return $this->gamed->unmarshal( $data, $this->data['GTerritoryDetail'] );
    }

    public function getRaw($table, $handler = '', $key = '')
    {
        $pack = pack("N*",-1).$this->gamed->packLongOctet($table).$this->gamed->packOctet($handler).$this->gamed->packOctet($key);
        $data = $this->gamed->deleteHeader($this->gamed->SendToGamedBD($this->gamed->createHeader(3055, $pack)));
        return  $this->gamed->unmarshal($data, $this->data['RawRead']);
    }

    public function parseOctet($octet, $name)
    {
        $data = pack("H*", $octet);
        return $this->gamed->unmarshal($data, $this->data['octet'][$name]);
    }

    public function getUserFaction($id)
    {
        $tmp = 0;
        $pack = pack("N*", -1, 1, $id);
        $data = $this->gamed->SendToGamedBD($this->gamed->createHeader($this->data['code']['getUserFaction'], $pack));
        $this->gamed->unpackCuint($data, $tmp);
        $this->gamed->unpackCuint($data, $tmp);
        $data = substr($data, $tmp+8);
        return $this->gamed->unmarshal($data, $this->data['getUserFaction']);
    }

    public function generateSkill($params = array())
    {
        $skills = substr($params['skills'], 8);
        $id = isset($params['id']) ? dechex($params['id']) : 1;
        $level = isset($params['level']) ? dechex($params['level']) : 1;
        $progress = isset($params['progress']) ? dechex($params['progress']) : 0;
        $skills .= $this->gamed->reverseOctet($this->gamed->hex2octet($id));
        $skills .= $this->gamed->reverseOctet($this->gamed->hex2octet($progress));
        $skills .= $this->gamed->reverseOctet($this->gamed->hex2octet($level));
        $count = dechex(strlen($skills)/24);
        $skills = $this->gamed->reverseOctet($this->gamed->hex2octet($count)).$skills;

        return $skills;
    }

    public function serverOnline()
    {
        return @fsockopen( settings( 'server_ip', '127.0.0.1' ), config( 'pw-api.ports.client' ), $errCode, $errStr, 1 ) ? TRUE : FALSE;
    }

    public function ports()
    {
        $ports = [];
        $port_list = config( 'pw-api.ports' );
        foreach ( $port_list as $name => $port )
        {
            $ports[$name]['port'] = $port;
            $ports[$name]['open'] = @fsockopen( settings( 'server_ip', '127.0.0.1' ), $port, $errCode, $errStr, 1 ) ? TRUE : FALSE;
        }
        return $ports;
    }
}