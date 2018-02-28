<?php

namespace Amp\Ssh\Transport;

use Amp\Promise;
use Amp\Ssh\Encryption\Decryption;
use Amp\Ssh\Encryption\Encryption;
use Amp\Ssh\Mac\Mac;
use Amp\Ssh\Message;
use function Amp\call;

/**
 * @internal
 */
class MessageHandler implements BinaryPacketHandler {
    private $handler;

    private $messageClassRegistry = [];

    public function __construct(BinaryPacketHandler $handler) {
        $this->handler = $handler;
    }

    public function registerMessageClass(string $messageClass) {
        if (!\is_subclass_of($messageClass, Message\Message::class)) {
            throw new \RuntimeException(\sprintf('%s must be a instance of Message', $messageClass));
        }

        $this->messageClassRegistry[$messageClass::getNumber()] = $messageClass;
    }

    public static function create(...$args) {
        $static = new static(...$args);
        $static->registerMessageClass(Message\Disconnect::class);
        $static->registerMessageClass(Message\Ignore::class);
        $static->registerMessageClass(Message\Unimplemented::class);
        $static->registerMessageClass(Message\Debug::class);
        $static->registerMessageClass(Message\ServiceRequest::class);
        $static->registerMessageClass(Message\ServiceAccept::class);
        $static->registerMessageClass(Message\KeyExchangeInit::class);
        $static->registerMessageClass(Message\NewKeys::class);
        $static->registerMessageClass(Message\KeyExchangeCurveInit::class);
        $static->registerMessageClass(Message\KeyExchangeCurveReply::class);
        $static->registerMessageClass(Message\UserAuthRequest::class);
        $static->registerMessageClass(Message\UserAuthFailure::class);
        $static->registerMessageClass(Message\UserAuthSuccess::class);
        $static->registerMessageClass(Message\UserAuthBanner::class);
        $static->registerMessageClass(Message\UserAuthPkOk::class);
        $static->registerMessageClass(Message\GlobalRequest::class);
        $static->registerMessageClass(Message\ChannelOpen::class);
        $static->registerMessageClass(Message\ChannelOpenConfirmation::class);
        $static->registerMessageClass(Message\ChannelOpenFailure::class);
        $static->registerMessageClass(Message\ChannelWindowAdjust::class);
        $static->registerMessageClass(Message\ChannelData::class);
        $static->registerMessageClass(Message\ChannelExtendedData::class);
        $static->registerMessageClass(Message\ChannelEof::class);
        $static->registerMessageClass(Message\ChannelClose::class);
        $static->registerMessageClass(Message\ChannelRequest::class);
        $static->registerMessageClass(Message\ChannelSuccess::class);
        $static->registerMessageClass(Message\ChannelFailure::class);

        return $static;
    }

    public function updateDecryption(Decryption $decryption, Mac $decryptMac) {
        $this->handler->updateDecryption($decryption, $decryptMac);
    }

    public function updateEncryption(Encryption $encryption, Mac $encryptMac) {
        $this->handler->updateEncryption($encryption, $encryptMac);
    }

    public function read(): Promise {
        return call(function () {
            $packet = yield $this->handler->read();

            if ($packet === null) {
                return null;
            }

            if ($packet instanceof Message\Message) {
                return $packet;
            }

            $type = \unpack('C', $packet)[1];

            if (\array_key_exists($type, $this->messageClassRegistry)) {
                $class = $this->messageClassRegistry[$type];
                $packet = $class::decode($packet);
            }

            return $packet;
        });
    }

    public function write($message): Promise {
        if ($message instanceof Message\Message) {
            $message = $message->encode();
        }

        return $this->handler->write($message);
    }

    public function close() {
        $this->handler->close();
    }
}
