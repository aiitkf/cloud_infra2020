<?php
if (
    !isset($_POST['fullname'])
    || !isset($_POST['passwd'])
) {
    //必要な2つのパラメータがあることを確認
    //pubkeyがないときは作成
    echo "{'error':{'message':'parameter is not enough.','code':204}}\n";
    exit;
}

//パラメータを別の変数に代入
$passwd = $_POST['passwd'];
$fullname = $_POST['fullname'];
$username = $id;

if (!isset($_POST['pubkey'])) {
    // 公開鍵がないときは鍵ペアを作成
    exec('ssh-keygen -t ed25519 -f ./tempkeys/' . $username . '.key -N "" -q -C ""');
    $privkey = file_get_contents("./tempkeys/" . $username . '.key'); //save private key into file
    $pubkey = file_get_contents("./tempkeys/" . $username . '.key.pub'); //save private key into file
    $pubkey = str_replace(" " . PHP_EOL, '', $pubkey);
    exec("rm -f ./tempkeys/$username*");
} else {
    $pubkey = $_POST['pubkey'];
}

//ユーザ入力値のサニタイズ
$fullname = SQLite3::escapeString($fullname);
$username = SQLite3::escapeString($username);
$passwd = SQLite3::escapeString($passwd);
$pubkey = SQLite3::escapeString($pubkey);
//sql文を生成
$sql = "INSERT INTO user (fullname,username,passwd,pubkey) VALUES ('$fullname','$username','$passwd','$pubkey')";

// DB接続
$db = dbconnect($db_name);

//echo $sql;
$db->exec($sql);

// 実行で更新された行が1行でなければエラー、1行なら成功
if ($db->changes() != 1) {
    echo "{'error':{'message':'database error.','code':207}}\n";
    exit();
}
if (isset($privkey)) { //秘密鍵を生成した場合
    echo "{'success':{'privkey':'$privkey','pubkey','$pubkey'}}\n";
} else {
    // 秘密鍵を生成しない場合、単に成功のみ返す
    echo "{'success'}\n";
}
$db->close();
