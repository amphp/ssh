<?php

namespace Amp\SSH\Message;

interface Message
{
    public const SSH_MSG_DISCONNECT = 1;
    public const SSH_MSG_IGNORE = 2;
    public const SSH_MSG_UNIMPLEMENTED = 3;
    public const SSH_MSG_DEBUG = 4;
    public const SSH_MSG_SERVICE_REQUEST = 5;
    public const SSH_MSG_SERVICE_ACCEPT = 6;
    public const SSH_MSG_KEXINIT = 20;
    public const SSH_MSG_NEWKEYS = 21;
    public const SSH_MSG_KEXDH_INIT = 30;
    public const SSH_MSG_KEXDH_REPLY = 31;
    public const SSH_MSG_KEX_ECDH_INIT = 30;
    public const SSH_MSG_KEX_ECDH_REPLY = 31;
    public const SSH_MSG_USERAUTH_REQUEST = 50;
    public const SSH_MSG_USERAUTH_FAILURE = 51;
    public const SSH_MSG_USERAUTH_SUCCESS = 52;
    public const SSH_MSG_USERAUTH_BANNER = 53;
    public const SSH_MSG_USERAUTH_PK_OK = 60;
    public const SSH_MSG_GLOBAL_REQUEST = 80; //@TODO
    public const SSH_MSG_REQUEST_SUCCESS = 81; //@TODO
    public const SSH_MSG_REQUEST_FAILURE = 82; //@TODO
    public const SSH_MSG_CHANNEL_OPEN = 90;
    public const SSH_MSG_CHANNEL_OPEN_CONFIRMATION = 91;
    public const SSH_MSG_CHANNEL_OPEN_FAILURE = 92;
    public const SSH_MSG_CHANNEL_WINDOW_ADJUST = 93;
    public const SSH_MSG_CHANNEL_DATA = 94;
    public const SSH_MSG_CHANNEL_EXTENDED_DATA = 95;
    public const SSH_MSG_CHANNEL_EOF = 96;
    public const SSH_MSG_CHANNEL_CLOSE = 97;
    public const SSH_MSG_CHANNEL_REQUEST = 98;
    public const SSH_MSG_CHANNEL_SUCCESS = 99;
    public const SSH_MSG_CHANNEL_FAILURE = 100;

    public function encode(): string;

    /**
     * @param string $payload
     *
     * @return static
     */
    public static function decode(string $payload);

    public static function getNumber(): int;
}
