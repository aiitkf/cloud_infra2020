<?php
// パラメータを取得
parse_str(file_get_contents('php://input'), $param);

// 必要なパラメータがあることを確認
if (!isset($param['username']) || !isset($param['passwd'])) {
    echo "{'error':{'message':'parameter is not enough.','code':204}}\n";
    exit;
}
$username = $param['username'];
$passwd = $param['passwd'];
$vmname = $id;

// DB接続
$db = dbconnect($db_name);

// ユーザー認証を行う
$username_sql = SQLite3::escapeString($username); //サニタイズ
$passwd_sql = SQLite3::escapeString($passwd); //サニタイズ
$vmname_sql = SQLite3::escapeString($vmname); //サニタイズ
$sql = "SELECT user.id AS uid, server.id AS sid, server.ipv4 FROM user, server, vm "
    . " WHERE user.username='$username_sql' AND user.passwd='$passwd_sql' "
    . " AND vm.domname='$vmname_sql' AND vm.isdefined=1 AND vm.serverid=server.id"
    . " AND vm.userid=user.id";
if ($res = $db->querySingle($sql, true)) {
    //print_r($res);
    $ip_destserver = $res['ipv4'];
    //削除対象のVMの物理サーバのIPアドレスを取得
} else {
    echo "{'error':{'message':'authentication error. check username/password/vmname.','code':210}}\n";
    exit;
}

// 該当サーバにSSH接続する
require "ssh2connect.php";

$cmd = "sudo virsh destroy $vmname";
$stream = ssh2_exec($connection, $cmd);
stream_set_blocking($stream, true);
$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR); //エラー出力
$array = array(
    "output" => stream_get_contents($stream),
    "error" => stream_get_contents($errorStream),
);
echo json_encode($array); //実行結果を出力
fclose($stream);
fclose($errorStream);
exit;
