<?php
     echo "hello!<br />";
     $conn = libvirt_connect('null', false);
     if (!$conn){
	die("no connection!");
}

     $doms = libvirt_list_active_domains($conn);
     $num_dom = libvirt_domain_get_counts($conn);
     print_r($doms);
     print_r($num_dom);
?>

