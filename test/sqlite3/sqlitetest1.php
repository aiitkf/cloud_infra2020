<?php
// 作成するデータベース名
$db_name = '/var/lib/phpliteadmin/test.db';
#$db_name = 'test.db';
// 作成するテーブルのSQL
$sql = <<<DOC_END
CREATE TABLE test_tbl (
	test_id    INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	test_name  TEXT
)
DOC_END;
// INSERTするデータのSQL
$sql2 = <<<DOC_END
INSERT INTO test_tbl(test_id, test_name) VALUES(4, '細野晴臣');
INSERT INTO test_tbl(test_id, test_name) VALUES(5, 'ほそのはるおみ');
INSERT INTO test_tbl(test_id, test_name) VALUES(6, 'ホソノハルオミ');
DOC_END;

try {
    $db = new SQLite3($db_name);
} catch (Exception $e) {
    print 'DB接続エラー。<br>';
    print $e->getTraceAsString();
}
$db->exec($sql);
$db->exec($sql2);
$db->close();
