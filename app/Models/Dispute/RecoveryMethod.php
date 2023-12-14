<?php


namespace RZP\Models\Dispute;


use RZP\Models\Payment\Gateway;

class RecoveryMethod
{
    const ADJUSTMENT       = "adjustment";
    const REFUND           = "refund";
    const RISK_OPS_REVIEW  = "risk_ops_review";
    const REFUNDED_PAYMENT = 'refunded_payment';

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
        Gateway::UPI_AIRTEL,
        Gateway::UPI_CITI,
        Gateway::UPI_KOTAK,
        Gateway::UPI_YESBANK,
        Gateway::UPI_RBL,
        Gateway::UPI_JUSPAY,
    ];

    const WALLET_RECOVER_VIA_ADJUSTMENT_GATEWAYS = [
        Gateway::WALLET_OLAMONEY,
        Gateway::WALLET_PHONEPE,
        Gateway::WALLET_PAYZAPP,
        Gateway::WALLET_AMAZONPAY,
        Gateway::WALLET_PHONEPESWITCH,
        Gateway::WALLET_AIRTELMONEY
    ];

    const WALLET_RECOVER_VIA_REFUND_GATEWAYS = [
        Gateway::BAJAJFINSERV,
        Gateway::WALLET_FREECHARGE,
        Gateway::WALLET_JIOMONEY,
        Gateway::MOBIKWIK,
        Gateway::WALLET_OPENWALLET,
        Gateway::WALLET_BAJAJ,
        Gateway::PAYPAL
    ];

    const EMI_RECOVER_VIA_ADJUSTMENT_GATEWAY = [
        Gateway::HITACHI,
        Gateway::HDFC,
        Gateway::FULCRUM,
        Gateway::MGPS,
        Gateway::FIRST_DATA,
        Gateway::HDFC_DEBIT_EMI,
        Gateway::CYBERSOURCE,
        Gateway::CARD_FSS,
        Gateway::AXIS_MIGS,
        Gateway::PAYSECURE,
        Gateway::INDUSIND_DEBIT_EMI,
        Gateway::ISG,
        Gateway::BAJAJFINSERV
    ];

    const PAYLATER_RECOVER_VIA_ADJUSTMENT_GATEWAYS = [
        Gateway::PAYLATER
    ];
}
