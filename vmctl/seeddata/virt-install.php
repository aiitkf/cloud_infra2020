<?php
// virt-installで初期設定
$cmd = <<<EOT
sudo virt-install --connect qemu:///system \
-n ${vmname_shell} \
--memory=${memory} \
--vcpus=${vcpu} \
--network bridge=br0 \
--import \
--disk path=/var/kvm/guest/${vmname_shell}.qcow2 \
--disk path=/var/kvm/guest/${vmname_shell}_config.iso,device=cdrom \
--os-type=linux \
--os-variant=${osvariant} \
--graphics none \
--hvm \
--virt-type kvm 

EOT;
