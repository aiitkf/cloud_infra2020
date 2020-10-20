<?php
exec('ssh-keygen -t ed25519 -f temp.key -N "" -q -C ""', $retval);
// print_r($retval);
$privkey = file_get_contents('temp.key'); //save private key into file
$pubkey = file_get_contents('temp.key.pub'); //save private key into file

echo "Private Key:\n $privkey \n\n";
echo "Public key:\n$pubkey\n\n";
