<?php
namespace App\Helpers;
use App\Model\Server;
use Config;
use DB;
use Auth;

class DatabaseConnection
{
    public static function setConnection($params)
    {
        config(['database.connections.dynamicmysql' => [
            'driver' => 'mysql',
            'host' => $params->host,
            'username' => $params->username,
            'password' => $params->password,
            'database' => $params->dbname
        ]]);

        return DB::connection('dynamicmysql');
    }

    public static function getConnectionParams(){
        dd(Auth::user());
    }


    public static function getServers(){
        $servers = Server::where('is_active',1)->get();
        return $servers;
    }
}
?>
