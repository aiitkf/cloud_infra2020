<?php
// DB接続する
// DBからdefinedのVM一覧を取り出す
// HTMLに書き出す

if (isset($_POST)) {
    print_r($_POST);
    // デバッグ用: ポストされた内容をすべて表示する
}

$uuid = (string) filter_input(INPUT_POST, 'dom_getxml');
if ($uuid !== '') {
    $domName = $lv->domain_get_name_by_uuid($uuid);
    $ret     = $lv->domain_get_xml($domName);
    $xml = simplexml_load_string($ret);
    echo "location: " . $xml->devices->disk->source->attributes()->file;
    //xmlからイメージファイルの場所を抜き出す
    $ret = '<pre>' . str_replace(array('<', '>'), array('&lt;', '&gt;'), $ret) . '</pre>';
}

$uuid = (string) filter_input(INPUT_POST, 'dom_start');
if ($uuid !== '') {
    $domName = $lv->domain_get_name_by_uuid($uuid);
    $ret     = $lv->domain_start($domName) ? "<p>[ " . $domName . " ] has been started successfully</p>" : '<p>Error while starting domain: ' . $lv->get_last_error() . '</p>';
}

$uuid = (string) filter_input(INPUT_POST, 'dom_shutdown');
if ($uuid !== '') {
    $domName = $lv->domain_get_name_by_uuid($uuid);
    $ret     = $lv->domain_shutdown($domName) ? "<p>[ " . $domName . " ] has started to shutdown.</p>" : '<p>Error while shutdown domain: ' . $lv->get_last_error() . '</p>';
}

$uuid = (string) filter_input(INPUT_POST, 'dom_destroy');
if ($uuid !== '') {
    $domName = $lv->domain_get_name_by_uuid($uuid);
    $ret     = $lv->domain_destroy($domName) ? "<p>[ " . $domName . " ] has started to destroy.</p>" : '<p>Error while destroying domain: ' . $lv->get_last_error() . '</p>';
}

$uuid = (string) filter_input(INPUT_POST, 'dom_reboot');
if ($uuid !== '') {
    $domName = $lv->domain_get_name_by_uuid($uuid);
    $ret     = $lv->domain_reboot($domName) ? "<p>[ " . $domName . " ] has started to reboot.</p>" : '<p>Error while reboot domain: ' . $lv->get_last_error() . '</p>';
}

$uuid = (string) filter_input(INPUT_POST, 'dom_undefine');
if ($uuid !== '') {
    $domName = $lv->domain_get_name_by_uuid($uuid);
    $xmlret     = $lv->domain_get_xml($domName);
    $xml = simplexml_load_string($xmlret);
    $vollocation = $xml->devices->disk->source->attributes()->file;
    $ret = $lv->storagevolume_delete($vollocation);
    $ret     = $lv->domain_undefine($domName) ? "<p>[ " . $domName . " ] has started to undefine.</p>" : '<p>Error while undefining domain: ' . $lv->get_last_error() . '</p>';
}

$uuid = (string) filter_input(INPUT_POST, 'dom_clone');
if ($uuid !== '') {
    echo "cloning process started.";
    $domName = $lv->domain_get_name_by_uuid($uuid);
    //    $xml     = $lv->domain_get_xml($domName);
    //    $poolname = "kvm_centos7";
    //    $pool = $lv->get_storagepool_res($poolname); 
    //    $original_volume = libvirt_storagevolume_lookup_by_name($pool,$domName);
    //    echo libvirt_storagevolume_create_xml_from($pool,$xml,$original_volume);
    echo exec("sudo virt-clone --original $domName --auto-clone");
}

$doms = $lv->get_domains();
?>
<html>

<head>
    <meta charset="UTF-8">
    <title>PHP Virt Start</title>
</head>

<body>
    <?php
    if (isset($ret))
        echo $ret;
    ?>

    <form method="POST" action="#">
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>State</th>
                <th>UUID</th>
                <th>GetXML</th>
                <th>Start</th>
                <th>Shutdown</th>
                <th>Destroy</th>
                <th>Reboot</th>
                <th>Undefine</th>
                <th>Clone</th>
            </tr>
            <?php
            foreach ($doms as $name) :
                $dom          = $lv->get_domain_object($name);
                $uuid         = libvirt_domain_get_uuid_string($dom);
                $domid     = $lv->domain_get_id($dom);
                $info         = $lv->domain_get_info($dom);
                $state        = $lv->domain_state_translate($info['state']);
                $disable      = $lv->domain_is_running($name) ? ' disabled' : '';
                $disable_stop = $lv->domain_is_running($name) ? '' : ' disabled';
            ?>
                <tr>
                    <td><?php
                        echo $domid;
                        ?></td>
                    <td><?php
                        echo $name;
                        ?></td>
                    <td><?php
                        echo $state;
                        ?></td>
                    <td><?php
                        echo $uuid;
                        ?></td>
                    <td>
                        <button type="submit" name="dom_getxml" value="<?php
                                                                        echo $uuid;
                                                                        ?>">
                            GetXML</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_start" value="<?php
                                                                        echo $uuid;
                                                                        ?>" <?php
                                                                                echo $disable;
                                                                                ?>>Start</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_shutdown" value="<?php
                                                                            echo $uuid;
                                                                            ?>" <?php
                                                                                    echo $disable_stop;
                                                                                    ?>>Shutdown</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_destroy" value="<?php
                                                                        echo $uuid;
                                                                        ?>" <?php
                                                                                echo $disable_stop;
                                                                                ?>>Destroy</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_reboot" value="<?php
                                                                        echo $uuid;
                                                                        ?>" <?php
                                                                                echo $disable_stop;
                                                                                ?>>Reboot</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_undefine" value="<?php
                                                                            echo $uuid;
                                                                            ?>" <?php
                                                                                    echo $disable;
                                                                                    ?>>Undefine</button>
                    </td>
                    <td>
                        <button type="submit" name="dom_clone" value="<?php
                                                                        echo $uuid;
                                                                        ?>" <?php
                                                                                echo $disable;
                                                                                ?>>Clone</button>
                    </td>
                </tr>
            <?php
            endforeach;
            ?>
        </table>
        Shutdown: Try to shutdown normally.<br />
        Destroy: Force to shutdown. Does not destroy nor remove the disk image.<br />
        Reboot: Try to reboot normally.<br />
        Undefine: Remove image and free the storage, then undefine domain.<br />
        Clone: Just run "sudo virt-clone --original (domainname) --auto-clone". Takes five secounds or ten. Nothing exciting;)<br />
        <button type="submit" name="reload" value="reload">Reload</button>
    </form>
</body>

</html>