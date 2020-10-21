<?php
// meta-data
$metadata = <<<EOT
instance-id: $(uuidgen)

EOT;

// network-config.yaml
$networkconfig = <<<EOT
version: 2
ethernets:
  interface0:
    match:
      name: en*
    addresses:
    - ${ip}/23
    gateway4: 192.168.6.254
    nameservers:
      addresses:
      - 202.225.94.247
      - 210.147.240.193

EOT;

// user-data
$userdata = <<< EOT
#cloud-config
preserve_hostname: false # falseにするとprivate ipをもとにしたホスト名
hostname: ${vmname}
user: ${username}
password: ${passwd}
chpasswd: { expire: True }
ssh_pwauth: True
ssh_authorized_keys:
  - ${pubkey}
# manage_etc_hosts: True
timezone: "Asia/Tokyo"

EOT;
