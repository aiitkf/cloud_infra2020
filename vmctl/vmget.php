<?php
// DB接続
$db = dbconnect($db_name);

if ($id == "") {
    // VM名が空文字列のとき
    // 192.168.6.1/vm/ で打った場合こうなる
    unset($id);
}

if (isset($id)) {
    // VM名が指定されているとき
    $id_sql = SQLite3::escapeString($id);
    $where = "domname='$id_sql'";
} else {
    // VM名の指定がないとき
    $where = "isdefined=1";
}
$sql = "SELECT * from vm where " . $where;
if ($res = $db->query($sql)) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        echo json_encode($row);
    }
} else {
    echo "{'error':{'message':'no database response','code':203}}\n";
    exit;
}
