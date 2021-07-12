<?php

namespace  RZP\Models\Gateway\Priority;

use RZP\Constants\Mode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;

class Defaults
{
    const GATEWAY_ORDER = [
        Method::CARD => [
            /**
            * These card gateways can be used live and can have direct
            * terminal assignments for the merchant.
            *
            * The order in which we specify them is important because
            * that denotes their preference in our system currently.
            *
            */
            Mode::LIVE => [
                Gateway::FULCRUM,
                Gateway::PAYSECURE,
                Gateway::HDFC,
                Gateway::AXIS_MIGS,
                Gateway::AMEX,
                Gateway::CYBERSOURCE,
                Gateway::FIRST_DATA,
                Gateway::HITACHI,
                Gateway::MPGS,
                Gateway::CARD_FSS,
                Gateway::CHECKOUT_DOT_COM,
            ],

            /**
            * These gateways are only used in test and may or may not graduate to live
            * someday. Although, axis genius was live, we removed it from there
            * because of downtimes and really low success rates.
            * Paytm supports only cards in test mode. Although we are live on paytm
            * on netbanking, but it doesn't support that in test mode.
            */
            Mode::TEST => [
                Gateway::HDFC_DEBIT_EMI,
                Gateway::AXIS_GENIUS,
                Gateway::PAYTM,
                Gateway::ATOM,
                Gateway::SHARP,
                Gateway::PAYU,
                Gateway::CASHFREE,
                Gateway::ZAAKPAY,
                Gateway::CCAVENUE,
            ]
        ],
        Method::NETBANKING => [
            /**
            * Gateways which support netbanking in live mode
            */
            Mode::LIVE => [
                Gateway::BILLDESK,
                Gateway::EBS,
                Gateway::ATOM
            ],
            /**
            * Gateways which support netbanking in test mode
            * Paytm can support live mode as well but we do not want to use
            * it in live for netbanking.
            */
            Mode::TEST => [
                Gateway::PAYTM,
                Gateway::ATOM
            ]
        ]
    ];
}
