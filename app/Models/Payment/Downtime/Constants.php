<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Constants
{
    /**
     * Map of method vs field which is being used as downtime instrument
     */
    const METHOD_QUERY_MAP = [
        Method::CARD       => Entity::NETWORK,
        Method::NETBANKING => Entity::ISSUER,
        Method::WALLET     => Entity::ISSUER,
        Method::UPI        => Entity::VPA_HANDLE,
        Method::EMANDATE   => Entity::ISSUER,
        Method::FPX        => Entity::ISSUER
    ];

    // List of active UPI gateways that are being used
    const UPI_GATEWAYS = [
        Gateway::UPI_AXIS,
        Gateway::UPI_ICICI,
        Gateway::UPI_MINDGATE,
        Gateway::UPI_SBI,
    ];

    // List of active card gateways that are being used
    const CARD_GATEWAYS = [
        Gateway::AMEX,
        Gateway::AXIS_MIGS,
        Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA,
        Gateway::HDFC,
        Gateway::HITACHI,
    ];

    // Constants used For email notification
    const CREATED   = 'CREATED';
    const RESOLVED  = 'RESOLVED';
    const SECONDS_IN_A_DAY = 86400;
    const HISTORY_REFRESH_BATCH_SIZE = 7;
    const MAX_LOOKBACK_PERIOD        = 30;
    const DOWNTIMES_EMAIL_CC = 'DOWNTIMES_EMAIL_CC_';

    // Send Merchant Downtimes Razorx
    const WEBHOOKS = 'WEBHOOKS';
    const FETCH_API = 'FETCH_API';
    const EMAILS = 'EMAILS';

    public static function getMethodQueryInstrument($method)
    {
        switch ($method)
        {
            case Method::CARD :
                return [Entity::NETWORK, Entity::ISSUER];
                break;
            case Method::EMANDATE:
            case Method::NETBANKING :
            case Method::WALLET :
            case Method::FPX :
                return  [Entity::ISSUER];
                break;
            case Method::UPI :
                return [Entity::VPA_HANDLE, Entity::ISSUER, Entity::PSP];
                break;
        }
    }
}
