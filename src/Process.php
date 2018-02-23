<?php

namespace Amp\Ssh;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\Deferred;
use Amp\Promise;
use Amp\Ssh\Channel\ChannelInputStream;
use Amp\Ssh\Channel\ChannelOutputStream;
use Amp\Ssh\Message\ChannelRequestExitStatus;
use Amp\Success;
use function Amp\asyncCall;
use function Amp\call;

class Process {
    /** @var Channel\Session */
    private $session;

    /** @var string */
    private $command;

    /** @var ChannelInputStream */
    private $stderr;

    /** @var ChannelInputStream */
    private $stdout;

    /** @var ChannelOutputStream */
    private $stdin;

    /** @var int */
    private $exitCode;

    /** @var Deferred */
    private $resolved;

    /** @var array */
    private $env;

    public function __construct(SSHResource $sshResource, string $command, string $cwd = null, array $env = []) {
        $this->session = $sshResource->createSession();
        $this->command = $cwd !== null ? \sprintf('cd %s; %s', $cwd, $command) : $command;
        $this->stdout = new ChannelInputStream($this->session->getDataEmitter()->iterate());
        $this->stderr = new ChannelInputStream($this->session->getDataExtendedEmitter()->iterate());
        $this->stdin = new ChannelOutputStream($this->session);
        $this->env = $env;
    }

    public function __destruct() {
        if ($this->isRunning()) {
            $this->kill();
        }
    }

    public function start(): Promise {
        if ($this->resolved !== null || $this->exitCode !== null) {
            throw new \RuntimeException('Process has already been started.');
        }

        $this->resolved = new Deferred();

        return call(function () {
            yield $this->session->open();

            foreach ($this->env as $key => $value) {
                yield $this->session->env($key, $value, true);
            }

            yield $this->session->exec($this->command);

            $this->handleRequests();
        });
    }

    public function join(): Promise {
        if ($this->exitCode !== null) {
            return new Success($this->exitCode);
        }

        if ($this->resolved === null) {
            throw new \RuntimeException('Process has not been started.');
        }

        return $this->resolved->promise();
    }

    public function kill(): void {
        Promise\rethrow($this->signal(SIGKILL));
    }

    public function signal(int $signo): Promise {
        if (!$this->isRunning()) {
            throw new \RuntimeException('Process is not running.');
        }

        return $this->session->signal($signo);
    }

    public function isRunning() {
        return $this->resolved !== null;
    }

    public function getStdin(): OutputStream {
        return $this->stdin;
    }

    public function getStdout(): InputStream {
        return $this->stdout;
    }

    public function getStderr(): InputStream {
        return $this->stderr;
    }

    protected function handleRequests(): void {
        asyncCall(function () {
            $requestIterator = $this->session->getRequestEmitter()->iterate();

            while ($this->isRunning()) {
                yield $requestIterator->advance();
                $message = $requestIterator->getCurrent();

                if ($message instanceof ChannelRequestExitStatus) {
                    $resolved = $this->resolved;
                    $this->resolved = null;
                    $resolved->resolve($message->code);

                    $this->session->close();

                    break;
                }
            }
        });
    }
}
