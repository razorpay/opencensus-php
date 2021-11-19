<?php


namespace RZP\Models\Dispute;


use RZP\Models\Payment\Gateway;

class RecoveryMethod
{
    const ADJUSTMENT      = "adjustment";
    const REFUND          = "refund";
    const RISK_OPS_REVIEW = "risk_ops_review";

    const NETBANKING_RECOVER_VIA_REFUND_GATEWAYS = [
        Gateway::NETBANKING_HDFC,
        Gateway::NETBANKING_YESB,
        Gateway::NETBANKING_AXIS,
        Gateway::NETBANKING_FEDERAL,
        Gateway::ATOM,
        Gateway::BILLDESK,
        Gateway::NETBANKING_ICICI,
    ];

    const UPI_RECOVER_VIA_ADJUSTMENT_GATEWAYS = [
        Gateway::UPI_AXIS,
        Gateway::UPI_ICICI,
        Gateway::UPI_SBI,
        Gateway::UPI_MINDGATE,
    ];

    const WALLET_RECOVER_VIA_ADJUSTMENT_GATEWAYS = [
        Gateway::WALLET_OLAMONEY,
        Gateway::WALLET_PHONEPE,
        Gateway::MOBIKWIK,
        Gateway::WALLET_PAYZAPP,
    ];

    const WALLET_RECOVER_VIA_REFUND_GATEWAYS = [
        Gateway::BAJAJFINSERV,
        Gateway::WALLET_FREECHARGE,
        Gateway::WALLET_JIOMONEY,
    ];
}