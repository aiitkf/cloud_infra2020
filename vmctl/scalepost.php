<?php
// だいたい並列動作する
// 5台で5秒くらい

if (
    !isset($_POST['username']) || !isset($_POST['passwd'])
    || !isset($_POST['ip']) || !isset($_POST['ostype'])
    || !isset($_POST['vmcount'])
) {
    //必要な5つのパラメータがあることを確認
    //serverはなければランダムで決める
    //VM名=hostnameはURLで与える
    echo "{'error':{'message':'parameter is not enough.','code':204}}\n";
    exit;
}

if ($_POST['vmcount'] > 10) { // 一度のリクエストは10台まででいいと思う。
    echo "{'error':{'message':'too many vm requested.','code':241}}\n";
    exit;
}

if (isset($_POST['memory']) && $_POST['memory'] >= 256 && $_POST['memory'] <= 4096) {
    // 有効な1台あたりメモリ容量が指定されているとき
    $memory = $_POST['memory'];
} else {
    // それ以外のとき
    $memory = 1024; //MB
}

if (isset($_POST['disksize']) && $_POST['disksize'] >= 4 && $_POST['disksize'] <= 512) {
    // 有効な1台あたりディスク容量が指定されているとき
    $disksize = $_POST['disksize'];
} else {
    // それ以外のとき
    $disksize = 10; //GB
}

