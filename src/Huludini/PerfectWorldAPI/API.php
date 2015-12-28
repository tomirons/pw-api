<?php

namespace Huludini\PerfectWorldAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class API
 *
 * @package huludini/pw-api
 */
class API
{
    public $online;

    public $data = [];

    public function __construct()
    {
        // Check if there is a protocol file for the set game version
        if ( Schema::hasTable( 'pweb_settings' ) )
        {
            $version = ( DB::table( 'pweb_settings' )->where( 'key', 'server_version' )->exists() ) ? settings( 'server_version' ) : '101';

            if ( file_exists( __DIR__ . '/../../protocols/pw_v' . $version . '.php' ) )
            {
                require( __DIR__ . '/../../protocols/pw_v' . $version . '.php' );
                if ( isset( $PROTOCOL ) )
                {
                    $this->data = $PROTOCOL;
                    $this->online = $this->serverOnline();
                }
            }
            else
            {
                throw new Exception( trans( 'pw-api-messages.no_version', ['version' => $version] ) );
            }
        }
    }


    /**
     * Returns the array of role data by structure
     * @params string $role
     * @return array
     */
    public function getRole($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader( $this->data['code']['getRole'], $pack );
        $send = Gamed::SendToGamedBD( $pack );
        $data = Gamed::deleteHeader( $send );
        $user = Gamed::unmarshal( $data, $this->data['role'] );

        if( !is_array( $user ) )
        {
            $user['base'] = self::getRoleBase($role);
            $user['status'] = self::getRoleStatus($role);
            $user['pocket'] = self::getRoleInventory($role);
            $user['pets'] = self::getRolePetBadge($role);
            $user['equipment'] = self::getRoleEquipment($role);
            $user['storehouse'] = self::getRoleStoreHouse($role);
            $user['task'] = self::getRoleTask($role);
        }

        return $user;
    }

