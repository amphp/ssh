<?php

declare(strict_types=1);

namespace Amp\SSH\Encryption\CipherMode;

class Ctr implements CipherMode
{
    public const NAME = 'ctr';

    private const CTR_BLOCK_MAX = 65536; // maximum 16-bit unsigned integer value

    private const BLOCK_SIZE = 16;
    
    /**
     * The hash initialization vector, stored as eight 16-bit words
     * @var int[]
     */
    private $iv;

    /**
     * The counter offset to add to the initialization vector
     * @var int[]
     */
    private $ctrOffset;
    
    public function __construct(string $iv, string $openSSLMethodName)
    {
        if (\strlen($iv) !== \openssl_cipher_iv_length($openSSLMethodName)) {
            throw new \InvalidArgumentException('Invalid initialization vector');
        }

        $this->iv = $this->extractIvParts($iv);
        $this->ctrOffset = array_fill(0, 8, 0);
    }

    public function getCurrentIV(): string
    {
        $iv = array_fill(0, 8, 0);
        $carry = 0;
        for ($i = 7; $i >= 0; $i--) {
            $sum = $this->ctrOffset[$i] + $this->iv[$i] + $carry;
            $carry = (int) ($sum / self::CTR_BLOCK_MAX);
            $iv[$i] = $sum % self::CTR_BLOCK_MAX;
        }

        return implode('', array_map(function ($ivBlock) {
            return pack('n', $ivBlock);
        }, $iv));
    }

    public function updateIV(string $cipherBlock): void
    {
        $incrementBy = \strlen($cipherBlock) / self::BLOCK_SIZE;

        for ($i = 7; $i >= 0; $i--) {
            $incrementedBlock = $this->ctrOffset[$i] + $incrementBy;
            $incrementBy = (int) ($incrementedBlock / self::CTR_BLOCK_MAX);
            $this->ctrOffset[$i] = $incrementedBlock % self::CTR_BLOCK_MAX;
        }
    }

    private function extractIvParts(string $iv): array
    {
        return array_map(function ($part) {
            return unpack('nnum', $part)['num'];
        }, str_split($iv, 2));
    }

    private function calculateCurrentIv(array $baseIv, array $ctrOffset): string
    {
    }
}
