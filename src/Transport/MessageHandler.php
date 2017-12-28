<?php

declare(strict_types=1);

namespace Amp\SSH\Transport;

use function Amp\call;
use Amp\Promise;
use Amp\SSH\Encryption\Decryption;
use Amp\SSH\Encryption\Encryption;
use Amp\SSH\Mac\Mac;
use Amp\SSH\Message;

class MessageHandler implements BinaryPacketHandler
{
    private $handler;

    private $messageClassRegistry = [];

    public function __construct(BinaryPacketHandler $handler)
    {
        $this->handler = $handler;
    }

    public function registerMessageClass(string $messageClass)
    {
        if (!is_subclass_of($messageClass, Message\Message::class)) {
            throw new \RuntimeException(sprintf('%s must be a instance of Message', $messageClass));
        }

        $this->messageClassRegistry[$messageClass::getNumber()] = $messageClass;
    }

    public static function create(...$args)
    {
        $static = new static(...$args);
        $static->registerMessageClass(Message\ChannelClose::class);
        $static->registerMessageClass(Message\ChannelData::class);
        $static->registerMessageClass(Message\ChannelEof::class);
        $static->registerMessageClass(Message\ChannelExtendedData::class);
        $static->registerMessageClass(Message\ChannelFailure::class);
        $static->registerMessageClass(Message\ChannelOpen::class);
        $static->registerMessageClass(Message\ChannelOpenConfirmation::class);
        $static->registerMessageClass(Message\ChannelOpenFailure::class);
        $static->registerMessageClass(Message\ChannelRequest::class);
        $static->registerMessageClass(Message\ChannelSuccess::class);
        $static->registerMessageClass(Message\ChannelWindowAdjust::class);

        return $static;
    }

    public function updateDecryption(Decryption $decryption, Mac $decryptMac): void
    {
        $this->handler->updateDecryption($decryption, $decryptMac);
    }

    public function updateEncryption(Encryption $encryption, Mac $encryptMac): void
    {
        $this->handler->updateEncryption($encryption, $encryptMac);
    }

    public function read(): Promise
    {
        return call(function () {
            $packet = yield $this->handler->read();

            if ($packet instanceof Message\Message) {
                return $packet;
            }

            $type = unpack('C', $packet)[1];

            if (array_key_exists($type, $this->messageClassRegistry)) {
                $class = $this->messageClassRegistry[$type];
                $packet = $class::decode($packet);
            }

            return $packet;
        });
    }

    public function write($message): Promise
    {
        if ($message instanceof Message\Message) {
            $message = $message->encode();
        }

        return $this->handler->write($message);
    }
}