    public function getRoleBase($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader($this->data['code']['getRoleBase'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, $this->data['code']['base']);

        return $user;
    }

    public function getRoleStatus($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader($this->data['code']['getRoleStatus'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, $this->data['role']['status']);

        return $user;
    }

    public function getRoleInventory($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader($this->data['code']['getRoleInventory'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, $this->data['role']['pocket']['inv']);

        return $user;
    }

    public function getRoleEquipment($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader($this->data['code']['getRoleEquipment'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, $this->data['role']['pocket']['equipment']);

        return $user;
    }

    public function getRolePetBadge($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader(3088, $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, $this->data['role']['pocket']['petbadge']);

        return $user;
    }

    public function getRoleStorehouse($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader($this->data['code']['getRoleStoreHouse'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $store = Gamed::unmarshal($data, $this->data['role']['storehouse']);

        return $store;
    }

    public function getRoleTask($role)
    {
        $pack = pack("N*", -1, $role);
        $pack = Gamed::createHeader($this->data['code']['getRoleTask'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, $this->data['role']['task']);

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
        $pack = Gamed::createHeader(config('code.getRole'), $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        $user = Gamed::unmarshal($data, config('role'));

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
        $pack = Gamed::createHeader( $this->data['code']['getUserRoles'], $pack );
        $send = Gamed::SendToGamedBD( $pack );
        $data = Gamed::deleteHeader( $send );
        $roles = Gamed::unmarshal( $data, $this->data['user']['roles'] );

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
        $data = Gamed::cuint($this->data['code']['getUser']).Gamed::cuint(strlen($pack)).$pack;
        $send = Gamed::SendToGamedBD($data);
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
        $user = Gamed::unmarshal($send, $this->data['user']['info']);
        $user['login_ip'] = Gamed::getIp(Gamed::reverseOctet(substr($user['login_record'], 8, 8)));
        $user['login_time'] = Gamed::getTime(substr($user['login_record'], 0, 8));

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
        $data = Gamed::SendToGamedBD(Gamed::createHeader(config('code.getUser'), $pack));
        $send = Gamed::SendToGamedBD($data);
        return Gamed::unmarshal($data, config('user.info'));*/
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
        if ( $this->data['code']['putRole'] != '' )
        {
            $pack = pack( "NNC*", -1, $role, 1).Gamed::marshal( $params, $this->data['role'] );

            return Gamed::SendToGamedBD( Gamed::createHeader( $this->data['code']['putRole'], $pack ) );
        }
        else
        {
            $pack = pack( "NNC*", -1, $role ) . Gamed::marshal( $params["base"], $this->data['role']['base'] );
            Gamed::SendToGamedBD( Gamed::createHeader( $this->data['role']['putRoleBase'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . Gamed::marshal( $params["status"], $this->data['role']['status'] );
            Gamed::SendToGamedBD( Gamed::createHeader( $this->data['role']['putRoleStatus'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . Gamed::marshal( $params["pocket"], $this->data['role']['pocket'] );
            Gamed::SendToGamedBD( Gamed::createHeader( $this->data['role']['putRoleInventory'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . Gamed::marshal( $params["equipment"], $this->data['role']['equipment'] );
            Gamed::SendToGamedBD( Gamed::createHeader( $this->data['role']['putRoleEquipment'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . Gamed::marshal( $params["storehouse"], $this->data['role']['storehouse'] );
            Gamed::SendToGamedBD( Gamed::createHeader( $this->data['role']['putRoleStoreHouse'], $pack ) );
            $pack = pack( "NNC*", -1, $role ) . Gamed::marshal( $params["task"], $this->data['role']['task'] );

            return Gamed::SendToGamedBD( Gamed::createHeader( $this->data['role']['putRoleTask'], $pack ) );
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
        /*$pack = pack("NNC*", -1, $role, 1).Gamed::marshal($params, config('role'));
        Gamed::SendToGamedBD(Gamed::createHeader(config('code.putRole'), $pack));

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

        $pack = pack( "NNCN", 344, 1025, 3, $receiver ) . Gamed::packString( $title ) . Gamed::packString( $context );
        $pack .= Gamed::marshal( $item, $this->data['role']['pocket']['inv'] );
        $pack .= pack("N", $money);

        return Gamed::SendToDelivery( Gamed::createHeader( $this->data['code']['sendMail'], $pack ) );
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
        $pack = pack("CCN", $channel, 0, $role) . Gamed::packString( $msg ) . Gamed::packOctet( '' );

        return Gamed::SendToProvider( Gamed::createHeader( $this->data['code']['worldChat'], $pack ) );
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
        $pack = pack( "N*", -1, 0, $role, $time ) . Gamed::packString( $reason );

        return Gamed::SendToDelivery( Gamed::createHeader( $this->data['code']['forbidAcc'], $pack ) );
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
        $pack = pack( "N*", -1, 0, $role, $time ) . Gamed::packString( $reason );

        return Gamed::SendToDelivery( Gamed::createHeader( $this->data['code']['forbidRole'], $pack ) );
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
        $pack = pack( "N*", -1, 0, $role, $time ) . Gamed::packString( $reason );

        return Gamed::SendToDelivery( Gamed::createHeader( $this->data['code']['muteAcc'], $pack ) );
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
        $pack = pack( "N*", -1, 0, $role, $time ) . Gamed::packString( $reason );

        return Gamed::SendToDelivery( Gamed::createHeader( $this->data['code']['muteRole'], $pack ) );
    }

    /**
     * Returns the ID of role
     * @params string $rolename
     * @return string
     */
    public function getRoleid($rolename)
    {
        $pack = pack("N", -1).Gamed::packString($rolename).pack("C", 1);
        $data = Gamed::deleteHeader(Gamed::SendToGamedBD(Gamed::createHeader($this->data['code']['getRoleid'], $pack)));
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
        $pack = pack( "N*", -1, $role ) . Gamed::packString( $oldname ) . Gamed::packString( $newname );

        return Gamed::SendToGamedBD( Gamed::createHeader( $this->data['code']['renameRole'], $pack ) );
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
            $pack = pack( 'N*', -1, 1, $id ) . Gamed::packString( '1' );
            $pack = Gamed::createHeader( 352, $pack );
            $send = Gamed::SendToDelivery( $pack );
            $data = Gamed::deleteHeader($send);
            $data = Gamed::unmarshal( $data, $this->data['RoleList'] );

            if ( isset( $data['users'] ) )
            {
                foreach ( $data['users'] as $user )
                {
                    $online[] = $user;
                    //$id = Gamed::MaxOnlineUserID( $data['users'] );
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
        $data = Gamed::SendToGamedBD( Gamed::createHeader( $this->data['code']['getRoleFriend'], $pack ) );
        Gamed::unpackCuint( $data, $tmp );
        Gamed::unpackCuint( $data, $tmp );
        $data = substr( $data, $tmp+5 );

        return Gamed::unmarshal( $data, $this->data['FriendList'] );
    }

    public function addFaction($roleid, $name, $fid)
    {
        $pack = pack("N*", -1).Gamed::packString($name).pack("NN", $roleid, $fid);
        $pack = Gamed::createHeader($this->data['code']['AddFaction'], $pack);
        return Gamed::SendToGamedBD($pack);
    }

    public function delFaction($fid)
    {
        $pack = pack("N*", -1, $fid);
        $pack = Gamed::createHeader($this->data['code']['DelFaction'], $pack);
        return Gamed::SendToGamedBD($pack);
    }

    public function upgradeFaction($roleid, $fid, $level)
    {
        $pack = pack("N*", -1, $fid, $roleid, 0).pack("C", $level);
        $pack = Gamed::createHeader($this->data['code']['FactionUpgrade'], $pack);
        return Gamed::SendToGamedBD($pack);
    }

    public function getFactionInfo($id)
    {
        $pack = pack("N*", -1, $id);
        $pack = Gamed::createHeader($this->data['code']['getFactionInfo'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        return Gamed::unmarshal($data, $this->data['FactionInfo']);
    }

    public function getFactionDetail($id)
    {
        $pack = pack("N*", -1, $id);
        $pack = Gamed::createHeader($this->data['code']['getFactionDetail'], $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        return Gamed::unmarshal($data, $this->data['FactionDetail']);
    }

    public function getFactionFortressDetail($id)
    {
        /*$pack = pack("N*", -1, $id);
        $pack = Gamed::createHeader(config('code.GFactionFortressDetail'), $pack);
        $send = Gamed::SendToGamedBD($pack);
        $data = Gamed::deleteHeader($send);
        return Gamed::unmarshal($data, config('FactionFortressDetail'));*/
    }

    public function getTerritories()
    {
        $pack = pack( "N*", -1, 1 );
        $data = Gamed::SendToGamedBD( Gamed::createHeader( $this->data['code']['getTerritory'], $pack ) );
        $length = 0;
        Gamed::unpackCuint( $data, $length );
        Gamed::unpackCuint( $data, $length );
        $length += 6;
        $data = substr($data, $length);
        return Gamed::unmarshal( $data, $this->data['GTerritoryDetail'] );
    }

    public function getRaw($table, $handler = '', $key = '')
    {
        $pack = pack("N*",-1).Gamed::packLongOctet($table).Gamed::packOctet($handler).Gamed::packOctet($key);
        $data = Gamed::deleteHeader(Gamed::SendToGamedBD(Gamed::createHeader(3055, $pack)));
        return  Gamed::unmarshal($data, $this->data['RawRead']);
    }

    public function parseOctet($octet, $name)
    {
        $data = pack("H*", $octet);
        return Gamed::unmarshal($data, $this->data['octet'][$name]);
    }

    public function getUserFaction($id)
    {
        $tmp = 0;
        $pack = pack("N*", -1, 1, $id);
        $data = Gamed::SendToGamedBD(Gamed::createHeader($this->data['code']['getUserFaction'], $pack));
        Gamed::unpackCuint($data, $tmp);
        Gamed::unpackCuint($data, $tmp);
        $data = substr($data, $tmp+8);
        return Gamed::unmarshal($data, $this->data['getUserFaction']);
    }

    public function generateSkill($params = array())
    {
        $skills = substr($params['skills'], 8);
        $id = isset($params['id']) ? dechex($params['id']) : 1;
        $level = isset($params['level']) ? dechex($params['level']) : 1;
        $progress = isset($params['progress']) ? dechex($params['progress']) : 0;
        $skills .= Gamed::reverseOctet(Gamed::hex2octet($id));
        $skills .= Gamed::reverseOctet(Gamed::hex2octet($progress));
        $skills .= Gamed::reverseOctet(Gamed::hex2octet($level));
        $count = dechex(strlen($skills)/24);
        $skills = Gamed::reverseOctet(Gamed::hex2octet($count)).$skills;

        return $skills;
    }

    public function serverOnline()
    {
        return @fsockopen( config( 'pw-api.local' ), config( 'pw-api.ports.client' ), $errCode, $errStr, 1 ) ? TRUE : FALSE;
    }
}