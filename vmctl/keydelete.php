<?php
// パラメータを取得
parse_str(file_get_contents('php://input'), $delete_param);
//print_r($put_param);

if (!isset($delete_param['passwd'])) { //パスワードがあることを確認
    echo "{'error':{'message':'parameter is not enough.','code':204}}\n";
    exit;
}

//パラメータを別の変数に代入
$passwd = $delete_param['passwd'];
$newkey = "";

// DB接続
$db = dbconnect($db_name);

//ユーザ入力値のサニタイズ
$id = SQLite3::escapeString($id);
$passwd = SQLite3::escapeString($passwd);
$sql = "DELETE FROM user WHERE username = '$id' AND passwd = '$passwd'";
//echo $sql;
$db->exec($sql);

// 実行で更新された行が1行でなければエラー、1行なら成功
if ($db->changes() != 1) {
    echo "{'error':{'message':'check username and password.','code':205}}";
} else {
    echo "{'success'}";
}
