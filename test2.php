<?php

require_once __DIR__ . '/vendor/autoload.php';

$ssh2 = new \phpseclib\Net\SSH2('127.0.0.1');
$ssh2->login('joelwurtz');
var_dump($ssh2->isConnected());
