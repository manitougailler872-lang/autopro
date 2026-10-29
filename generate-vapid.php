<?php

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();

echo 'PUBLIC KEY:' . PHP_EOL;
echo $keys['publicKey'] . PHP_EOL;

echo PHP_EOL . 'PRIVATE KEY:' . PHP_EOL;
echo $keys['privateKey'] . PHP_EOL;
