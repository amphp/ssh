<?php

namespace Amp\SSH;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use function Amp\call;
use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\SSH\Channel\ChannelInputStream;
use Amp\SSH\Channel\ChannelOutputStream;
use Amp\SSH\Message\ChannelRequest;
use Amp\SSH\Message\ChannelRequestExitStatus;
use Amp\SSH\Message\Message;
use Amp\Success;

class Process
{
    /** @var Channel\Session */
    private $session;

    private $command;

    private $stderr;

    private $stdout;

    private $stdin;

    private $exitCode;

    /** @var Deferred */
    private $resolved;

    private $env;

    public function __construct(SSHResource $sshResource, string $command, string $cwd = null, array $env = [])
    {
        $this->session = $sshResource->createSession();
        $this->command = $cwd !== null ? sprintf('cd %s; %s', $cwd, $command) : $command;
        $this->stdout = new ChannelInputStream($this->session);
        $this->stderr = new ChannelInputStream($this->session, Message::SSH_MSG_CHANNEL_EXTENDED_DATA);
        $this->stdin = new ChannelOutputStream();
        $this->env = $env;
        $this->session->once(Message::SSH_MSG_CHANNEL_REQUEST, function (ChannelRequest $request) {
            if (!$request instanceof ChannelRequestExitStatus) {
                return false;
            }

            $this->exitCode = $request->code;

            if ($this->resolved !== null) {
                $resolved = $this->resolved;
                $this->resolved = null;
                $resolved->resolve($this->exitCode);
            }

            return true;
        });
    }

    public function __destruct()
    {
        if ($this->isRunning()) {
            $this->kill();
        }
    }

    public function start(): Promise
    {
        if ($this->resolved !== null || $this->exitCode !== null) {
            throw new \RuntimeException('Process has already been started.');
        }

        $this->resolved = new Deferred();

        return call(function () {
            yield $this->session->initialize();

            foreach ($this->env as $key => $value) {
                yield $this->session->env($key, $value, true);
            }

            yield $this->session->exec($this->command);
        });
    }

    public function join(): Promise
    {
        if ($this->exitCode !== null) {
            return new Success($this->exitCode);
        }

        if ($this->resolved === null) {
            throw new \RuntimeException('Process has not been started.');
        }

        return $this->resolved->promise();
    }

    public function kill(): void
    {
        Promise\rethrow($this->signal(SIGKILL));
    }

    public function signal(int $signo): Promise
    {
        if (!$this->isRunning()) {
            throw new \RuntimeException('Process is not running.');
        }

        return $this->session->signal($signo);
    }

    public function isRunning()
    {
        return $this->resolved !== null;
    }

    public function getStdin(): OutputStream
    {
        return $this->stdin;
    }

    public function getStdout(): InputStream
    {
        return $this->stdout;
    }

    public function getStderr(): InputStream
    {
        return $this->stderr;
    }
}