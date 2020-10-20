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

$username_sql = "hoge1"; //サニタイズ
$passwd_sql = "hogehoge"; //サニタイズ
$sql = "SELECT id, pubkey FROM user WHERE username='$username_sql' AND passwd='$passwd_sql'";
//echo $sql;
$result = $db->querySingle($sql, true);
if ($result) {
    $userid = $result['id'];
    $pubkey = $result['pubkey'];
} else {
    echo "{'error':{'message':'username not found','code':202}}\n";
}
