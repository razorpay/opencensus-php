<?php

namespace RZP\Models\Ledger;

class Constants
{
    //Gateway events
    const GATEWAY_CAPTURED                                  = "payment_gateway_captured";
    const GATEWAY_CAPTURED_COMMISSION                       = "payment_gateway_capture_commission";
    const INR_CURRENCY                                      = "INR";

    //Merchant Capture events
    const MERCHANT_CAPTURED                                 = "payment_merchant_captured";
    const TRANSFER                                          = "transfer_processed";
    const TRANSFER_DEBIT                                    = "transfer_debit";
    const TRANSFER_CREDIT                                   = "transfer_credit";
    const SETTLEMENT_PROCESSED                              = "settlement_processed";
    const AUTOREFUND_PROCESSED                              = "autorefund_processed";
    const TRANSFER_REVERSAL_PROCESSED                       = "transfer_reversal_processed";
    const CREDIT_ACCOUNTING                                 = 'credit_accounting';
    const AMOUNT_CREDITS                                    = 'amount_credits';
    const FEE_CREDITS                                       = 'fee_credits';
    const POSTPAID                                          = 'postpaid';
    const DIRECT_SETTLEMENT_ACCOUNTING                      = 'direct_settlement_accounting';
    const DIRECT_SETTLEMENT                                 = 'direct_settlement';
    const DIRECT_SETTLEMENT_TERMINAL                        = 'direct_settlement_terminal';
    const WITH_REFUND                                       = 'with_refund';
    const WITHOUT_REFUND                                    = 'without_refund';
    const ZERO_AMOUNT_PAYMENT                               = 'zero_amount_payment';
    const ZERO_AMOUNT_ACCOUNTING                            = 'zero_amount_accounting';
    const MERCHANT_BALANCE_ACCOUNTING                       = 'merchant_balance_accounting';
    const BALANCE_DEDUCT                                    = 'balance_deduct';
    const CUSTOMER_WALLET_LOADING                           = "customer_wallet_loading";
    const RAZORPAY_REWARD                                   = 'razorpay_reward';

    //Refund events
    const REFUND_REVERSAL                                   = "refund_reversed";
    const REFUND_PROCESSED                                  = "refund_processed";
    const DISPUTE_REFUND_PROCESSED                          = "dispute_refund_processed";
    const REFUND_INSTANT_PROCESSED                          = "instant_refund_processed";
    const REFUND_PROCESSED_WITH_CREDITS                     = "refund_credits_normal";
    const REFUND_PROCESSED_WITH_CREDITS_INSTANT             = "instant_refund_processed_with_credits";
    const REFUND_REVERSED_CREDITS                           = "refund_reversed_credits";
    const INSTANT_REFUND_REVERSED_CREDITS                   = "instant_refund_reversed_credits";
    const INSTANT_REFUND_REVERSED                           = "instant_refund_reversed";
    const REFUND_ACCOUNTING                                 = "refund_accounting";
    const REFUND_CREDITS                                    = "refund_credits";
    const ACCOUNT_ENTITIES                                  = "account_entities";
    const REFUND_ID                                         = "refund_id";

    const INSTANT_REFUND_PROCESSED_WITH_CREDITS_POSTPAID_MODEL  = "instant_refund_processed_with_credits_postpaid_model";
    const INSTANT_REFUND_PROCESSED_POSTPAID_MODEL               = "instant_refund_processed_postpaid_model";

    //Chargeback Events
    const RAZORPAY_DISPUTE_DEDUCT                           = "razorpay_dispute_deduct";
    const RAZORPAY_DISPUTE_REVERSAL                         = "razorpay_dispute_reversal";

    //Adjustment Events
    const POSITIVE_ADJUSTMENT                           = "positive_adjustment";
    const NEGATIVE_ADJUSTMENT                           = "negative_adjustment";

