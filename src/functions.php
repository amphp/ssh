<?php

namespace Amp\SSH;

use function Amp\call;
use Amp\SSH\Authentication\UsernamePassword;
use Amp\SSH\Transport\LoggerHandler;
use Amp\SSH\Transport\MessageHandler;
use Amp\SSH\Transport\PayloadHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

function connect($uri, $username, $password, LoggerInterface $logger = null, $identification = 'SSH-2.0-AmpSSH_0.1') {
    return call(function () use($uri, $username, $password, $identification, $logger) {
        $socket = yield \Amp\Socket\connect($uri);
        $logger = $logger ?? new NullLogger();

        yield $socket->write($identification . "\r\n");

        /*
        The server MAY send other lines of data before sending the version
        string.  Each line SHOULD be terminated by a Carriage Return and Line
        Feed.  Such lines MUST NOT begin with "SSH-", and SHOULD be encoded
        in ISO-10646 UTF-8 [RFC3629] (language is not specified).  Clients
        MUST be able to process such lines.  Such lines MAY be silently
        ignored, or MAY be displayed to the client user.
        */
        $serverIdentification = false;
        $readed = '';

        while ($serverIdentification === false) {
            $readed .= yield $socket->read();

            if (($crlfpos = strpos($readed, "\r\n")) !== false) {
                $line = substr($readed, 0, $crlfpos);

                if (strpos($line, 'SSH') === 0) {
                    $serverIdentification = $line;
                }

                $readed = substr($readed, $crlfpos + 2);
            }
        }

        $payloadHandler = new PayloadHandler($socket, $readed);
        $messageHandler = MessageHandler::create($payloadHandler);
        $loggerHandler = new LoggerHandler($messageHandler, $logger);

        $negotiator = Negotiator::create();
        $cryptedHandler = yield $negotiator->negotiate($loggerHandler, $serverIdentification, $identification);

        $auth = new UsernamePassword($username, $password);
        yield $auth->authenticate($cryptedHandler);

        $loop = new SSHResource($cryptedHandler);
        $loop->loop();

        return $loop;
    });
}
