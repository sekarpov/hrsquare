<?php
try {
    $socket = fsockopen('127.0.0.1', 9000, $errno, $error, 2);
    if (!$socket) { exit(1); }
    fclose($socket);
    new PDO('pgsql:host='.getenv('DB_HOST').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_TIMEOUT => 3]);
    $redis = new Redis();
    $redis->connect(getenv('REDIS_HOST'), 6379, 2);
    $redis->auth(getenv('REDIS_PASSWORD'));
    exit($redis->ping() ? 0 : 1);
} catch (Throwable $e) { exit(1); }