    //Money Params Fields
    const FEE_CREDITS_DEDUCTIBLE                            = "fee_credits_deductible";
    const REFUND_AMOUNT                                     = "refund_amount";
    const MERCHANT_DEDUCTIBLE_AMOUNT                        = "merchant_deductible_amount";
    const MERCHANT_DEDUCTIBLE_REFUND_CREDITS                = "merchant_deductible_refund_credits";
    const GATEWAY_AMOUNT                                    = "gateway_amount";
    const GATEWAY_DISPUTE_PAYABLE_AMOUNT                    = "gateway_dispute_payable_amount";
    const ADJUSTMENT_AMOUNT                                 = "adjustment_amount";
    const GMV_AMOUNT                                        = "gmv_amount";
    const DS_GMV_AMOUNT                                     = "ds_gmv_amount";
    const DS_CONTROL_AMOUNT                                 = "ds_control_amount";
    const MERCHANT_BALANCE_AMOUNT                           = "merchant_balance_amount";
    const MERCHANT_RECEIVABLE_AMOUNT                        = "merchant_receivable_amount";
    const MERCHANT_PAYABLE_AMOUNT                           = "merchant_payable_amount";
    const CUSTOMER_WALLET_AMOUNT                            = "customer_wallet_amount";
    const GATEWAY_REVERSED_AMOUNT                           = "gateway_reversed_amount";
    const MERCHANT_REFUND_CREDITS_ADDITION                  = "merchant_refund_credits_addition";
    const MERCHANT_SETTLEMENT_AMOUNT                        = "merchant_settlement_amount";
    const CREDIT_AMOUNT                                     = "credit_amount";
    const CREDIT_TYPE                                       = "credit_type";
    const CREDIT_CONTROL_AMOUNT                             = "credit_control_amount";
    const ENTITY_TYPE                                       = "entity_type";
    const CREDIT_BALANCE_TYPE                               = "CREDIT_BALANCE_TYPE";
    const ENTRY_TYPE                                        = "entry_type";
    const ENTRY_TYPE_DEBIT                                  = "debit";
    const ENTRY_TYPE_CREDIT                                 = "credit";
    const RESERVE_BALANCE_AMOUNT                            = "reserve_balance_amount";
    const LEDGER_ENTRY                                      = "ledger_entry";

    //Refund events Direct Settlement
    const REFUND_PROCESSED_DIRECT_SETTLEMENT                = "refund_processed_ds";
    const DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS          = "direct_settlement_instant_refund_credits";
    const DIRECT_SETTLEMENT_INSTANT_REFUND                  = "direct_settlement_instant_refund";
    const DIRECT_SETTLEMENT_NORMAL_REFUND_CREDITS           = "direct_settlement_normal_refund_credits";
    const DIRECT_SETTLEMENT_NORMAL_REFUND                   = "direct_settlement_normal_refund";
    const AUTO_REFUND_DIRECT_SETTLEMENT_NORMAL              = "auto_refund_direct_settlement_normal";
    const AUTO_REFUND_DIRECT_SETTLEMENT_CREDITS_NORMAL      = "auto_refund_direct_settlement_credits_normal";
    const AUTO_REFUND_DIRECT_SETTLEMENT_INSTANT             = "auto_refund_direct_settlement_instant";
    const AUTO_REFUND_DIRECT_SETTLEMENT_CREDITS_INSTANT     = "auto_refund_direct_settlement_credits_instant";
    const AUTOREFUND                                        = "auto_refund";
    const REVERSE_REFUND_ACCOUNTING                         = "reverse_refund_accounting";
    const REVERSED_AMOUNT                                   = "reversed_amount";
    const CUSTOMER_REFUND                                   = "customer_refund";

    //Payload Keys
    const API_TRANSACTION_ID                                = "api_transaction_id";
    const COMMISSION                                        = "commission";
    const TRANSFER_COMMISSION                               = "transfer_commission";
    const TRANSACTION_DATE                                  = "transaction_date";
    const IDENTIFIERS                                       = "identifiers";
    const TRANSACTOR_ID                                     = "transactor_id";
    const TRANSACTOR_EVENT                                  = 'transactor_event';
    const ADDITIONAL_PARAMS                                 = 'additional_params';
    const BASE_AMOUNT                                       = 'base_amount';
    const GATEWAY_COMMISSION                                = "gateway_commission";
    const GATEWAY_TAX                                       = "gateway_tax";
    const LEDGER_INTEGRATION_MODE                           = "ledger_integration_mode";
    const TENANT                                            = "tenant";
    const IDEMPOTENCY_KEY                                   = "idempotency_key";
    const ID                                                = "id";
    const JOURNAL_ID                                        = "journal_id";
    const ADJUSTMENT_ID                                     = "adjustment_id";
    const PAYMENT_ID                                        = "payment_id";
    const API_TXN_ID                                        = "api_txn_id";

