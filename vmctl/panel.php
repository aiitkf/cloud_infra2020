<?php
/*
できること
VM一覧の表示 (vmget) definedのみ
VM起動(bootput)
強制シャットダウン (bootdelete)
VM削除 (vmdelete)

できないこと
新VM起動 (vmpost, scalepost)
ユーザー鍵管理
*/

// DB接続
$db = dbconnect($db_name);

// DBからdefinedのVM一覧を取り出す
$sql = "SELECT * from vm where isdefined = 1";
if ($res = $db->query($sql)) {
    // なにもしない
} else {
    echo "{'error':{'message':'no database response','code':203}}\n";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // PHPのcURL実装だと手間なので，シェルを呼びだす
    // ユーザ入力値なので本当はサニタイズが必要
    if (isset($_POST['dom_start'])) {
        $cmd = 'curl -X PUT http://192.168.6.1/vmctl/boot/' .    $_POST['dom_start']
            . ' -d "username=' . $_POST['username'] . '&passwd=' . $_POST['password'] . '"';
        echo $cmd;
        exec($cmd);
    } elseif (isset($_POST['dom_destroy'])) {
        $cmd = 'curl -X DELETE http://192.168.6.1/vmctl/boot/' .    $_POST['dom_destroy']
            . ' -d "username=' . $_POST['username'] . '&passwd=' . $_POST['password'] . '"';
        echo $cmd;
        exec($cmd);
    } elseif (isset($_POST['dom_undefine'])) {
        $cmd = 'curl -X DELETE http://192.168.6.1/vmctl/vm/'  .  $_POST['dom_undefine']
            . ' -d "username=' . $_POST['username'] . '&passwd=' . $_POST['password'] . '"';
        echo $cmd;
        exec($cmd);
    }
    // デバッグ用: ポストされた内容をすべて表示する
    //print_r($_POST);
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>VM Control Panel</title>
</head>

<body>
    <?php
    if (isset($ret))
        echo $ret;
    ?>

    <form method="POST" action="#">
        <table>
            <tr>
                <td><b>username:</b></td>
                <td><input type="text" name="username" size="30"></td>
            </tr>
            <tr>
                <td><b>password:</b></td>
                <td><input type="password" name="password" size="10"></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>ID</th>
                <th>SetupTime<br />(UTC)</th>
                <th>DomName</th>
                <th>IPv4</th>
                <th>ServerID</th>
                <th>UserID</th>
                <th>CPU</th>
                <th>RAM</th>
                <th>Disk</th>
                <th>OSType</th>
                <th>Start</th>
                <th>Destroy</th>
                <th>Undefine</th>
            </tr>
            <?php
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                //echo json_encode($row);
                //print_r($row);
                $domid = $row['id'];
                $setuptime = $row['setuptime'];
                $dom = $row['domname'];
                $ipv4 = $row['ipv4'];
                $serverid = $row['serverid'];
                $userid = $row['userid'];
                $cpu = $row['cpu'];
                $memory = $row['memory'];
                $disk = $row['disk'];
                $ostype = $row['ostype'];
            ?>
                <tr>
                    <td><?php
                        echo $domid; ?></td>
                    <td><?php
                        echo $setuptime; ?></td>
                    <td><?php
                        echo $dom; ?></td>
                    <td><?php
                        echo $ipv4; ?></td>
                    <td><?php
                        echo $serverid; ?></td>
                    <td><?php
                        echo $userid; ?></td>
                    <td><?php
                        echo $cpu; ?></td>
                    <td><?php
                        echo $memory; ?></td>
                    <td><?php
                        echo $disk; ?></td>
                    <td><?php
                        echo $ostype; ?></td>

                    <td>
                        <button type="submit" name="dom_start" value="<?php
                                                                        echo $dom; ?>">Start</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_destroy" value="<?php
                                                                        echo $dom; ?>">Destroy</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_undefine" value="<?php
                                                                            echo $dom; ?>">Undefine</button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </table>
        Start: Try to start VM normally.<br />
        Destroy: Force to shutdown. Does not destroy nor remove the disk image.<br />
        Undefine: Remove image and free the storage, then undefine domain.<br />
        <button type="submit" name="reload" value="reload">Reload</button>
    </form>
</body>

</html>