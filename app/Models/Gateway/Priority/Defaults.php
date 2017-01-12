<?php

namespace  RZP\Models\Gateway\Priority;

use RZP\Models\Payment\Gateway;

class Defaults
{
    /**
     * These card gateways can be used live and can have direct
     * terminal assignments for the merchant.
     *
     * The order in which we specify them is important because
     * that denotes their preference in our system currently.
     *
     * @var array
     */
    public static $directCardGatewaysOrder = [
        Gateway::HDFC,
        Gateway::AXIS_MIGS,
        Gateway::AMEX,
        Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA,
    ];

    /**
     * These gateways are only used in test and may or may not graduate to live
     * someday. Although, axis genius was live, we removed it from there
     * because of downtimes and really low success rates.
     * Paytm supports only cards in test mode. Although we are live on paytm
     * on netbanking, but it doesn't support that in test mode.
     *
     * @var array
     */
    public static $directCardGatewaysInTestOrder = [
        Gateway::AXIS_GENIUS,
        Gateway::PAYTM,
        Gateway::ATOM,
        Gateway::SHARP,
        Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA,
    ];

    /**
     * Gateways which support netbanking in live mode
     *
     * @var array
     */
    public static $directNetbankingGatewaysOrder = [
        Gateway::BILLDESK,
        Gateway::EBS
    ];

    /**
     * Gateways which support netbanking in test mode
     * Paytm can support live mode as well but we do not want to use
     * it in live for netbanking.
     *
     * @var array
     */
    public static $directNetbankingGatewaysInTestOrder = [
        Gateway::PAYTM,
        Gateway::ATOM
    ];
}
