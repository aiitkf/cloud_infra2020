<?php
if (
    !isset($_POST['username']) || !isset($_POST['passwd'])
    || !isset($_POST['ip']) || !isset($_POST['ostype'])
) {
    //必要な4つのパラメータがあることを確認
    //serverはなければランダムで決める
    //VM名=hostnameはURLで与える
    echo "{'error':{'message':'parameters are not enough.','code':204}}\n";
    exit;
}

if (isset($_POST['memory']) && $_POST['memory'] >= 256 && $_POST['memory'] <= 4096) {
    // 有効なメモリ容量が指定されているとき
    $memory = $_POST['memory'];
} else {
    // それ以外のとき
    $memory = 1024; //MB
}

if (isset($_POST['disksize']) && $_POST['disksize'] >= 4 && $_POST['disksize'] <= 512) {
    // 有効なディスク容量が指定されているとき
    $disksize = $_POST['disksize'];
} else {
    // それ以外のとき
    $disksize = 10; //GB
}

if (isset($_POST['vcpu']) && $_POST['vcpu'] >= 1 && $_POST['vcpu'] <= 4) {
    // 有効なVCPU数が指定されているとき
    $vcpu = $_POST['vcpu'];
} else {
    // それ以外のとき
    $vcpu = 1; //個
}

//パラメータを別の変数に代入
$username = $_POST['username'];
$passwd = $_POST['passwd'];
$vmname = $id;
$ip = $_POST['ip'];
$ostype = $_POST['ostype'];
$split = explode(".", $ip);
$ip_lastoctet = $split[3];

if (substr($ip, 0, 10) <> "192.168.7." || $ip_lastoctet < 1 || $ip_lastoctet > 254) {
    //IPアドレスの指定がおかしいとき
    echo "{'error':{'message':'invalid ip address','code':209}}\n";
    exit;
}

if (isset($_POST['server']) && $_POST['server'] >= 2 && $_POST['server'] <= 4) {
    // 物理サーバ番号が指定されているとき
    $servernum = $_POST['server'];
} else {
    // 物理サーバ番号が指定されていないとき
    $servernum = mt_rand(2, 4);
}

// DB接続
$db = dbconnect($db_name);

// ユーザー認証，公開鍵取得
$username_sql = SQLite3::escapeString($username); //サニタイズ
$passwd_sql = SQLite3::escapeString($passwd); //サニタイズ
$sql = "SELECT id, pubkey FROM user WHERE username='$username_sql' AND passwd='$passwd_sql'";
//echo $sql;
$result = $db->querySingle($sql, true);
if ($result) {
    $userid = $result['id'];
    $pubkey = $result['pubkey'];
} else {
    echo "{'error':{'message':'username not found','code':202}}\n";
    exit();
}

// 空きスペースを確認する
// $servernumは2-4の間
$sql = "SELECT sum(cpu), sum(memory), sum(disk) FROM vm WHERE serverid=${servernum} AND isdefined = 1";
if ($result = $db->querySingle($sql, true)) {
    $currentusage = $result;
} else {
    echo "{'error':{'message':'database error.','code':212}}\n";
    exit;
}
$sql = "SELECT maxcpu, maxmemory, maxdisk, ipv4 FROM server WHERE id=${servernum}";
if ($result = $db->querySingle($sql, true)) {
    $capacity = $result;
    // VMを作成する物理サーバのIPアドレスを取得
    $ip_destserver = $capacity['ipv4'];
} else {
    echo "{'error':{'message':'database error.','code':213}}\n";
    exit;
}
if (
    // メモリ、CPU、ディスクのいずれか１つでも超過するとき
    $currentusage['sum(cpu)'] + $vcpu > $capacity['maxcpu'] ||
    $currentusage['sum(memory)'] + $memory > $capacity['maxmemory'] ||
    $currentusage['sum(disk)'] + $disksize > $capacity['maxdisk']
) {
    echo "{'error':{'message':'no enough capacity on specified host server.','code':214}}\n";
    exit;
}

