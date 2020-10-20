<?php
$vmname = "hoge1";
$pubkey = "hogehoge";
$ip = "192.168.7.23";
$ostype = "ubuntu2004"; //centos7, centos8, ubuntu2004
require "../vmctl/seeddata/${ostype}/meta-data.php";

$filename1 = '/var/kvm/guest/meta-data';
$filename2 = '/var/kvm/guest/network-config.yaml';
$filename3 = '/var/kvm/guest/user-data';

file_put_contents($filename1, $metadata); //パーミッションに注意、0666にしておく
file_put_contents($filename2, $networkconfig); //パーミッションに注意、0666にしておく
file_put_contents($filename3, $networkconfig); //パーミッションに注意、0666にしておく
//readfile($filename);
echo "done";