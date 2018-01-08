<?php

namespace Amp\SSH\Channel;

use Amp\Promise;
use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\ChannelRequestEnv;
use Amp\SSH\Message\ChannelRequestExec;
use Amp\SSH\Message\ChannelRequestPty;
use Amp\SSH\Message\ChannelRequestShell;
use Amp\SSH\Message\ChannelRequestSignal;
use function Amp\call;

/**
 * @internal
 */
class Session extends Channel {
    protected function getType(): string {
        return ChannelOpen::TYPE_SESSION;
    }

    public function env(string $name, string $value, $quiet = false): Promise {
        $request = new ChannelRequestEnv();
        $request->recipientChannel = $this->channelId;
        $request->value = $value;
        $request->name = $name;
        $request->wantReply = !$quiet;

        return call(function () use ($request) {
            try {
                return yield $this->doRequest($request);
            } catch (\Throwable $exception) {
                throw new \RuntimeException('Unable to set env var, check it is authorised on the server', 0, $exception);
            }
        });
    }

    public function signal(int $signo) {
        $request = new ChannelRequestSignal();
        $request->recipientChannel = $this->channelId;
        $request->signal = $signo;

        return $this->doRequest($request);
    }

    public function pty(int $columns = 80, int $rows = 24, int $width = 800, int $height = 600) {
        $request = new ChannelRequestPty();
        $request->recipientChannel = $this->channelId;
        $request->columns = $columns;
        $request->rows = $rows;
        $request->width = $width;
        $request->height = $height;

        return $this->doRequest($request);
    }

    public function shell() {
        $request = new ChannelRequestShell();
        $request->recipientChannel = $this->channelId;

        return $this->doRequest($request);
    }

    public function exec(string $command) {
        $request = new ChannelRequestExec();
        $request->recipientChannel = $this->channelId;
        $request->command = $command;

        return $this->doRequest($request);
    }
}
