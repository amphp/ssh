<?php

namespace Amp\Ssh\Channel;

use Amp\Promise;
use Amp\Ssh\Message\ChannelOpen;
use Amp\Ssh\Message\ChannelRequestEnv;
use Amp\Ssh\Message\ChannelRequestExec;
use Amp\Ssh\Message\ChannelRequestPty;
use Amp\Ssh\Message\ChannelRequestShell;
use Amp\Ssh\Message\ChannelRequestSignal;
use function Amp\call;

/**
 * @internal
 */
final class Session extends Channel {
    protected function getType(): string {
        return ChannelOpen::TYPE_SESSION;
    }

    public function env(string $name, string $value, $quiet = false): Promise {
        $request = new ChannelRequestEnv();
        $request->recipientChannel = $this->channelId;
        $request->value = $value;
        $request->name = $name;
        $request->wantReply = !$quiet;

        return call(function () use ($request, $quiet, $name) {
            try {
                return yield $this->doRequest($request, !$quiet);
            } catch (\Throwable $exception) {
                throw new SessionEnvException(\sprintf('Unable to set env var %s, check if it is authorised on the server', $name), 0, $exception);
            }
        });
    }

    public function signal(int $signo) {
        $request = new ChannelRequestSignal();
        $request->recipientChannel = $this->channelId;
        $request->signal = $signo;

        return $this->doRequest($request, false);
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
