<?php

namespace Amp\Ssh\KeyExchange;

function twos_compliment($data) {
    return (\ord($data[0]) & 0x80)
        ? \chr(0) . $data
        : $data;
}
