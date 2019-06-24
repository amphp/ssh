<?php

namespace Amp\Ssh\Tests;

use Amp\Ssh\SshResource;

class NetworkHelper {
    /**
     * Simulate disconnect from server.
     * @param SshResource $sshResource
     */
    public static function disconnect(SshResource $sshResource) {
        $reflection = new \ReflectionObject($sshResource);
        $property = $reflection->getProperty('handler');
        $property->setAccessible(true);
        $handler = $property->getValue($sshResource);
        $handler->close();
    }
}
