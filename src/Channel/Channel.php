<?php

namespace Amp\Ssh\Channel;

use function Amp\asyncCall;
use function Amp\call;
use Amp\Emitter;
use Amp\Iterator;
use Amp\Promise;
use Amp\Ssh\Message\ChannelClose;
use Amp\Ssh\Message\ChannelData;
use Amp\Ssh\Message\ChannelEof;
use Amp\Ssh\Message\ChannelExtendedData;
use Amp\Ssh\Message\ChannelFailure;
use Amp\Ssh\Message\ChannelOpen;
use Amp\Ssh\Message\ChannelOpenConfirmation;
use Amp\Ssh\Message\ChannelOpenFailure;
use Amp\Ssh\Message\ChannelRequest;
use Amp\Ssh\Message\ChannelSuccess;
use Amp\Ssh\Transport\BinaryPacketWriter;
use Amp\Success;

/**
 * @internal
 */
abstract class Channel {
    protected $channelId;

    /** @var BinaryPacketWriter */
    protected $writer;

    /** @var Iterator */
    protected $channelMessage;

    protected $dataEmitter;

    protected $dataExtendedEmitter;

    protected $requestEmitter;

    protected $requestResultEmitter;

    protected $open = false;

    public function __construct(BinaryPacketWriter $writer, Iterator $channelMessage, int $channelId) {
        $this->channelId = $channelId;
        $this->writer = $writer;
        $this->channelMessage = $channelMessage;
        $this->dataEmitter = new Emitter();
        $this->dataExtendedEmitter = new Emitter();
        $this->requestEmitter = new Emitter();
        $this->requestResultEmitter = new Emitter();
    }

    public function getChannelId(): int {
        return $this->channelId;
    }

    /**
     * @return Emitter
     */
    public function getDataEmitter(): Emitter {
        return $this->dataEmitter;
    }

    /**
     * @return Emitter
     */
    public function getDataExtendedEmitter(): Emitter {
        return $this->dataExtendedEmitter;
    }

    /**
     * @return Emitter
     */
    public function getRequestEmitter(): Emitter {
        return $this->requestEmitter;
    }

    protected function dispatch() {
        asyncCall(function () {
            try {
                while (yield $this->channelMessage->advance()) {
                    $message = $this->channelMessage->getCurrent();

                    if ($message instanceof ChannelData) {
                        $this->dataEmitter->emit($message);
                    }

                    if ($message instanceof ChannelExtendedData) {
                        $this->dataExtendedEmitter->emit($message);
                    }

                    if ($message instanceof ChannelRequest) {
                        $this->requestEmitter->emit($message);
                    }

                    if ($message instanceof ChannelSuccess || $message instanceof ChannelFailure) {
                        $this->requestResultEmitter->emit($message);
                    }

                    if ($message instanceof ChannelClose) {
                        $this->doClose();
                    }
                }
                if ($this->open) {
                    $this->doClose();
                }
            } catch (\Exception $exception) {
                $this->doFail($exception);
            }
        });
    }

    public function open(): Promise {
        return call(function () {
            $channelOpen = new ChannelOpen();
            $channelOpen->senderChannel = $this->channelId;
            $channelOpen->channelType = $this->getType();

            yield $this->writer->write($channelOpen);
            yield $this->channelMessage->advance();

            $openResult = $this->channelMessage->getCurrent();

            if ($openResult instanceof ChannelOpenConfirmation) {
                $this->open = true;
                $this->dispatch();

                return true;
            }

            if ($openResult instanceof ChannelOpenFailure) {
                throw new \RuntimeException('Failed to open channel : ' . $openResult->description);
            }

            throw new \RuntimeException('Invalid message receive');
        });
    }

    public function data(string $data): Promise {
        $message = new ChannelData();
        $message->data = $data;
        $message->recipientChannel = $this->channelId;

        if ($data === '') {
            return new Success;
        }

        return $this->writer->write($message);
    }

    public function eof(): Promise {
        $message = new ChannelEof();
        $message->recipientChannel = $this->channelId;

        return $this->writer->write($message);
    }

    public function close(): Promise {
        return call(function () {
            $message = new ChannelClose();
            $message->recipientChannel = $this->channelId;

            yield $this->writer->write($message);

            $this->doClose();
        });
    }

    public function __destruct() {
        if ($this->open) {
            $this->close();
        }
    }

    private function doClose() {
        $this->open = false;
        $this->requestResultEmitter->complete();
        $this->requestEmitter->complete();
        $this->dataEmitter->complete();
        $this->dataExtendedEmitter->complete();
    }

    private function doFail(\Exception $reason) {
        $this->open = false;
        $this->requestResultEmitter->fail($reason);
        $this->requestEmitter->fail($reason);
        $this->dataEmitter->complete();
        $this->dataExtendedEmitter->complete();
    }

    protected function doRequest(ChannelRequest $request, $needAck = true): Promise {
        return call(function () use ($request, $needAck) {
            yield $this->writer->write($request);

            if (!$needAck) {
                return true;
            }

            if (!yield $this->requestResultEmitter->iterate()->advance()) {
                throw new ChannelException(\sprintf('Cannot advance on the channel iterator sending %s message', \get_class($request)));
            }

            $message = $this->requestResultEmitter->iterate()->getCurrent();

            if ($message instanceof ChannelFailure) {
                throw new ChannelFailureException('Request failure', $message);
            }

            if (!$message instanceof ChannelSuccess) {
                throw new ChannelException('Invalid message receive');
            }

            return true;
        });
    }

    abstract protected function getType(): string;
}
