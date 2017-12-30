<?php

namespace Amp\SSH;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use function Amp\call;
use Amp\Deferred;
use Amp\Promise;
use Amp\SSH\Channel\ChannelInputStream;
use Amp\SSH\Channel\ChannelOutputStream;
use Amp\SSH\Message\ChannelRequest;
use Amp\SSH\Message\ChannelRequestExitStatus;
use Amp\SSH\Message\Message;

class Shell
{
    /** @var Channel\Session */
    private $session;

    /** @var ChannelInputStream */
    private $stderr;

    /** @var ChannelInputStream */
    private $stdout;

    /** @var ChannelOutputStream */
    private $stdin;

    /** @var Deferred */
    private $resolved;

    /** @var array */
    private $env;

    public function __construct(SSHResource $sshResource, array $env = [])
    {
        $this->session = $sshResource->createSession();
        $this->stdout = new ChannelInputStream($this->session);
        $this->stderr = new ChannelInputStream($this->session, Message::SSH_MSG_CHANNEL_EXTENDED_DATA);
        $this->stdin = new ChannelOutputStream($this->session);
        $this->env = $env;
        $this->session->each(Message::SSH_MSG_CHANNEL_REQUEST, function (ChannelRequest $request) {

            if (!$request instanceof ChannelRequestExitStatus) {
                return false;
            }

            yield $this->session->close();

            if ($this->resolved !== null) {
                $resolved = $this->resolved;
                $this->resolved = null;
                $resolved->resolve();
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
        if ($this->resolved !== null) {
            throw new \RuntimeException('Process has already been started.');
        }

        $this->resolved = new Deferred();

        return call(function () {
            yield $this->session->initialize();

            foreach ($this->env as $key => $value) {
                yield $this->session->env($key, $value, true);
            }

            yield $this->session->pty();
            yield $this->session->shell();
        });
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