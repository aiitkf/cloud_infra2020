<?php
// meta-data
$metadata = <<<EOT
instance-id: $(uuidgen)
local-hostname: ${vmname}

EOT;

// network-config.yaml
$networkconfig = <<<EOT
version: 2
ethernets:
  eth0:
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
preserve_hostname: false # falseにするとここで指定したhostnameに変更される
hostname: ${vmname}
user: ${username}
password: ${passwd}
chpasswd: { expire: True } # 初回ログイン時にパスワード変更を強制
ssh_pwauth: True
ssh_authorized_keys:
  - ${pubkey}
# manage_etc_hosts: True
timezone: "Asia/Tokyo"

EOT;
