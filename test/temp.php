<?php

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // PHPのcURL実装だと手間なので，シェルを呼びだす
    if (isset($_POST['dom_start'])) {
        $cmd = 'curl -X DELETE http://192.168.6.1/vmctl/ -d "username=wan&passwd=1111"';
    } elseif (isset($_POST['dom_destroy'])) {
        $cmd = 'curl -X DELETE http://192.168.6.1/vmctl/ -d "username=wan&passwd=1111"';
    } elseif (isset($_POST['dom_undefine'])) {
        $cmd = 'curl -X DELETE http://192.168.6.1/vmctl/ -d "username=wan&passwd=1111"';
    }
    echo $cmd;
    //exec($cmd);
    // デバッグ用: ポストされた内容をすべて表示する
    print_r($_POST);
}
