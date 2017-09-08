<?php

namespace RZP\Gateway\Blade;

class ParesResponse
{
    //TODO fix the response code
    const MSG_REF_VALIDATION_FAILED = '';
    const MSG_INVALID_PROPERTY = '';

    const PURCHASE          = 'Purchase';
    const XID               = 'xid';
    const TX                = 'TX';
    const CAVV              = 'cavv';
    const CAVVALGORITHM     = 'cavvAlgorithm';
    const STATUS            = 'status';
    const ECI               = 'eci';
    const MERCHANT          = 'Merchant';
    const ACQBIN            = 'acqBIN';
    const MERID             = 'merID';
    const MESSAGE           = 'Message';
    const PARES             = 'PARes';
    const GATEWAY_PARES     = 'PaRes';
}
