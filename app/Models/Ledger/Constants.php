<?php

namespace RZP\Models\Ledger;

class Constants
{
    //Gateway events
    const GATEWAY_CAPTURED                                  = "payment_gateway_captured";

    //Merchant Capture events
    const MERCHANT_CAPTURED                                 = "payment_merchant_captured";
    const SETTLEMENT_PROCESSED                              = "settlement_processed";
    const AUTOREFUND_PROCESSED                              = "autorefund_processed";
    const CREDIT_ACCOUNTING                                 = 'credit_accounting';
    const AMOUNT_CREDITS                                    = 'amount_credits';
    const FEE_CREDITS                                       = 'fee_credits';
    const POSTPAID                                          = 'postpaid';
    const DIRECT_SETTLEMENT_ACCOUNTING                      = 'direct_settlement_accounting';
    const DIRECT_SETTLEMENT                                 = 'direct_settlement';

    //Refund events
    const REFUND_REVERSAL                                   = "refund_reversed";
    const REFUND_PROCESSED                                  = "refund_processed";
    const REFUND_INSTANT_PROCESSED                          = "instant_refund_processed";
    const REFUND_PROCESSED_WITH_CREDITS                     = "refund_credits_normal";
    const REFUND_PROCESSED_WITH_CREDITS_INSTANT             = "instant_refund_processed_with_credits";
    const REFUND_REVERSED_CREDITS                           = "refund_reversed_credits";
    const INSTANT_REFUND_REVERSED_CREDITS                   = "instant_refund_reversed_credits";
    const INSTANT_REFUND_REVERSED                           = "instant_refund_reversed";
    const REFUND_ACCOUNTING                                 = "refund_accounting";

    //Refund events Direct Settlement
    const REFUND_PROCESSED_DIRECT_SETTLEMENT                = "refund_processed_ds";
    const DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS          = "direct_settlement_instant_refund_credits";
    const DIRECT_SETTLEMENT_INSTANT_REFUND                  = "direct_settlement_instant_refund";
    const DIRECT_SETTLEMENT_NORMAL_REFUND_CREDITS           = "direct_settlement_rzp_normal_refund_credits";
    const DIRECT_SETTLEMENT_NORMAL_REFUND                   = "direct_settlement_rzp_normal_refund";
    const DIRECT_SETTLEMENT_RZP_REFUND_INSTANT_CREDITS      = "direct_settlement_rzp_instant_refund_credits";
    const DIRECT_SETTLEMENT_RZP_REFUND_INSTANT              = "direct_settlement_rzp_instant_refund";
    const AUTOREFUND_DS_WITH_REFUND                         = "autorefund_ds_with_refund";
    const AUTOREFUND_DS_WITHOUT_REFUND                      = "autorefund_ds_without_refund";
    const AUTOREFUND_DS_WITHOUT_REFUND_WITH_CREDITS         = "autorefund_ds_without_refund_with_credits";
    const AUTOREFUND                                        = "autorefund";
    const REVERSE_REFUND_ACCOUNTING                         = 'reverse_refund_accounting';

    //Payload Keys
    const API_TRANSACTION_ID                                = "api_transaction_id";
    const COMMISSION                                        = "commission";
    const TRANSACTION_DATE                                  = "transaction_date";
    const IDENTIFIERS                                       = "identifiers";
    const TRANSACTOR_ID                                     = "transactor_id";
    const TRANSACTOR_EVENT                                  = 'transactor_event';
    const ADDITIONAL_PARAMS                                 = 'additional_params';
    const BASE_AMOUNT                                       = 'base_amount';
    const MERCHANT_ID                                       = 'merchant_id';
    const TAX                                               = 'tax';
    const CURRENCY                                          = 'currency';
    const AMOUNT                                            = 'amount';
    const NOTES                                             = 'notes';
    const GATEWAY                                           = 'gateway';

    const REGISTER_EVENT_FOR_LEDGER_TRANSACTION             = 'register_event_for_ledger_transaction';
    const KAFKA_MESSAGE_TASK_NAME                           = 'task_name';
    const KAFKA_MESSAGE_DATA                                = 'data';
    const CREATE_LEDGER_JOURNAL_EVENT                       = 'create-ledger-journal-event';

}