if (isset($_POST['vcpu']) && $_POST['vcpu'] >= 1 && $_POST['vcpu'] <= 4) {
    // 有効な1台あたりVCPU数が指定されているとき
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
$vmcount = $_POST['vmcount'];
$split = explode(".", $ip);
$ip_lastoctet = $split[3];
$ip_network = $split[0] . "." . $split[1] . "." . $split[2] . ".";
if (substr($ip, 0, 10) <> "192.168.7." || $ip_lastoctet < 1 || $ip_lastoctet > 254) {
    //IPアドレスの指定がおかしいとき
    echo "{'error':{'message':'invalid ip address','code':209}}\n";
    exit;
}

if (isset($_POST['server'])) {
    if ($_POST['server'] >= 2 && $_POST['server'] <= 4) {
        // 1台目の物理サーバ番号が指定されているとき
        $servernum = $_POST['server'];
    } else {
        echo "{'error':{'message':'server number specified is incorrect.','code':233}}\n";
        exit;
    }
} else {
    // 指定されていないとき
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
    echo "{'error':{'message':'username/password not found','code':202}}\n";
    exit();
}

//空きスペース確認のための配列
$serverarr = [2, 3, 4]; // 物理サーバの番号
$deployservernum = $servernum; //1台目のVMをデプロイするサーバ番号
$key = array_search($servernum, $serverarr);
for ($i = 0; $i < $vmcount; $i++) {
    $current = ($i + $key) % count($serverarr);
    //echo $serverarr[$current];
    $deployserverarr[] = $serverarr[$current]; //物理サーバ番号配置の配列
    $iparr[] = $ip_network . strval($ip_lastoctet + $i); //IPアドレスの配列
    $vmnamearr[] = $vmname . "-" . strval($i); //VM名の配列 アンダースコアを使うとubuntuでhostnameが正しく設定されない仕様かバグか
}
//print_r($deployserverarr); //展開するサーバ番号一覧
//print_r(array_count_values($deployserverarr)); //サーバ番号ごとの必要な台数
//print_r($iparr);
//print_r($vmnamearr);
$countdeployserverarr = array_count_values($deployserverarr); //サーバ番号、デプロイ台数
//この配列の長さ(i.e. 1, 2, or 3)の回数だけ空きスペースの確認を繰り返せばいい

// 空きスペースを確認する
for ($i = 0; $i < count($countdeployserverarr); $i++) { //繰り返し回数を指定。1,2,3のいずれか
    // $deployserverarr[$i]は確認するサーバ番号、$iは0,1,2のどれか
    $currentservernum = $deployserverarr[$i]; //問い合わせを行うサーバ番号
    $currentserver_vmcount = $countdeployserverarr[$currentservernum]; //問い合わせを行うサーバ番号にデプロイするVM台数
    $sql = "SELECT sum(cpu) AS sumcpu, sum(memory) AS summemory, sum(disk) AS sumdisk from vm where serverid=$currentservernum and isdefined=1";
    if ($result = $db->querySingle($sql, true)) {
        $currentusage = $result;
    } else {
        echo "{'error':{'message':'database error.','code':212}}\n";
        exit;
    }
    $sql = "SELECT maxcpu, maxmemory, maxdisk, ipv4 from server where id=$currentservernum";
    if ($result = $db->querySingle($sql, true)) {
        $capacity = $result;
    } else {
        echo "{'error':{'message':'database error.','code':213}}\n";
        exit;
    }
    if (
        // メモリ、CPU、ディスクのいずれか１つでも超過するとき
        $currentusage['sumcpu'] + ($vcpu * $currentserver_vmcount) > $capacity['maxcpu'] ||
        $currentusage['summemory'] + ($memory * $currentserver_vmcount) > $capacity['maxmemory'] ||
        $currentusage['sumdisk'] + ($disksize * $currentserver_vmcount) > $capacity['maxdisk']
    ) {
        echo "{'error':{'message':'no enough capacity on server #${currentservernum}.','code':214}}\n";
        exit;
    }
}

//IPアドレスまたはVM名の重複がないことを確認
for ($i = 0; $i < $vmcount; $i++) { //繰り返し回数を指定。最大サーバ台数(10)以下
    $vmname_sql = SQLite3::escapeString($vmnamearr[$i]);
    $sql = "SELECT id from vm where (ipv4='$iparr[$i]' OR domname='$vmname_sql') AND isdefined = 1";
    if ($res = $db->querySingle($sql)) {
        echo "{'error':{'message':'ip address or vm name duplicated at vm# $i','code':219}}\n";
        exit();
    }
}

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

file_put_contents("commands", ""); // 作業用ファイルを空にする

for ($i = 0; $i < $vmcount; $i++) {
    // ループが長いので注意

    // 該当サーバのIPアドレスをDBから取得する
    $sql = "SELECT ipv4 FROM server WHERE id=$deployserverarr[$i]";
    if ($res = $db->querySingle($sql)) {
        $ip_destserver = $res;
    } else {
        echo "{'error':{'message':'database error.','code':233}}\n";
        exit;
    }

    // sshリモートログイン
    // リモートにmeta-data, network-config.yaml, user-dataを作成
    // リモートにメタデータISO作成
    //qemu-img createでファイル作成コマンドを書き込み
    //virt-installで初期設定コマンドを書き込み

    $vmname = $vmnamearr[$i];
    $vmname_shell = escapeshellcmd($vmname);
    $ip = $iparr[$i];
    require "../vmctl/seeddata/${ostype}/meta-data.php";
    require "seeddata/virt-install.php";
    $virtinstall = $cmd;
    $cmd = <<<EOC
sudo ssh root@${ip_destserver} -i /var/www/sshkeys/id_rsa_lan \\
"echo '${metadata}' > /var/kvm/guest/meta-data&
echo '${userdata}' > /var/kvm/guest/user-data&
echo '${networkconfig}' > /var/kvm/guest/network-config.yaml;
cd /var/kvm/guest;
cloud-localds --network-config network-config.yaml ${vmname_shell}_config.iso user-data meta-data;
qemu-img create -f qcow2 -F qcow2 -b /var/kvm/master/${imgfile} /var/kvm/guest/${vmname_shell}.qcow2 ${disksize}G;
${virtinstall}"?
EOC;
file_put_contents('commands', $cmd, FILE_APPEND | LOCK_EX);

    //サーバ情報をDBに入れる
    $vmname_sql = SQLite3::escapeString($vmnamearr[$i]);
    $servernum_sql = SQLite3::escapeString($deployserverarr[$i]);
    //$ip, $userid, $vcpu, $memory, $disksize, $ostypeはサニタイズできていると仮定
    $sql = "INSERT INTO vm (isdefined, domname, hostname, ipv4, serverid, userid, "
        . "cpu, memory, disk, ostype) values (1, '$vmname_sql', '$vmname_sql', "
        . "'$iparr[$i]', $servernum_sql, $userid, $vcpu, $memory, $disksize, '$ostype')";
    if ($db->exec($sql)) {
        echo ($db->changes() != 1) ? "{'error':{'message':'database error.','code':218}}\n" : "";
        //挿入が1行のみなら正常
    } else {
        echo "{'error':{'message':'database error.','code':217}}\n";
        exit;
    }
} //user-data作成からのforの終わり

//passthru('exec 2>&1; xargs -P5 -t -d ? --arg-file commands -I {} sh -c {}'); //qemu-img create, virt-installを並列で実行
exec('xargs -P5 -t -d ? --arg-file commands -I {} sh -c {}'); //qemu-img create, virt-installを並列で実行
//file_put_contents("commands", "");
echo "{'success'}\n";
exit;
