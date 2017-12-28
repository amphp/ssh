<?php

declare(strict_types=1);

namespace Amp\SSH\Channel;

use Amp\SSH\Message\ChannelClose;
use Amp\SSH\Message\ChannelData;
use Amp\SSH\Message\ChannelEof;
use Amp\SSH\Message\ChannelExtendedData;
use Amp\SSH\Message\ChannelFailure;
use Amp\SSH\Message\ChannelOpen;
use Amp\SSH\Message\ChannelOpenConfirmation;
use Amp\SSH\Message\ChannelOpenFailure;
use Amp\SSH\Message\ChannelRequest;
use Amp\SSH\Message\ChannelSuccess;
use Amp\SSH\Message\ChannelWindowAdjust;

interface ChannelListener
{
    public function onChannelOpen(ChannelOpen $channelOpen);

    public function onChannelOpenConfirmation(ChannelOpenConfirmation $channelSuccess);

    public function onChannelOpenFailure(ChannelOpenFailure $channelFailure);

    public function onChannelData(ChannelData $channelData);

    public function onChannelExtendedData(ChannelExtendedData $channelExtraData);

    public function onChannelEof(ChannelEof $channelEof);

    public function onChannelClose(ChannelClose $channelClose);

    public function onChannelWindowAdjust(ChannelWindowAdjust $channelWindowAdjust);

    public function onChannelSuccess(ChannelSuccess $channelSuccess);

    public function onChannelFailure(ChannelFailure $channelFailure);

    public function onChannelRequest(ChannelRequest $channelRequest);
}
