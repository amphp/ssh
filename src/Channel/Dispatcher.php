<?php

namespace Amp\Ssh\Channel;

use function Amp\asyncCall;
use Amp\Emitter;
use Amp\Ssh\Message\ChannelClose;
use Amp\Ssh\Message\ChannelOpen;
use Amp\Ssh\Message\Message;
use Amp\Ssh\Transport\BinaryPacketHandler;

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

    public function start() {
        if ($this->closed) {
            throw new \RuntimeException('SSH Connection is closed');
        }

        asyncCall(function () {
            while ($this->running) {
                $message = yield $this->handler->read();

                if ($message === null) {
                    $this->doFail(new ChannelException('SSH connection was closed by remote server'));
                }

                if (!$message instanceof Message) {
                    continue;
                }

                $type = $message::getNumber();

                if ($type >= Message::SSH_MSG_CHANNEL_OPEN && $type <= Message::SSH_MSG_CHANNEL_FAILURE) {
                    $channelId = $message instanceof ChannelOpen ? $message->senderChannel : $message->recipientChannel;

                    if (!\array_key_exists($channelId, $this->channelsEmitter)) {
                        continue;
                    }

                    yield $this->channelsEmitter[$channelId]->emit($message);

                    if (!\array_key_exists($channelId, $this->channelsEmitter)) {
                        continue;
                    }

                    if ($message instanceof ChannelClose) {
                        $this->channelsEmitter[$channelId]->complete();

                        unset($this->channelsEmitter[$channelId]);
                    }

                    continue;
                }
            }
        });
    }

    private function doFail(\Throwable $reason) {
        $this->stop();
        foreach ($this->channelsEmitter as $channelId => $emitter) {
            $emitter->fail($reason);

            unset($this->channelsEmitter[$channelId]);
        }
    }

    public function stop() {
        $this->running = false;
    }

    public function close() {
        $this->stop();

        foreach ($this->channelsEmitter as $channelId => $emitter) {
            $emitter->complete();

            unset($this->channelsEmitter[$channelId]);
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
