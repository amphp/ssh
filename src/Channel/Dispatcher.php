<?php

namespace Amp\SSH\Channel;

use Amp\Emitter;
use Amp\SSH\Message\ChannelClose;
use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\Message;
use Amp\SSH\Transport\BinaryPacketHandler;
use function Amp\asyncCall;

/**
 * @internal
 */
class Dispatcher {
    /** @var Emitter[] */
    private $channelsEmitter = [];

    private $handler;

    private $channelSequence = 0;

    private $running = true;

    private $closed = false;

    public function __construct(BinaryPacketHandler $handler) {
        $this->handler = $handler;
    }

    public function start(): void {
        if ($this->closed) {
            throw new \RuntimeException('SSH Connection is closed');
        }

        asyncCall(function () {
            while ($this->running) {
                $message = yield $this->handler->read();

                if (!$message instanceof Message) {
                    continue;
                }

                $type = $message::getNumber();

                if ($type >= Message::SSH_MSG_CHANNEL_OPEN && $type <= Message::SSH_MSG_CHANNEL_FAILURE) {
                    $channelId = $message instanceof ChannelOpen ? $message->senderChannel : $message->recipientChannel;

                    if (!\array_key_exists($channelId, $this->channelsEmitter)) {
                        continue;
                    }

                    $this->channelsEmitter[$channelId]->emit($message);

                    if ($message instanceof ChannelClose) {
                        $this->channelsEmitter[$channelId]->complete();

                        unset($this->channelsEmitter[$channelId]);
                    }

                    continue;
                }
            }
        });
    }

    public function stop(): void {
        $this->running = false;
    }

    public function close() {
        $this->stop();

        foreach ($this->channelsEmitter as $emitter) {
            $emitter->complete();
        }
    }

    public function createSession(): Session {
        $emitter = new Emitter();
        $session = new Session($this->handler, $emitter->iterate(), $this->channelSequence);
        $this->channelsEmitter[$this->channelSequence] = $emitter;
        ++$this->channelSequence;

        return $session;
    }
}
