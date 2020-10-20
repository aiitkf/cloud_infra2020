<?php
$id = SQLite3::escapeString($id);
$sql = "SELECT pubkey from user where username = '$id'";
$db = dbconnect($db_name);
$result = $db->querySingle($sql);
if ($result == "") {
    echo "{'error':{'message':'username not found','code':202}}\n";
} else {
    echo ('{"pubkey":"' . $result . '"}');
}
