<?php

namespace Amp\Ssh\Tests\Negotiator;

use Amp\Ssh\Encryption\Decryption;
use Amp\Ssh\Encryption\Encryption;
use Amp\Ssh\KeyExchange\KeyExchange;
use Amp\Ssh\Mac\Mac;
use Amp\Ssh\Negotiator;

class NegotiatorBuilder {
    protected $negotiator;
    protected $addEncryptionClosure;
    protected $addDecryptionClosure;
    protected $addMacClosure;
    protected $addKeyExchangeClosure;

    public function __construct() {
        $this->negotiator = new Negotiator();

        $this->addEncryptionClosure = $this->getClosure('addEncryption');
        $this->addDecryptionClosure = $this->getClosure('addDecryption');
        $this->addMacClosure = $this->getClosure('addMac');
        $this->addKeyExchangeClosure = $this->getClosure('addKeyExchange');
    }

    private function getClosure($methodName) {
        $reflection = new \ReflectionObject($this->negotiator);
        return $reflection->getMethod($methodName)->getClosure($this->negotiator);
    }

    public function addEncryption(Encryption $encryption) {
        \call_user_func($this->addEncryptionClosure, $encryption);
    }

    public function addEncryptions() {
        $encryptions = Negotiator::supportedEncryptions();
        foreach ($encryptions as $encryption) {
            $this->addEncryption($encryption);
        }
    }

    public function addDecryption(Decryption $encryption) {
        \call_user_func($this->addDecryptionClosure, $encryption);
    }

    public function addDecryptions() {
        $decryptions = Negotiator::supportedDecryptions();
        foreach ($decryptions as $decryption) {
            $this->addDecryption($decryption);
        }
    }

    public function addMac(Mac $mac) {
        \call_user_func($this->addMacClosure, $mac);
    }

    public function addMacs() {
        $macs = Negotiator::supportedMacs();
        foreach ($macs as $mac) {
            $this->addMac($mac);
        }
    }

    public function addKeyExchange(KeyExchange $keyExchange) {
        \call_user_func($this->addKeyExchangeClosure, $keyExchange);
    }

    public function addKeyExchanges() {
        $keyExchanges = Negotiator::supportedKeyExchanges();
        foreach ($keyExchanges as $keyExchange) {
            $this->addKeyExchange($keyExchange);
        }
    }

    public function get(): Negotiator {
        return $this->negotiator;
    }
}
