<?php

function dbconnect($db_name)
{
    try {
        $db = new SQLite3($db_name);
    } catch (Exception $e) {
        echo "{'error':{'message':'no database connection','code':201}}\n";
        echo $e->getTraceAsString();
    }
    return $db;
}

$db_name = '/var/lib/phpliteadmin/vmctl.db'; //dbファイル名

$db = dbconnect($db_name);
$vmname="test1";
$servernum=2;

//サーバ情報をDBに入れる
$vmname_sql = SQLite3::escapeString($vmname);
$servernum_sql = SQLite3::escapeString($servernum);
//$ip, $userid, $vcpu, $memory, $disksize, $ostypeはサニタイズできていると仮定
$sql = "INSERT INTO vm (isdefined, domname, hostname, ipv4, serverid, userid, "
    . "cpu, memory, disk, ostype) values (1, '$vmname_sql', '$vmname_sql', "
    . "'$ip', $servernum_sql, $userid, $vcpu, $memory, $disksize, '$ostype')";
if ($res = mysqli_query($link, $sql)) {
    echo (mysqli_affected_rows($link) != 1) ? "{'error':{'message':'database error.','code':218}}\n" : "";
    //挿入が1行のみなら正常
} else {
    echo "{'error':{'message':'database error.','code':217}}\n";
    exit;
}

