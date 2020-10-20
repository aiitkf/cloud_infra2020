<?php

$cmd="sudo ssh root@192.168.6.2 -i /var/www/sshkeys/id_rsa_lan hostname";
passthru($cmd);

passthru("whoami;pwd");
