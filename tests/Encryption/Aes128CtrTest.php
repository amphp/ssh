<?php

namespace Amp\Ssh\Tests\Encryption;

use Amp\Ssh\Encryption\Aes;
use PHPUnit\Framework\TestCase;

/**
 * @see http://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38a.pdf
 */
class Aes128CtrTest extends TestCase {
    public function testEncrypt() {
        $aes128ctr = new Aes();
        $aes128ctr->resetEncrypt(
            \hex2bin('2b7e151628aed2a6abf7158809cf4f3c'),
            \hex2bin('f0f1f2f3f4f5f6f7f8f9fafbfcfdfeff')
        );

        $cipher = $aes128ctr->crypt(
            \hex2bin('6bc1bee22e409f96e93d7e117393172a')
            .
            \hex2bin('ae2d8a571e03ac9c9eb76fac45af8e51')
        );

        self::assertEquals(
            '874d6191b620e3261bef6864990db6ce9806f66b7970fdff8617187bb9fffdff',
            \bin2hex($cipher)
        );

        $cipher = $aes128ctr->crypt(\hex2bin('30c81c46a35ce411e5fbc1191a0a52ef'));

        self::assertEquals('5ae4df3edbd5d35e5b4f09020db03eab', \bin2hex($cipher));

        $cipher = $aes128ctr->crypt(\hex2bin('f69f2445df4f9b17ad2b417be66c3710'));

        self::assertEquals('1e031dda2fbe03d1792170a0f3009cee', \bin2hex($cipher));
    }

    public function testDecrypt() {
        $aes128ctr = new Aes();
        $aes128ctr->resetDecrypt(
            \hex2bin('2b7e151628aed2a6abf7158809cf4f3c'),
            \hex2bin('f0f1f2f3f4f5f6f7f8f9fafbfcfdfeff')
        );

        $plaintext = $aes128ctr->decrypt(\hex2bin('874d6191b620e3261bef6864990db6ce'));

        self::assertEquals('6bc1bee22e409f96e93d7e117393172a', \bin2hex($plaintext));

        $plaintext = $aes128ctr->decrypt(\hex2bin('9806f66b7970fdff8617187bb9fffdff'));

        self::assertEquals('ae2d8a571e03ac9c9eb76fac45af8e51', \bin2hex($plaintext));

        $plaintext = $aes128ctr->decrypt(\hex2bin('5ae4df3edbd5d35e5b4f09020db03eab'));

        self::assertEquals('30c81c46a35ce411e5fbc1191a0a52ef', \bin2hex($plaintext));

        $plaintext = $aes128ctr->decrypt(\hex2bin('1e031dda2fbe03d1792170a0f3009cee'));

        self::assertEquals('f69f2445df4f9b17ad2b417be66c3710', \bin2hex($plaintext));
    }
}
