<?php
// パラメータを取得
parse_str(file_get_contents('php://input'), $put_param);
//print_r($put_param);

if (!isset($put_param['passwd']) || !isset($put_param['newkey'])) {
    //パラメータが2つあることを確認
    echo "{'error':{'message':'parameter is not enough.','code':204}}\n";
    exit;
}

//パラメータを別の変数に代入
$passwd = $put_param['passwd'];
$newkey = $put_param['newkey'];

// DB接続
$db = dbconnect($db_name);

//ユーザ入力値のサニタイズ
$id = SQLite3::escapeString($id);
$newkey = SQLite3::escapeString($newkey);
$passwd = SQLite3::escapeString($passwd);
$sql = "UPDATE user SET pubkey = '$newkey' WHERE username = '$id' AND passwd = '$passwd'";
//echo $sql;
$db->exec($sql);

// 実行で更新された行が1行でなければエラー、1行なら成功
if ($db->changes() != 1) {
    echo "{'error':{'message':'check username and password.','code':205}}";
} else {
    echo "{'success'}";
}
