<?php

namespace Amp\Ssh;

use function Amp\asyncCall;
use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use function Amp\call;
use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Ssh\Channel\ChannelInputStream;
use Amp\Ssh\Channel\ChannelOutputStream;
use Amp\Ssh\Message\ChannelRequestExitStatus;
use Amp\Success;

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

    /** @var bool */
    private $open = false;

    /** @var array */
    private $env;

    public function __construct(SshResource $sshResource, string $command, string $cwd = null, array $env = []) {
        $this->session = $sshResource->createSession();
        $this->handleRequests();
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
            return new Failure(new StatusError('Process has already been started.'));
        }

        $this->resolved = new Deferred();

        return call(function () {
            try {
                if (!$this->open) {
                    yield $this->session->open();

                    $this->open = true;
                }

                foreach ($this->env as $key => $value) {
                    yield $this->session->env($key, $value);
                }

                yield $this->session->exec($this->command);
            } catch (\Exception $exception) {
                $this->resolved = null;
                throw $exception;
            }
        });
    }

    public function join(): Promise {
        if ($this->exitCode !== null) {
            return new Success($this->exitCode);
        }

        if ($this->resolved === null) {
            return new Failure(new StatusError('Process has not been started.'));
        }

        return $this->resolved->promise();
    }

    public function kill() {
        Promise\rethrow($this->signal(SIGKILL));
    }

    public function signal(int $signo): Promise {
        if (!$this->isRunning()) {
            return new Failure(new StatusError('Process is not running.'));
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

    private function handleRequests() {
        asyncCall(function () {
            $requestIterator = $this->session->getRequestEmitter()->iterate();

            try {
                while (yield $requestIterator->advance()) {
                    $message = $requestIterator->getCurrent();

                    if ($message instanceof ChannelRequestExitStatus) {
                        $resolved = $this->resolved;
                        $this->resolved = null;
                        $this->exitCode = $message->code;
                        $resolved->resolve($message->code);
                    }
                }
                // some servers does not send exit status
                if ($this->resolved) {
                    $this->resolved->resolve(false);
                    $this->exitCode = false;
                    $this->resolved = null;
                }
            } catch (\Exception $exception) {
                if ($this->resolved) {
                    $resolved = $this->resolved;
                    $this->resolved = null;
                    $resolved->fail($exception);
                }
            }
        });
    }
}
