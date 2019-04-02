<?php

namespace Amp\Ssh;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\Promise;
use Amp\Ssh\Channel\ChannelInputStream;
use Amp\Ssh\Channel\ChannelOutputStream;
use function Amp\call;

class Tunnel implements InputStream, OutputStream
{
    /** @var Channel\DirectTcpIp */
    private $directTcpIp;

    /** @var ChannelInputStream */
    private $input;

    /** @var ChannelOutputStream */
    private $output;

    /** @var Promise|null */
    private $connectPromise;

    /** @var bool */
    private $connected = false;

    public function __construct(
        SshResource $sshResource,
        string $host,
        int $port,
        string $originHost,
        string $originPort
    ) {
        $this->directTcpIp = $sshResource->createDirectTcpIp($host, $port, $originHost, $originPort);
        $this->input = new ChannelInputStream($this->directTcpIp->getDataEmitter()->iterate());
        $this->output = new ChannelOutputStream($this->directTcpIp);
    }

    public function read(): Promise
    {
        if ($this->connected) {
            return $this->input->read();
        }

        return call(function () {
            yield $this->connect();

            return yield $this->input->read();
        });
    }

    private function connect(): Promise
    {
        if ($this->connectPromise) {
            return $this->connectPromise;
        }

        $connected = &$this->connected;
        $this->connectPromise = $this->directTcpIp->open();
        $this->connectPromise->onResolve(static function ($e) use (&$connected) {
            if (!$e) {
                $connected = true;
            }
        });

        return $this->connectPromise;
    }

    public function write(string $data): Promise
    {
        if ($this->connected) {
            return $this->output->write($data);
        }

        return call(function () use ($data) {
            yield $this->connect();

            return $this->output->write($data);
        });
    }

    public function end(string $finalData = ''): Promise
    {
        if ($this->connected) {
            return $this->output->end($finalData);
        }

        return call(function () use ($finalData) {
            yield $this->connect();

            return $this->output->end($finalData);
        });
    }
}
