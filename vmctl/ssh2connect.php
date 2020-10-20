<?php
//$ip_destserver="192.168.6.2";
// 該当サーバにSSH接続する
$connection = ssh2_connect($ip_destserver, 22, array('hostkey' => 'ssh-rsa'));

if (!ssh2_auth_pubkey_file(
    $connection,
    'root',
    '/var/www/sshkeys/id_rsa_lan.pub',
    '/var/www/sshkeys/id_rsa_lan'
)) {
    echo "{'error':{'message':'ssh connection error.','code':219}}\n";
    exit;
}
//echo "認証成功 $ip_destserver" . PHP_EOL;
