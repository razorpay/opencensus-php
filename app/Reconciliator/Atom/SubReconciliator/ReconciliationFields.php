<?php

namespace RZP\Reconciliator\Atom\SubReconciliator;

class ReconciliationFields
{
    const MERCHANT_NAME         = 'merchant_name';
    const MERCHANT_ID           = 'merchant_id';
    const ATOM_TXN_ID           = ['atom_txn_id', 'paynetz_txn_id'];
    const TXN_STATE             = ['txn_state', 'transaction_type'];
    const TXN_DATE              = ['txn_date', 'transaction_date'];
    const CLIENT_CODE           = 'client_code';
    const MERCHANT_TXN_ID       = ['merchant_txn_id', 'transaction_id'] ;
    const PRODUCT               = ['product', 'product_name'];
    const DISCRIMINATOR         = ['discriminator', 'channal_type'];
    const BANK_CARD_NAME        = ['bank_card_name', 'bank_name'];
    const CARD_TYPE             = 'card_type';
    const CARD_NO               = 'card_no';
    const CARD_ISSUING_BANK     = 'card_issuing_bank';
    const BANK_REF_NO           = ['bank_ref_no', 'bank_conf_no'];
    const REFUND_REF_NO         = 'refund_ref_no';
    const GROSS_TXN_AMOUNT      = ['gross_txn_amount', 'amount'];
    const TXN_CHARGES           = 'txn_charges';
    const SERVICE_TAX           = ['service_tax', 'txn_gst_18','txn_service_taxs'];
    const SB_CESS               = ['sb_cess', 'txn_sb_cess'];
    const KRISHI_KALYAN_CESS    = ['krishi_kalyan_cess', 'txn_krishi_kalyan_cess'];
    const TOTAL_CHARGEABLE      = ['total_chargeable', 'txn_net_chargeable'];
    const NET_AMOUNT_TO_NE_PAID = ['net_amount_to_be_paid', 'sale_amount_to_be_paid'];
    const PAYMENT_STATUS        = 'payment_status';
    const SETTLEMENT_DATE       = 'settlement_date';

    const OLD_TO_NEW_FILE_MAPPING = [
        self::MERCHANT_NAME,
        self::MERCHANT_ID,
        self::ATOM_TXN_ID,
        self::TXN_STATE,
        self::TXN_DATE,
        self::CLIENT_CODE,
        self::MERCHANT_TXN_ID,
        self::PRODUCT,
        self::DISCRIMINATOR,
        self::BANK_CARD_NAME,
        self::CARD_TYPE,
        self::CARD_NO,
        self::CARD_ISSUING_BANK,
        self::BANK_REF_NO,
        self::REFUND_REF_NO,
        self::GROSS_TXN_AMOUNT,
        self::TXN_CHARGES,
        self::SERVICE_TAX,
        self::SB_CESS,
        self::KRISHI_KALYAN_CESS,
        self::TOTAL_CHARGEABLE,
        self::NET_AMOUNT_TO_NE_PAID,
        self::PAYMENT_STATUS,
        self::SETTLEMENT_DATE,
    ];
}
