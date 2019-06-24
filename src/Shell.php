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

class Shell {
    /** @var Channel\Session */
    private $session;

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

    public function __construct(SshResource $sshResource, array $env = []) {
        $this->session = $sshResource->createSession();
        $this->handleRequests();
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

    public function join(): Promise {
        if ($this->exitCode !== null) {
            return new Success($this->exitCode);
        }

        if (!$this->isRunning()) {
            return new Failure(new StatusError('Shell is not running'));
        }

        return $this->resolved->promise();
    }

    public function start(int $columns = 80, int $rows = 24, int $width = 800, int $height = 600): Promise {
        if ($this->resolved !== null || $this->exitCode !== null) {
            return new Failure(new StatusError('Shell has already been started.'));
        }

        $this->resolved = new Deferred();
        $this->exitCode = null;

        return call(function () use ($columns, $rows, $width, $height) {
            try {
                yield $this->session->open();

                foreach ($this->env as $key => $value) {
                    yield $this->session->env($key, $value, true);
                }

                yield $this->session->pty($columns, $rows, $width, $height);
                yield $this->session->shell();
            } catch (\Exception $exception) {
                $this->resolved = null;
                throw $exception;
            }
        });
    }

    public function changeWindowSize(int $columns = 80, int $rows = 24, int $width = 800, int $height = 600) {
        if (!$this->isRunning()) {
            return new Failure(new StatusError('Shell is not running.'));
        }

        return $this->session->changeWindowSize($columns, $rows, $width, $height);
    }

    public function kill() {
        if (!$this->isRunning()) {
            throw new StatusError('Shell is not running.');
        }

        Promise\rethrow($this->signal(SIGKILL));
    }

    public function signal(int $signo): Promise {
        if (!$this->isRunning()) {
            return new Failure(new StatusError('Shell is not running.'));
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

                        break;
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
