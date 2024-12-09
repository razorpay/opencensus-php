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
        Gateway::NETBANKING_FEDERAL,
        Gateway::NETBANKING_HDFC,
        Gateway::NETBANKING_ICICI,
        Gateway::NETBANKING_CANARA,
        Gateway::NETBANKING_KOTAK,
        Gateway::NETBANKING_AXIS,
        Gateway::NETBANKING_SBI,
        Gateway::NETBANKING_RBL,
        Gateway::NETBANKING_IDFC,
        Gateway::NETBANKING_JKB,
        Gateway::ATOM,
        Gateway::NETBANKING_INDUSIND,
        Gateway::NETBANKING_IOB,
        Gateway::NETBANKING_UBI,
        Gateway::NETBANKING_PNB,
        Gateway::NETBANKING_IBK,
        Gateway::NETBANKING_YESB,
        Gateway::CCAVENUE,
        Gateway::NETBANKING_BOB,
        Gateway::NETBANKING_CBI,
        Gateway::NETBANKING_SIB,
        Gateway::NETBANKING_CUB,
        Gateway::NETBANKING_KVB,
        Gateway::NETBANKING_AUSF,
        Gateway::NETBANKING_DLB,
        Gateway::NETBANKING_AIRTEL,
        Gateway::NETBANKING_UCO,
        Gateway::PAYU,
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
        Gateway::UPI_RZPAPB,
        Gateway::UPI_MINDEED,
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
        Gateway::MOBIKWIK,
        Gateway::WALLET_FREECHARGE,
        Gateway::WALLET_OPENWALLET,
        Gateway::WALLET_JIOMONEY,
        Gateway::BAJAJFINSERV,
        Gateway::WALLET_BAJAJ,
        Gateway::PAYPAL
    ];

    const EMI_RECOVER_VIA_ADJUSTMENT_GATEWAY = [
        Gateway::HITACHI,
        Gateway::HDFC,
        Gateway::FULCRUM,
        Gateway::MGPS,
        Gateway::MPGS,
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
