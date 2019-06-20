<?php

namespace Amp\Ssh\Tests\Negotiator;

use function Amp\call;
use Amp\Loop;
use function Amp\Promise\timeout;
use Amp\Ssh\Authentication\AuthenticationFailureException;
use Amp\Ssh\Authentication\UsernamePassword;
use Amp\Ssh\Channel\Dispatcher;
use Amp\Ssh\Encryption\Decryption;
use Amp\Ssh\Encryption\Encryption;
use Amp\Ssh\KeyExchange\KeyExchange;
use Amp\Ssh\Mac\Mac;
use Amp\Ssh\Negotiator;
use Amp\Ssh\SshResource;
use Amp\Ssh\Tests\LoggerTest;
use Amp\Ssh\Transport\LoggerHandler;
use Amp\Ssh\Transport\MessageHandler;
use Amp\Ssh\Transport\PayloadHandler;
use PHPUnit\Framework\TestCase;

class NegotiatorTest extends TestCase {
    protected function getSsh(Negotiator $negotiator) {
        return call(function () use ($negotiator) {
            $uri = "127.0.0.1:2222";
            $authentication = new UsernamePassword('root', 'root');
            $identification = 'SSH-2.0-AmpSSH_0.1';
            $logger = LoggerTest::get();


            $socket = yield \Amp\Socket\connect($uri);

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

            $cryptedHandler = yield $negotiator->negotiate($loggerHandler, $serverIdentification, $identification);

            yield timeout($authentication->authenticate($cryptedHandler, $negotiator->getSessionId()), 1000);

            $dispatcher = new Dispatcher($cryptedHandler);
            $dispatcher->start();

            return new SshResource($cryptedHandler, $dispatcher);
        });
    }

    /**
     * @dataProvider keyExchanges
     * @param KeyExchange $keyExchange
     */
    public function testKeyExchange(KeyExchange $keyExchange) {
        Loop::run(function () use ($keyExchange) {
            $negotiator = new NegotiatorBuilder();
            $negotiator->addKeyExchange($keyExchange);
            $negotiator->addEncryptions();
            $negotiator->addDecryptions();
            $negotiator->addMacs();

            $sshResource = yield $this->getSsh($negotiator->get());
            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    /**
     * @dataProvider encryptions
     * @param Encryption $encryption
     */
    public function testEncryption(Encryption $encryption) {
        Loop::run(function () use ($encryption) {
            $negotiator = new NegotiatorBuilder();
            $negotiator->addKeyExchanges();
            $negotiator->addEncryption($encryption);
            $negotiator->addDecryptions();
            $negotiator->addMacs();

            $sshResource = yield $this->getSsh($negotiator->get());
            self::assertInstanceOf(SshResource::class, $sshResource);
            yield $sshResource->close();
        });
    }

    /**
     * @dataProvider decryptions
     * @param Decryption $decryption
     */
    public function testDecryption(Decryption $decryption) {
        Loop::run(function () use ($decryption) {
            $negotiator = new NegotiatorBuilder();
            $negotiator->addKeyExchanges();
            $negotiator->addDecryption($decryption);
            $negotiator->addEncryptions();
            $negotiator->addMacs();

            $sshResource = yield $this->getSsh($negotiator->get());
            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    /**
     * @dataProvider macs
     * @param Mac $mac
     */
    public function testMacs(Mac $mac) {
        Loop::run(function () use ($mac) {
            $negotiator = new NegotiatorBuilder();
            $negotiator->addKeyExchanges();
            $negotiator->addEncryptions();
            $negotiator->addDecryptions();
            $negotiator->addMac($mac);

            $sshResource = yield $this->getSsh($negotiator->get());
            self::assertInstanceOf(SshResource::class, $sshResource);
        });
    }

    public function provider($items) {
        $result = [];
        foreach ($items as $item) {
            $result[] = [$item];
        }
        return $result;
    }

    public function keyExchanges() {
        return $this->provider(Negotiator::supportedKeyExchanges());
    }

    public function dataName() {
        return parent::dataName(); // TODO: Change the autogenerated stub
    }

    public function encryptions() {
        return $this->provider(Negotiator::supportedEncryptions());
    }

    public function decryptions() {
        return $this->provider(Negotiator::supportedDecryptions());
    }

    public function macs() {
        return $this->provider(Negotiator::supportedMacs());
    }
}
