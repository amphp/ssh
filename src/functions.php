<?php

namespace Amp\Ssh;

use Amp\Promise;
use Amp\Ssh\Authentication\Authentication;
use Amp\Ssh\Authentication\AuthenticationFailureException;
use Amp\Ssh\Channel\Dispatcher;
use Amp\Ssh\Transport\LoggerHandler;
use Amp\Ssh\Transport\MessageHandler;
use Amp\Ssh\Transport\PayloadHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\call;

function connect(string $uri, Authentication $authentication, LoggerInterface $logger = null, string $identification = 'SSH-2.0-AmpSSH_0.1'): Promise {
    return call(function () use ($uri, $authentication, $identification, $logger) {
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
        $serverIdentification = null;
        $buffer = '';

        while ($serverIdentification === null) {
            $chunk = yield $socket->read();
            if ($chunk === null) {
                throw new AuthenticationFailureException('Could not read server identification: connection closed during read');
            }

            $buffer .= $chunk;

            if (($linePos = \strpos($buffer, "\n")) !== false) {
                $line = \substr($buffer, 0, $linePos);

                if (\strpos($line, 'SSH-') === 0) {
                    // OpenSSH before 7.5 does not always send CR before LF
                    $serverIdentification = \rtrim($line, "\r");
                }

                $buffer = \substr($buffer, $linePos + 1);
            }
        }

        $payloadHandler = new PayloadHandler($socket, $buffer);
        $messageHandler = MessageHandler::create($payloadHandler);
        $loggerHandler = new LoggerHandler($messageHandler, $logger);

        $negotiator = Negotiator::create();
        $cryptedHandler = yield $negotiator->negotiate($loggerHandler, $serverIdentification, $identification);

        yield $authentication->authenticate($cryptedHandler, $negotiator->getSessionId());

        $dispatcher = new Dispatcher($cryptedHandler);
        $dispatcher->start();

        return new SshResource($cryptedHandler, $dispatcher);
    });
}
