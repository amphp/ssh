<?php

namespace Amp\Ssh\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerTest {
    private static $logger;

    public static function get() {
        if (self::$logger === null) {
            self::$logger = new Logger('ampssh', [
                new StreamHandler(\fopen('debug.log', 'w+'))
            ]);
        }

        return self::$logger;
    }
}
