<?php

namespace RZP\Gateway\Mpi\Blade;

class VEReq
{
    const MESSAGE                = 'Message';
    const ATTRIBUTES             = '@attributes';
    const VEREQ                  = 'VEReq';
    const VERSION                = 'version';
    const PAN                    = 'pan';
    const MERCHANT               = 'Merchant';
    const ACQBIN                 = 'acqBIN';
    const MERCHANT_ID            = 'merID';
    const BROWSER                = 'Browser';
    const DEVICE_CATEGORY        = 'deviceCategory';
    const DEVICE_ACCEPT          = 'accept';
    const DEVICE_UA              = 'userAgent';

    const ACQ_BIN                = 'acq_bin';
    const CRED_MERCHANT_ID       = 'merchant_id';
    const PASSWORD               = 'password';

    const EXTENSION              = 'Extension';
    const ID                     = 'id';
    const CRITICAL               = 'critical';

    const IVR_CH_PHONE_FORMAT    = 'npc356chphoneidformat';
    const IVR_CH_PHONE           = 'npc356chphoneid';
    const IVR_PAREQ_CHANNEL      = 'npc356pareqchannel';
    const IVR_SHOP_CHANNEL       = 'npc356shopchannel';
    const IVR_AVAIL_AUTH_CHANNEL = 'npc356availauthchannel';
    const IVR_ITP_CREDENTIAL     = 'npc356itpcredential';
}
