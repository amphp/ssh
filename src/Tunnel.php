<?php

namespace Amp\Ssh;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\Promise;
use Amp\Ssh\Channel\ChannelInputStream;
use Amp\Ssh\Channel\ChannelOutputStream;
use Amp\Success;

class Tunnel implements InputStream, OutputStream {
    /** @var Channel\DirectTcpIp */
    private $directTcpIp;

    /** @var ChannelInputStream */
    private $input;

    /** @var ChannelOutputStream */
    private $output;

    private $connected = false;

    public function __construct(SshResource $sshResource, string $host, int $port, string $originHost, string $originPort) {
        $this->directTcpIp = $sshResource->createDirectTcpIp($host, $port, $originHost, $originPort);
        $this->input = new ChannelInputStream($this->directTcpIp->getDataEmitter()->iterate());
        $this->output = new ChannelOutputStream($this->directTcpIp);
    }

    public function read(): Promise {
        return \Amp\call(function () {
            yield $this->connect();

            return yield $this->input->read();
        });
    }

    public function write(string $data): Promise {
        return \Amp\call(function () use ($data) {
            yield $this->connect();

            return $this->output->write($data);
        });
    }

    public function end(string $finalData = ''): Promise {
        return \Amp\call(function () use ($finalData) {
            yield $this->connect();

            return $this->output->end($finalData);
        });
    }

    private function connect(): Promise {
        if ($this->connected) {
            return new Success();
        }

        $this->connected = true;

        return $this->directTcpIp->open();
    }
}
