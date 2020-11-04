<?php
preg_match('|' . dirname($_SERVER['SCRIPT_NAME']) . '/([\w%/-]*)|', $_SERVER['REQUEST_URI'], $matches);

// デバッグ用
//echo '|' . dirname($_SERVER['SCRIPT_NAME']) . '/([\w%/-]*)|' . "\n";
//echo $_SERVER['REQUEST_URI'] . "\n";
//print_r($matches);
//print_r($_POST);
//print_r($matches);

//エラー番号 101-103

// DB接続
function dbconnect($db_name)
{
  try {
    $db = new SQLite3($db_name);
  } catch (Exception $e) {
    echo "{'error':{'message':'no database connection','code':201}}\n";
    echo $e->getTraceAsString();
  }
  return $db;
}

$db_name = '/var/lib/phpliteadmin/vmctl.db'; //dbファイル名
$paths = explode('/', $matches[1]);
$id = isset($paths[1]) ? htmlspecialchars($paths[1]) : null;
switch (strtolower($_SERVER['REQUEST_METHOD']) . ':' . $paths[0]) {
  case 'post:vm': //新しいVMをセットアップ
    if ($id) {
      include "vmpost.php";
    } else {
      echo "{'error':{'message':'no vmname','code':102}}\n";
    }
    break;
  case 'get:vm': //DBから一覧を表示、または名前が指定されていればそれだけを表示
    include "vmget.php";
    break;
  case 'delete:vm': // VMを削除してイメージも削除 まず停止が必要
    if ($id) {
      include "vmdelete.php";
    } else {
      echo "{'error':{'message':'no vmname','code':102}}\n";
    }
    break;
  case 'put:boot': // VMを起動
    if ($id) {
      include "bootput.php";
    } else {
      echo "{'error':{'message':'no vmname','code':102}}\n";
    }
    break;
  case 'delete:boot': // VMを強制シャットダウン
    if ($id) {
      include "bootdelete.php";
    } else {
      echo "{'error':{'message':'no vmname','code':102}}\n";
    }
    break;
  case 'post:scale': //新しいVMを複数セットアップ
    if ($id) {
      include "scalepost.php";
    } else {
      echo "{'error':{'message':'no vmname','code':102}}\n";
    }
    break;
  case 'post:key': // ユーザの公開鍵を作成、秘密鍵を返す
    // 重複ユーザ名はDBのuniqueで排除する。その他のチェックはしない
    if ($id) {
      include "keypost.php";
    } else {
      echo "{'error':{'message':'no username','code':101}}\n";
    }
    break;
  case 'get:key': // ユーザの公開鍵を表示
    if ($id) {
      include "keyget.php";
    } else {
      echo "{'error':{'message':'no username','code':101}}\n";
    }
    break;
  case 'put:key': // ユーザの公開鍵を更新
    if ($id) {
      include "keyput.php";
    } else {
      echo "{'error':{'message':'no username','code':101}}\n";
    }
    break;
  case 'delete:key': // ユーザの公開鍵を削除
    if ($id) {
      include "keydelete.php";
    } else {
      echo "{'error':{'message':'no username','code':101}}\n";
    }
    break;
  case 'get:panel': // ブラウザから操作する画面
  case 'post:panel': // ブラウザから操作する画面
    include "panel.php";
    break;
  default:
    echo "{'error':{'message':'api request method error','code':103}}\n";
}
