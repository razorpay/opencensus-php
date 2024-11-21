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
    const ACCOUNTING                                        = "accounting";
    const AMOUNT_CREDITS                                    = 'amount_credits';
    const AMOUNT_CREDITS_REDEMPTION                         = 'amount_credits_redemption';
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
    const RAZORPAY_REWARDS                                  = 'razorpay_rewards';
    const DFB_AMOUNT_CREDITS                                = 'dfb_amount_credits';
    const VAS_MERCHANT_FLOW                                 = "vas_merchant_flow";
    const HDFC_VAS_DS_CFB_SURCHARGE_FLOW                    = "hdfc_vas_ds_cfb_surcharge_flow";
    const HDFC_NON_DS_SURCHARGE_FLOW                        = "hdfc_non_ds_surcharge_flow";

    //Refund events
    const REFUND_REVERSAL                                   = "refund_reversed";
    const REFUND_PROCESSED                                  = "refund_processed";
    const DISPUTE_REFUND_PROCESSED                          = "dispute_refund_processed";
    const REFUND_INSTANT_PROCESSED                          = "instant_refund_processed";
    const REFUND_PROCESSED_WITH_CREDITS                     = "refund_credits_normal";
    const REFUND_PROCESSED_WITH_CREDITS_INSTANT             = "instant_refund_processed_with_credits";
    const REFUND_REVERSED_CREDITS                           = "refund_reversed_credits";
    const INSTANT_REFUND_REVERSED_CREDITS                   = "instant_refund_reversed_credits";
    const INSTANT_REFUND_REVERSED_POSTPAID                  = "instant_refund_reversed_postpaid";
    const INSTANT_REFUND_REVERSED                           = "instant_refund_reversed";
    const REFUND_ACCOUNTING                                 = "refund_accounting";
    const REFUND_CREDITS                                    = "refund_credits";
    const ACCOUNT_ENTITIES                                  = "account_entities";
    const REFUND_ID                                         = "refund_id";
    const DISCOUNT                                          = "discount";

    const INSTANT_REFUND_REVERSED_POSTPAID_BALANCE_COMPLETE         = "instant_refund_reversed_postpaid_balance_complete";
    const INSTANT_REFUND_REVERSED_POSTPAID_CREDITS_COMPLETE         = "instant_refund_reversed_postpaid_credits_complete";

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
    const REFUND_CREDITS_AMOUNT                             = "refund_credits_amount";
    const FEE_CREDITS_AMOUNT                                = "fee_credits_amount";
    const CREDIT_TYPE                                       = "credit_type";
    const CREDIT_CONTROL_AMOUNT                             = "credit_control_amount";
    const ENTITY_TYPE                                       = "entity_type";
    const CREDIT_BALANCE_TYPE                               = "CREDIT_BALANCE_TYPE";
    const ENTRY_TYPE                                        = "entry_type";
    const ENTRY_TYPE_DEBIT                                  = "debit";
    const ENTRY_TYPE_CREDIT                                 = "credit";
    const RESERVE_BALANCE_AMOUNT                            = "reserve_balance_amount";
    const RESERVE_BALANCE_CONTROL_AMOUNT                    = "reserve_balance_control_amount";
    const LEDGER_ENTRY                                      = "ledger_entry";

    // Fee Types
    const UPI_INAPP                                         = "upi_inapp";
    const OPTIMIZER                                         = "optimizer";
    const ESAUTOMATIC                                       = "esautomatic";
    const RECURRING                                         = "recurring";
    const MAGIC_CHECKOUT                                    = "magic_checkout";
    const PARTNER_COMMISSION                                = "commission_payment";

    // Tax Types
    const PARTNER_TAX = "commission_tax";

    // Ledger Config Formulas for Fee Types
    const UPI_INAPP_FORMULA                              = "upi_inapp_commission";
    const OPTIMIZER_FORMULA                              = "optimizer_commission";
    const ESAUTOMATIC_FORMULA                            = "esautomatic_commission";
    const RECURRING_FORMULA                              = "recurring_commission";
    const MAGIC_CHECKOUT_FORMULA                         = "magic_checkout_commission";
    const PARTNER_COMMISSION_FORMULA                     = "partner_commission";

    // Ledger Config Formulas for Tax Types
    const PARTNER_TAX_FORMULA                            = "partner_tax";

    const FEE_AND_TAX_FORMULAS = [
        self::UPI_INAPP_FORMULA,
        self::OPTIMIZER_FORMULA,
        self::ESAUTOMATIC_FORMULA,
        self::RECURRING_FORMULA,
        self::MAGIC_CHECKOUT_FORMULA,
        self::PARTNER_COMMISSION_FORMULA,
        self::PARTNER_TAX_FORMULA
    ];

    const FEE_TYPE_VS_FORMULAS = [
        self::UPI_INAPP => self::UPI_INAPP_FORMULA,
        self::OPTIMIZER => self::OPTIMIZER_FORMULA,
        self::ESAUTOMATIC => self::ESAUTOMATIC_FORMULA,
        self::RECURRING => self::RECURRING_FORMULA,
        self::MAGIC_CHECKOUT => self::MAGIC_CHECKOUT_FORMULA,
        self::PARTNER_COMMISSION => self::PARTNER_COMMISSION_FORMULA
    ];


    const UPI_INAPP_COMMISSION                              = "upi_inapp_commission";
    const OPTIMIZER_COMMISSION                              = "optimizer_commission";
    const ESAUTOMATIC_COMMISSION                            = "esautomatic_commission";
    const RECURRING_COMMISSION                              = "recurring_commission";
    const MAGIC_CHECKOUT_COMMISSION                         = "magic_checkout_commission";
    const PARTNER_COMMISSION_FUND_ACCOUNT                   = "partner_commission";

    // Ledger Config Formulas for Tax Types
    const PARTNER_TAX_FUND_ACCOUNT                          = "partner_tax";

    const TAX_TYPE_VS_FORMULAS = [
        self::PARTNER_TAX => self::PARTNER_TAX_FORMULA
    ];

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

    const TRANSACTOR_AMOUNT                                 = "transactor_amount";
    const MERCHANT_BALANCE_LIMIT                            = "merchant_balance_limit";
    const MERCHANT_VAS_AMOUNT                               = "merchant_vas_amount";
    const TRANSACTION_ID                                    = "transaction_id";
    const METADATA                                          = "metadata";
    const SETTLEMENT_ONDEMAND_PAYOUT_ID                     = "settlement_ondemand_payout_id";
    const REASON                                            = "reason";
    const GATEWAY_ACQUIRER_AMOUNT                           = "gateway_acquirer_amount";

    const MONEY_PARAMS                                      = 'money_params';
    const MERCHANT_ID                                       = 'merchant_id';
    const TAX                                               = 'tax';
    const CURRENCY                                          = 'currency';
    const AMOUNT                                            = 'amount';
    const NOTES                                             = 'notes';
    const GATEWAY                                           = 'gateway';
    const JOURNALS                                          = 'journals';
    const SOURCE                                            = "source";
    const FEE                                               = "fee";

    const REGISTER_EVENT_FOR_LEDGER_TRANSACTION                 = 'register_event_for_ledger_transaction';
    const REGISTER_EVENT_FOR_MULTI_MERCHANT_LEDGER_TRANSACTION  = 'register_event_for_multi_merchant_ledger_transaction';
    const KAFKA_MESSAGE_TASK_NAME                               = 'task_name';
    const KAFKA_MESSAGE_DATA                                    = 'data';
    const CREATE_LEDGER_JOURNAL_EVENT                           = 'create-ledger-journal-event';
    const CREDIT_ID                                             = 'credit_id';
    const EXPIRED_AT                                            = 'expired_at';

    const RESERVE_BALANCE_ID                                    = 'reserve_balance_id';

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
    const MERCHANT_VA_MERCHANT       = 'merchant_va_merchant';

    const FUND_ACCOUNT_TYPE_CUSTOMER_WALLET = 'customer_wallet';

    const MERCHANT_VAS_ACCOUNT       = 'merchant_vas_account';

    const MERCHANT_GMV               = 'merchant_gmv';
    const MERCHANT_ONDEMAND_SETTLEMENT_LEDGER = 'merchant_ondemand_settlement';
    const REWARD                     = 'reward';
    const REWARD_CREDITS             = 'reward_credits';
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
    const LIABILITY                  = "liability";

    const ACCOUNT_DISCOVERY_CONFIG   = "account_discovery_config";
    const ACCOUNT_CATEGORY           = "account_category";
    const DYNAMIC_IDENTIFIERS        = "dynamic_identifiers";
    const DYNAMIC_MONEY_PARAMS       = "dynamic_money_params";

    const JOURNAL_PAYLOAD               = 'JOURNAL_PAYLOAD';

    const CREATE_TXN_FOR_REFUND_TASK               = 'create_transaction_for_refund';
    const CREATE_REFUND_TXN_API                    = 'create-payment-transaction-event';
    const CREATE_TRANSACTION_FOR_ADJUSTMENT        = 'create_transaction_for_adjustment';
    const CREATE_TRANSACTION_FOR_DIRECT_TRANSFER   = 'create_transaction_for_direct_transfer';
    const CREATE_TRANSACTION_FOR_TRANSFER_REVERSAL = 'create_transaction_for_transfer_reversal';
    const LIVE_ART_EVENTS                          = 'live_art_events'; // check if to add prod_

    const PRODUCER_KEY  = 'producer_key';
    const TOPIC         = 'topic';
    const MESSAGE       = 'message';

    const IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT = "is_credit_or_reserve_balance_loading_payment";

    const FEE_CREDIT        = "fee_credit";
    const REFUND_CREDIT     = "refund_credit";
    const AMOUNT_CREDIT     = "amount_credit";
    const RESERVE_BALANCE   = "reserve_balance";

    const FEE_CREDIT_GMV      = "fee_credit_gmv";
    const REFUND_CREDIT_GMV   = "refund_credit_gmv";
    const RESERVE_BALANCE_GMV = "reserve_balance_gmv";
    const AMOUNT_CREDIT_GMV   = "amount_credit_gmv";
    const GMV_ACCOUNTING      = "gmv_accounting";
    const FEE_BREAKUP         = "fee_breakup";
    const TRUE                = "true";

    //Transfers
    const TRANSFER_ID           = 'transfer_id';
    const DEBIT_TRANSACTION_ID  = 'debit_transaction_id';
    const CREDIT_TRANSACTION_ID = 'credit_transaction_id';

    const REVERSAL_ID           = 'reversal_id';
    const DUMMY_REFUND_ID       = 'refund_id';
    const CUSTOMER_REFUND_ID    = 'customer_refund_id';


    const CREDITS_PREFIX    = "credits_";
    const DATA              = "data";
    const ENTITY            = "entity";
    const NAMESPACE         = "namespace";
    const EXPIRY_AMOUNT     = "expiry_amount";
    const ENTITY_ID         = "entity_id";

    const AMOUNT_CREDITS_EXPIRY_EVENT   = "amount_credits_expiry";

    const RESPONSE   = "response";

    const LEDGER_ONDEMAND_SETTLEMENT_AMOUNT = "ondemand_settlement_amount";
    const LEDGER_ONDEMAND_SETTLEMENT_FEE = "ondemand_settlement_fee";
    const LEDGER_ONDEMAND_SETTLEMENT_TAX = "ondemand_settlement_tax";

    const LEDGER_ONDEMAND_SETTLEMENT_PROCESSED = "ondemand_settlement_processed";
	const LEDGER_ONDEMAND_SETTLEMENT_REVERSED  = "ondemand_settlement_reversed";

    const LEDGER_ONDEMAND_PROCESSED_TRANSACTOR_ID_PREFIX = "setlod_";
    const LEDGER_ONDEMAND_REVERSED_TRANSACTOR_ID_PREFIX  = "setlodrvrsl_";

    const IS_AMOUNT_VALID = "is_amount_valid";

    const LINKED_ACCOUNT_MERCHANT_ID = 'linked_account_merchant_id';
    const BALANCE_TYPE               = 'balance_type';

    const MERCHANT_BALANCE_FUND_ACCOUNT                = 'merchant_balance';

    const CREATED_AT                                   = 'created_at';

    const UPDATED_AT                                   = 'updated_at';

    const RZP_COMMISSION                               = 'rzp_commission';

    const RZP_TRANSFER_FEE                             = 'rzp_transfer_fee';

    const RZP_GST                                      = 'rzp_gst';

    const MERCHANT_ONDEMAND_GST                        = 'merchant_ondemand_settlement_gst';

    const ONDEMAND_INCOME                              = 'merchant_ondemand_settlement_income';

    const RECEIVABLE                                   = 'receivable';

    const CASH                                         = 'cash';

    const BALANCE_UPDATED                              = 'balance_updated';

    const MERCHANT_INVOICE                             = 'merchant_invoice';

    const SUCCESSFUL_ENTRIES_COUNT                     =  'successful_entries_count';

    const SUCCESSFUL_IDS                               =  'successful_ids';

    const FAILED_ENTRIES_COUNT                         =  'failed_entries_count';

    const FAILED_IDS                                   =  'failed_ids';

    const MERCHANT_RESERVE_BALANCE_WITHDRAWAL          = 'merchant_reserve_balance_withdrawal';

    const MERCHANT_REFUND_CREDIT_WITHDRAWAL            = 'merchant_refund_credit_withdrawal';

    const MERCHANT_FEE_CREDIT_WITHDRAWAL               = 'merchant_fee_credit_withdrawal';
}