//IPアドレスまたはVM名の重複がないことを確認
$vmname_sql = SQLite3::escapeString($vmname); //サニタイズ
$sql = "SELECT id FROM vm WHERE (ipv4='$ip' OR domname='$vmname_sql') AND isdefined = 1";
if ($result = $db->querySingle($sql)) {
    // 結果がある = 重複あり
    echo "{'error':{'message':'ip address or vm name duplicated','code':219}}\n";
    exit;
} //結果なしなら重複なし，なにもしない

// ostypeデータをチェック
if ($ostype == "centos7") {
    $imgfile = "CentOS-7-x86_64-GenericCloud.qcow2";
    $osvariant = "centos7.0";
} elseif ($ostype == "centos8") {
    $imgfile = "CentOS-8-GenericCloud-8.2.2004-20200611.2.x86_64.qcow2";
    $osvariant = "centos8";
} elseif ($ostype == "ubuntu2004") {
    $imgfile = "focal-server-cloudimg-amd64.img";
    $osvariant = "ubuntu20.04";
} else {
    echo "{'error':{'message':'ostype error.','code':216}}\n";
    exit;
}

// meta-data, network-config.yaml, user-dataを作成
require "../vmctl/seeddata/${ostype}/meta-data.php";
file_put_contents('/var/kvm/guest/meta-data', $metadata); //パーミッションに注意、0666にしておく
file_put_contents('/var/kvm/guest/user-data', $userdata); //パーミッションに注意、0666にしておく
file_put_contents('/var/kvm/guest/network-config.yaml', $networkconfig); //パーミッションに注意、0666にしておく

//ローカルにメタデータISO作成
$vmname_shell = escapeshellcmd($vmname);
$cmd = "cd /var/kvm/guest/; "
    . "sudo cloud-localds --network-config network-config.yaml ${vmname_shell}_config.iso user-data meta-data";
//echo $cmd;
exec($cmd);
//www-dataをsudoersに入れておくこと、またはパーミッションをいじってもいい
//apache ALL=(ALL) NOPASSWD: /bin/genisoimage

// 該当サーバにSSH接続する
require "ssh2connect.php";

//metadataのisoをscpで転送する
$local_file = "/var/kvm/guest/" . $vmname . "_config.iso";
$remote_file = "/var/kvm/guest/" . $vmname . "_config.iso";
if (!ssh2_scp_send($connection, $local_file, $remote_file)) {
    echo "{'error':{'message':'ssh transfer failed.','code':220}}\n";
    exit;
}
unlink($local_file); //ローカルのISOは削除 権限に注意

//qemu-img createでファイル作成
$cmd = "sudo qemu-img create -f qcow2 -F qcow2 -b /var/kvm/master/${imgfile}"
    . " /var/kvm/guest/${vmname}.qcow2 ${disksize}G";
//exec($cmd);
if (!ssh2_exec($connection, $cmd)) {
    //リモートでのファイル作成が失敗したとき
    echo "{'error':{'message':'remote image creation failed.','code':221}}\n";
    exit;
}

// サーバ情報をDBに入れる
$vmname_sql = SQLite3::escapeString($vmname);
//$ip, $servernum, $userid, $vcpu, $memory, $disksize, $ostypeはサニタイズできていると仮定
$sql = "INSERT INTO vm (isdefined, domname, hostname, ipv4, serverid, userid, "
    . "cpu, memory, disk, ostype) values (1, '$vmname_sql', '$vmname_sql', "
    . "'$ip', $servernum, $userid, $vcpu, $memory, $disksize, '$ostype')";
if ($db->exec($sql)) {
    echo ($db->changes() != 1) ? "{'error':{'message':'database error.','code':218}}\n" : "";
    //挿入が1行のみなら正常
} else {
    echo "{'error':{'message':'database error.','code':217}}\n";
    exit;
}

//virt-installで初期設定
$vmname_shell = escapeshellcmd($vmname);
require "seeddata/virt-install.php";
if (!ssh2_exec($connection, $cmd)) {
    //リモートでのVM作成が失敗したとき
    echo "{'error':{'message':'remote vm start process failed.','code':222}}\n";
    exit;
}

echo "{'success'}\n";
//OK!

//処理の順番が適切か再確認
//ubuntuは試してない
//メモリやディスクサイズを変えた起動は確認済み
//IPとVM名の重複チェック機能がは追加
