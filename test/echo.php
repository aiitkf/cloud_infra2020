<?php
$vmname = "test1";
$cmd = "cd /var/kvm/guest/; "
    . "sudo cloud-localds --network-config network-config.yaml ${vmname}_config.iso user-data meta-data";
echo $cmd;
