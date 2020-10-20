<?php
// https://pgmemo.tokyo/data/archives/897.html
$encoding = 'UTF-8';
mb_internal_encoding($encoding);
ini_set('mbstring.internal_encoding', $encoding);
ini_set('mbstring.script_encoding', $encoding);
header("Content-Type: text/html; charset={$encoding}");

// 接続するデータベース名
#$db_name = 'test.db';
$db_name = '/var/lib/phpliteadmin/test.db';


try {
    $db = new SQLite3($db_name);
} catch (Exception $e) {
    print 'DB接続エラー。<br>';
    print $e->getTraceAsString();
}

$results = $db->query('SELECT * FROM test_tbl');
?>
<pre>
<?php
$mode = SQLITE3_ASSOC;
while ($row = $results->fetchArray($mode)) {
    print_r($row);
    print '<br>';
}
$db->close();
?>
</pre>