    const MONEY_PARAMS                                      = 'money_params';
    const MERCHANT_ID                                       = 'merchant_id';
    const TAX                                               = 'tax';
    const CURRENCY                                          = 'currency';
    const AMOUNT                                            = 'amount';
    const NOTES                                             = 'notes';
    const GATEWAY                                           = 'gateway';
    const JOURNALS                                          = 'journals';
    const SOURCE                                            = "source";

    const REGISTER_EVENT_FOR_LEDGER_TRANSACTION                 = 'register_event_for_ledger_transaction';
    const REGISTER_EVENT_FOR_MULTI_MERCHANT_LEDGER_TRANSACTION  = 'register_event_for_multi_merchant_ledger_transaction';
    const KAFKA_MESSAGE_TASK_NAME                               = 'task_name';
    const KAFKA_MESSAGE_DATA                                    = 'data';
    const CREATE_LEDGER_JOURNAL_EVENT                           = 'create-ledger-journal-event';
    const CREDIT_ID                                             = 'credit_id';

    //Credits loading events
    const MERCHANT_REFUND_CREDIT_LOADING                    = "merchant_refund_credit_loading";
    const MERCHANT_FEE_CREDIT_LOADING                       = "merchant_fee_credit_loading";
    const MERCHANT_RESERVE_BALANCE_LOADING                  = "merchant_reserve_balance_loading";
    const MERCHANT_AMOUNT_CREDIT_LOADING                    = "amount_credit_loading";

    //Reverse Shadow constants
    const MERCHANT_FEE_CREDITS       = 'merchant_fee_credits';
    const MERCHANT_AMOUNT_CREDITS    = 'merchant_amount_credits';
    const MERCHANT_REFUND_CREDITS    = 'merchant_refund_credits';
    const MERCHANT_BALANCE           = 'merchant_balance';
    const MERCHANT_RESERVE_BALANCE   = 'merchant_reserve_balance';
    const MERCHANT_NEGATIVE_BALANCE  = 'merchant_negative_balance';
    const REWARD                     = 'reward';
    const PAYABLE                    = 'payable';
    const BALANCE                    = 'balance';
    const ACCOUNT_TYPE               = 'account_type';
    const FUND_ACCOUNT_TYPE          = 'fund_account_type';
    const ENTITIES                   = 'entities';
    const MIN_BALANCE                = 'min_balance';
    const TENANT_PG                  = 'PG';
    const FEES                       = 'fees';
    const TYPE                       = 'type';
    const SHADOW                     = 'shadow';
    const REVERSE_SHADOW             = 'reverse-shadow';

    const JOURNAL_PAYLOAD               = 'JOURNAL_PAYLOAD';

    const CREATE_TXN_FOR_REFUND_TASK          = 'create_transaction_for_refund';
    const CREATE_REFUND_TXN_API               = 'create-payment-transaction-event';
    const CREATE_TRANSACTION_FOR_ADJUSTMENT   = 'create_transaction_for_adjustment';

    const PRODUCER_KEY  = 'producer_key';
    const TOPIC         = 'topic';
    const MESSAGE       = 'message';

    const IS_CREDIT_LOADING_PAYMENT = "is_credit_loading_payment";

    const FEE_CREDIT        = "fee_credit";
    const REFUND_CREDIT     = "refund_credit";
    const AMOUNT_CREDIT     = "amount_credit";

    const FEE_CREDIT_GMV    = "fee_credit_gmv";
    const REFUND_CREDIT_GMV = "refund_credit_gmv";
    const AMOUNT_CREDIT_GMV = "amount_credit_gmv";
    const GMV_ACCOUNTING    = "gmv_accounting";
}
