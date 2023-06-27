<?php

namespace RZP\Models\Payment;

use App;
use Route;
use Cache;
use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Base;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Emi\CardlessEmiProvider;
use RZP\Models\Vpa;
use RZP\Diag\EventCode;
use Razorpay\IFSC\IFSC;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Currency\Currency;
use Illuminate\Validation\Concerns;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Exception\BadRequestException;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Models\Payment\Processor\UpiTrait;
use RZP\Models\Payment\Processor\Constants;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Currency\Core as CurrencyCore;

class Validator extends Base\Validator
{

    use UpiTrait;
    use Concerns\ValidatesAttributes;

    protected $trace;

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    /**
     * recurring_token epoch constrains :
     * min : Sat Jan  1 05:30:00 IST 2000 => 946684800
     * max : 17 August 292278994 => 9223372036854775807 - max for 64 bit signed int
     **/

    protected static $createRules = [
        'validate_payment'              => 'sometimes|associative_array',
        'validate_payment.afa_required' => 'sometimes|boolean',
        'acs_afa_authentication'        => 'sometimes|associative_array',
        'amount'                        => 'required|integer',
        'currency'                      => 'required|string|size:3|custom',
        'method'                        => 'required|string',
        'vpa'                           => 'sometimes_if:method,upi|string|filled|max:100|custom',
        'aadhaar'                       => 'required_if:method,aeps|array',
        'aadhaar.number'                => 'required_if:method,aeps|size:12|string',
        'aadhaar.fingerprint'           => 'required_if:method,aeps|max:999|string',
        'aadhaar.session_key'           => 'sometimes_if:method,aeps|size:344|string',
        'aadhaar.hmac'                  => 'sometimes_if:method,aeps|size:64|string',
        'aadhaar.cert_expiry'           => 'sometimes_if:method,aeps|size:8|string',
        'card'                          => 'sometimes',
        'bank'                          => 'required_if:method,netbanking,aeps,emandate|string|between:4,6',
        'wallet'                        => 'required_if:method,wallet|custom',
        'emi_duration'                  => 'required_if:method,emi|integer|in:2,3,6,9,12,18,24',
        'description'                   => 'sometimes|nullable|string|max:255|utf8',
        'email'                         => 'sometimes|nullable|email',
        'upi.vpa'                       => 'sometimes_if:method,upi|filled|string',
        'upi.type'                      => 'sometimes_if:method,upi|filled|string',
        'upi.flow'                      => 'sometimes_if:method,upi|filled|string',
        'upi.start_time'                => 'sometimes_if:method,upi|filled|epoch',
        'upi.end_time'                  => 'sometimes_if:method,upi|filled|epoch',
        'upi_provider'                  => 'sometimes_if:method,upi|filled|string|custom',
        'contact'                       => 'sometimes|nullable|contact_syntax',
        'billing_address'               => 'sometimes',
        'signature'                     => 'sometimes|nullable|string',
        'notes'                         => 'sometimes|notes',
        'notes.merchant_order_id'       => 'required_with:signature',
        'callback_url'                  => 'sometimes|filled',
        'order_id'                      => 'sometimes|filled',
        'customer_id'                   => 'sometimes|public_id|filled',
        'subscription_id'               => 'sometimes|public_id',
        'receiver'                      => 'sometimes_if:method,card,upi,bank_transfer,offline|associative_array|filled|custom',
        'receiver.type'                 => 'required_with:receiver|filled|string',
        'receiver.id'                   => 'required_if:receiver,vpa,qr_code,bank_transfer|filled|public_id',
        'payment_link_id'               => 'sometimes|public_id|size:17',
        'token'                         => 'sometimes|string',
        'save'                          => 'sometimes|in:0,1',
        'recurring'                     => 'sometimes|in:1,preferred,auto',
        'fee'                           => 'sometimes|filled|integer|max:50000000',
        Entity::TAX                     => 'sometimes|filled|integer|max:50000000',
        'on_hold'                       => 'sometimes_if:method,transfer|boolean',
        'on_hold_until'                 => 'sometimes_if:method,transfer|nullable|epoch',
        'ip'                            => 'sometimes|ip',
        'referer'                       => 'sometimes|string|max:2083',
        'user_agent'                    => 'sometimes|string',
        '_'                             => 'sometimes|array',
        'test_success'                  => 'sometimes|boolean',
        'subscription_card_change'      => 'sometimes|boolean',
        'capture'                       => 'sometimes|boolean',
        'upi'                           => 'sometimes_if:method,upi|array',
        'upi.expiry_time'               => 'sometimes_if:method,upi|integer|between:5,5760|filled',
        'auth_type'                     => 'sometimes_if:method,emandate,card,emi,nach|string|max:20|filled',
        'preferred_auth'                => 'sometimes_if:method,card,emi|array|max:3|filled',
        'bank_account'                  => 'sometimes_if:method,emandate|associative_array|filled',
        'bank_account.account_number'   => 'required_with:bank_account|filled|alpha_num|between:5,20',
        'bank_account.ifsc'             => 'required_with:bank_account|filled|alpha_num|size:11',
        'bank_account.name'             => 'required_with:bank_account|filled|alpha_space_num|between:4,120',
        'recurring_token'               => 'sometimes_if:method,emandate,upi,card|associative_array|filled',
        'recurring_token.notification_id' => 'sometimes_if:method,card|public_id',
        'recurring_token.max_amount'    => 'sometimes_if:method,emandate,upi|filled|integer|min:500',
        'recurring_token.expire_by'     => 'sometimes_if:method,emandate,upi|filled|epoch:946684800,9223372036854775807',
        'nach'                          => 'sometimes_if:method,nach|associative_array',
        'nach.signed_form'              => 'sometimes_if:method,nach|file',
        'offer_id'                      => 'filled|public_id|size:20',
        'provider'                      => 'required_if:method,cardless_emi,paylater,app|string',
        'ott'                           => 'sometimes_if:method,cardless_emi,paylater|string',
        'payment_id'                    => 'sometimes_if:method,cardless_emi,paylater',
        'application'                   => 'sometimes|filled|string|in:google_pay,visasafeclick',
        'device'                        => 'sometimes',
        'currency_request_id'           => 'required_with:dcc_currency|string',
        'mcc_request_id'                => 'sometimes|string',
        'dcc_currency'                  => 'required_with:currency_request_id|string|max:3|custom',
        'charge_account'                => 'sometimes|string',
        'app_present'                   => 'sometimes_if:method,app|boolean',
        'force_terminal_id'             => 'sometimes|string|size:19|custom', // term_<14digitid>
        'language_code'                 => 'sometimes|string',
        'meta'                          => 'sometimes|array',
        'authentication'                => 'required_if:application,visasafeclick|array',
        'reward_ids'                    => 'sometimes|array',
        'wallet_user_id'                => 'filled|unsigned_id',
        'user_consent_for_tokenisation' => 'sometimes|in:0,1', // temporary - for taking saved card consent till Dec 31st 2021
        'consent_to_save_card'          => 'sometimes|in:0,1',
        'authentication.cavv'                                        => 'required_if:application,visasafeclick|size:28|string',
        'authentication.cavv_algorithm'                              => 'required_if:application,visasafeclick|size:1|string',
        'authentication.eci'                                         => 'required_if:application,visasafeclick|max:2|string',
        'authentication.xid'                                         => 'required_if:application,visasafeclick|max:28|string',
        'authentication.enrolled_status'                             => 'sometimes_if:application,visasafeclick|size:1|string',
        'authentication.authentication_status'                       => 'sometimes_if:application,visasafeclick|size:1|string',
        'authentication.provider_data'                               => 'required_if:application,visasafeclick|array',
        'authentication.provider_data.product_type'                  => 'sometimes_if:application,visasafeclick|max:10|string',
        'authentication.provider_data.auth_type'                     => 'required_if:application,visasafeclick|string|max:10',
        'authentication.provider_data.product_transaction_id'        => 'sometimes_if:application,visasafeclick|max:60|string',
        'authentication.provider_data.product_merchant_reference_id' => 'sometimes_if:application,visasafeclick|max:48|string',
        'authentication.authentication_channel'                      => 'sometimes_if:method,card|string|in:browser',
        'browser'                                                    => 'sometimes|array',
        'browser.java_enabled'                                       => 'sometimes|boolean',
        'browser.language'                                           => 'sometimes|string',
        'browser.javascript_enabled'                                 => 'sometimes|boolean',
        'browser.timezone_offset'                                    => 'sometimes|integer',
        'browser.color_depth'                                        => 'sometimes|integer',
        'browser.screen_width'                                       => 'sometimes|integer',
        'browser.screen_height'                                      => 'sometimes|integer',
        'network_transaction_id'                                     => 'sometimes',
        'payer_account_type'                                         => 'sometimes_if:method,upi|nullable|string|max:20',

    ];

    protected static $editAcquirerRules = [
        Entity::VPA                  => 'sometimes|string|max:100',
        Entity::REFERENCE1           => 'sometimes|nullable|string',
        Entity::REFERENCE2           => 'sometimes|nullable|string',
        Entity::REFERENCE16          => 'sometimes|nullable|string',
        Entity::REFERENCE17          => 'sometimes|nullable|string',
    ];

    protected static $posCreateRules = [
        'status'                       => 'required|string',
        'amount'                       => 'required|integer',
        'receiver_type'                => 'required|string',
        'currency'                     => 'required|string|size:3',
        'receiver'                     => 'required|string',
        'method'                       => 'required|string',
        'contact'                      => 'sometimes|string',
        'email'                        => 'sometimes|email',
        'reference1'                   => 'sometimes|string',
        'reference2'                   => 'sometimes|string',
        'meta'                         => 'required|associative_array',
        'meta.reference_id'            => 'required|string',
        'notes'                        => 'sometimes|array',
        'notes.*.external_ref_id1'     => 'sometimes|string',
        'notes.*.external_ref_id2'     => 'sometimes|string',
        'notes.*.external_ref_id3'     => 'sometimes|string',
        'notes.*.external_ref_id4'     => 'sometimes|string',
        'notes.*.external_ref_id5'     => 'sometimes|string',
        'notes.*.external_ref_id6'     => 'sometimes|string',
        'notes.*.external_ref_id7'     => 'sometimes|string',
        'notes.*.change_slip_date'     => 'sometimes|string',
        'notes.*.receipt_url'          => 'sometimes|string',
        'notes.*.tid'                  => 'sometimes|string',
        'card'                         => 'sometimes|array',
        'errorCode'                    => 'sometimes|string',
        'error_description'            => 'sometimes|string',
    ];

    protected static $editCpsResponseRules = [
        Entity::AUTH_TYPE               => 'sometimes|nullable|string',
        Entity::AUTHENTICATION_GATEWAY  => 'sometimes|nullable|string',
        Entity::REFERENCE2              => 'sometimes|nullable|string',
        Entity::TWO_FACTOR_AUTH         => 'sometimes|nullable|string',
        Entity::REFERENCE17             => 'sometimes|nullable|string',
        Entity::REFERENCE16             => 'sometimes|nullable|string',
    ];

    protected static $editRules = [
        Entity::NOTES                => 'sometimes|notes',
    ];

    protected static $captureRules = [
        Entity::AMOUNT               => 'required|integer',
        Entity::CURRENCY             => 'required|custom',
    ];

    protected static $otpGenerateRules = [
        'track_id'       => 'sometimes|string',
        'action'         => 'sometimes|in:' . Constants::ACTION_OTP_RESEND,
    ];


    protected static $bulkCaptureRules = [
        'payment_ids'                => 'required|sequential_array',
        'payment_ids.*'              => 'required|public_id',
    ];

    protected static $authorizePaymentRules = [
        'meta'                       => 'sometimes',
        'recurring_token'            => 'sometimes|associative_array',
        'recurring_token.max_amount' => 'sometimes|integer|min:1|mysql_signed_int',
        'recurring_token.expire_by'  => 'sometimes|epoch:946684800,9223372036854775807',
        'recurring_token.debit_type' => 'sometimes|in:fixed_amount,variable_amount',
        'recurring_token.frequency'  => 'sometimes|in:daily,weekly,monthly,quarterly,yearly,bi_monthly,bi_yearly,as_presented',
        'recurring_token.notes'      => 'sometimes|notes',
    ];

    protected static $bulkGatewayCaptureRules = [
        'payment_ids'                => 'sometimes|sequential_array',
        'payment_ids.*'              => 'required|public_id',
    ];

    protected static $verifyRules = [
        'bucket'                     => 'sometimes|sequential_array',
        'bucket.*'                   => 'sometimes|integer|max:7',
        'gateway'                    => 'sometimes|string|max:50'
    ];

    protected static $verifyAllRules = [
        'gateway'                        => 'sometimes|string|max:50',
        'delay'                          => 'sometimes|integer|max:2592000',
        'count'                          => 'sometimes|integer|max:10000',
        'bucket'                         => 'sometimes|sequential_array',
        'use_slave'                      => 'sometimes|boolean',
        'filter_payment_pushed_to_kafka' => 'sometimes|boolean',
    ];

    protected static $bulkVerifyRules = [
        'payment_ids'                => 'required|sequential_array',
        'payment_ids.*'              => 'required|public_id',
    ];

    protected static $bulkUpdateRefundAtRules = [
        'payments'                   => 'required|sequential_array',
        'payments.*.id'              => 'required|public_id',
        'payments.*.refund_at'       => 'present|epoch|nullable',
    ];

    protected static $refundRules = [
        'amount'                     => 'sometimes|integer',
        'notes'                      => 'sometimes|notes',
        'reverse_all'                => 'sometimes|boolean',
        'reversals'                  => 'sometimes|array',
        'reversals.*.transfer'       => 'required|public_id',
        'reversals.*.amount'         => 'required|integer|min:100',
        'reversals.*.notes'          => 'sometimes|notes',
    ];

    protected static $transferRules = [
        'transfers'                        => 'required|array',
        'transfers.*.customer'             => 'sometimes|public_id',
        'transfers.*.account'              => 'sometimes|public_id',
        'transfers.*.account_code'         => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
        'transfers.*.amount'               => 'required|integer|min:100',
        'transfers.*.currency'             => 'required|string|size:3',
        'transfers.*.notes'                => 'sometimes|notes',
        'transfers.*.linked_account_notes' => 'sometimes|array',
        'transfers.*.on_hold'              => 'sometimes|boolean',
        'transfers.*.on_hold_until'        => 'sometimes|epoch',
    ];

    protected static $getFlowsRules = [
        'callback'                  => 'sometimes', // JSONP
        'iin'                       => 'sometimes|nullable|numeric|digits:6',
        '_'                         => 'sometimes|array',
        'order_id'                  => 'sometimes|filled',
        'currency'                  => 'sometimes|string|size:3',
        'amount'                    => 'sometimes|integer',
        'token'                     => 'sometimes|string|max:20',
        'language_code'             => 'sometimes',
        'wallet'                    => 'sometimes|string',
        'provider'                  => 'sometimes|string'
    ];

    protected static $postFlowsRules = [
        'card_number'        => 'sometimes|numeric|luhn|digits_between:12,19',
        'iin'                => 'sometimes|numeric|digits:6',
        'currency'           => 'sometimes|string|size:3',
        'amount'             => 'sometimes|integer',
        'token'              => 'sometimes|string|max:20',
        'wallet'             => 'sometimes|string',
        'provider'           => 'sometimes|string'
    ];

    protected static array $paymentsDualWriteSyncRules = [
        'payment_ids'                       => 'sometimes|sequential_array|max:1000',
        'payment_ids.*'                     => 'sometimes|string|size:14',
        'time_range'                        => 'sometimes|array',
        'time_range.from'                   => 'sometimes|integer',
        'time_range.to'                     => 'sometimes|integer',
        'bucket_interval'                   => 'sometimes|array',
        'bucket_interval.duration'          => 'sometimes|integer',
        'bucket_interval.offset'            => 'sometimes|integer',
        'cache_based'                       => 'sometimes|array',
        'cache_based.from'                  => 'sometimes|integer',
        'cache_based.to'                    => 'sometimes|integer',
        'cache_based.bucket_interval'       => 'sometimes|integer',
        'cache_based.reset_cache_timestamp' => 'sometimes|boolean',
        'cache_based.reverse'               => 'sometimes|boolean',
    ];

    protected static $pspAmountLimit = [
        'upi'       => 20000000, // Changing limit for @upi handle
    ];

    protected static $validateVpaRules = [
        'vpa' => 'required|string|filled|max:100|custom',
    ];

    protected static $validateCredRules = [
        'contact' => 'required|string|contact_syntax',
        'id'      => 'sometimes|nullable|string|max:100',
    ];

    protected static $validateEntityRules = [
        'entity'           => 'required|string|in:vpa,cred',
        'value'            => 'required',
        'language_code'    => 'sometimes|string',
        '_'                => 'sometimes|array',
    ];

    protected static $callbackUrlValidationRules = [
        'callback_url' => 'sometimes|url|custom',
    ];

    protected static $acknowledgeRules = [
        Entity::NOTES => 'sometimes|notes',
    ];

    protected static $paymentOnholdBulkUpdateRules = [
        'payment_ids'    => 'required|sequential_array',
        'payment_ids.*'  => 'required|public_id',
        'on_hold'        => 'required|boolean',
    ];

    protected static $paymentCardMigrateRules = [
        'limit'                             => 'sometimes|integer',
        'migrate_missing_fingerprint_cards' => 'sometimes|boolean',
        'time_window'                       => 'sometimes|integer',
    ];

    protected static $mandateUpdateRules = [
        'start_time'  => 'sometimes',
        'max_amount'  => 'sometimes',
        'token_id'    => 'sometimes',
        'is_mandate'  => 'sometimes'
    ];

    protected static $createValidators = [
        'method',
        'card_key',
        'amount',
        'bank',
        'fee',
        'contact',
        'email',
        'hold_parameters',
        'customer_id',
        'test_success',
        'upi_expiry_time',
        'callback_url_check',
        // Ideally, we should be using custom. But
        // due to dot notation, we cannot use it.
        'ifsc',
        'order_id',
        // Ideally, we should be using custom. But
        // due to dot notation, we cannot use it.
        'token_max_amount',
        'token_expire_by',
        'auth_type',
        'preferred_auth',
        'payment_provider',
        'upi_block',
        'charge_account',
        'cod',
        'wallet_user_id',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected static $fetchStatusCountRules = [
        'from'          => 'required|filled|epoch',
        'to'            => 'required|filled|epoch'
    ];

    protected static $processNachRegisterRules = [
        Entity::ORDER_ID => 'required|public_id',
        Entity::FILE     => 'required|file',
    ];

    protected static $fetchStatusCountValidators = [
        'range'
    ];

    protected static $createUpiUnexpectedPaymentRules = [
        'payment'                       => 'required|array',
        'payment.vpa'                   => 'sometimes',
        'payment.amount'                => 'required|integer',
        'payment.method'                => 'required|string|in:upi',
        'payment.currency'              => 'required|string',
        'payment.contact'               => 'required',
        'payment.email'                 => 'required|string',
        'payment.payer_account_type'    => 'sometimes|string',
        'upi'                           => 'required|array',
        'upi.npci_reference_id'         => 'required',
        'upi.merchant_reference'        => 'sometimes',
        'upi.gateway_payment_id'        => 'sometimes',
        'upi.gateway_merchant_id'       => 'required',
        'upi.vpa'                       => 'required',
        'upi.status_code'               => 'sometimes',
        'upi.account_number'            => 'sometimes',
        'upi.ifsc'                      => 'sometimes',
        'terminal'                      => 'required|array',
        'terminal.gateway_merchant_id'  => 'required',
        'terminal.gateway'              => 'required|string|in:upi_sbi,upi_icici,upi_axis,upi_yesbank,upi_kotak,upi_axisolive,upi_juspay,upi_mindgate',
        'meta'                          => 'required|array',
        'meta.art_reason'               => 'sometimes|string',
        'meta.art_request_id'           => 'sometimes',
        'meta.version'                  => 'required',
    ];

    protected static $authorizeFailedUpiPaymentRules = [
        'payment'                       => 'required|array',
        'payment.method'                => 'required|string|in:upi',
        'payment.id'                    => 'required|string|size:14',
        'payment.amount'                => 'required|integer',
        'upi'                           => 'required|array',
        'upi.npci_reference_id'         => 'required',
        'upi.gateway_payment_id'        => 'sometimes',
        'upi.merchant_reference'        => 'required',
        'upi.npci_txn_id'               => 'sometimes',
        'upi.vpa'                       => 'required',
        'upi.gateway'                   => 'required|string|in:upi_sbi,upi_icici,upi_axis,upi_yesbank,upi_kotak,upi_axisolive,upi_juspay,upi_mindgate',
        'meta'                          => 'required|array',
        'meta.force_auth_payment'       => 'required|boolean',
        'meta.art_request_id'           => 'required',
        'meta.version'                  => 'required',
    ];

    protected static $authorizeFailedNetbankingPaymentRules = [
        'payment'                              => 'required|array',
        'payment.method'                       => 'required|string|in:netbanking',
        'payment.id'                           => 'required|string|size:14',
        'payment.amount'                       => 'required|integer',
        'wallet'                               => 'sometimes',
        'netbanking.status_code'               => 'required',
        'netbanking'                           => 'required|array',
        'netbanking.gateway_transaction_id'    => 'sometimes',
        'netbanking.bank_transaction_id'       => 'sometimes',
        'netbanking.bank_account_number'       => 'sometimes',
        'netbanking.gateway_merchant_id'       => 'sometimes',
        'netbanking.gateway'                   => 'required|string|in:netbanking_sbi,netbanking_icici,netbanking_hdfc,netbanking_axis',
        'meta'                                 => 'required|array',
        'meta.force_auth_payment'              => 'required|boolean',
        'meta.art_request_id'                  => 'required',
        'meta.version'                         => 'required',
    ];

    protected static $authorizeFailedCardPaymentRules = [
        'payment'                              => 'required|array',
        'payment.method'                       => 'required|string|in:card',
        'payment.id'                           => 'required|string|size:14',
        'payment.amount'                       => 'required|integer',
        'card'                                 => 'required|array',
        'card.auth_code'                       => 'required|string',
        'card.rrn'                             => 'required|string',
        'card.arn'                             => 'required|string',
        'meta'                                 => 'required|array',
        'meta.force_auth_payment'              => 'required|boolean',
        'meta.art_request_id'                  => 'required',
        'meta.version'                         => 'required',
    ];

    protected static $createTransactionAuthorizedCardPaymentRules = [
     'payment_id'                              => 'required|string|size:14',
     'art_request_id'                          => 'required'
    ];

    protected static $authorizeFailedWalletPaymentRules = [
        'payment'                              => 'required|array',
        'payment.method'                       => 'required|string|in:wallet',
        'payment.id'                           => 'required|string|size:14',
        'payment.amount'                       => 'required|integer',
        'wallet.status_code'                   => 'required',
        'netbanking'                           => 'sometimes',
        'wallet'                               => 'required|array',
        'wallet.wallet_transaction_id'         => 'sometimes',
        'wallet.gateway'                       => 'required|string|in:wallet_bajaj',
        'meta'                                 => 'required|array',
        'meta.force_auth_payment'              => 'required|boolean',
        'meta.art_request_id'                  => 'required',
        'meta.version'                         => 'required',
    ];

    protected static $updateB2BInvoiceDetailsRules = [
        'document_id'                       => 'required',
    ];

    protected static $updateMerchantDocumentDetailsRules = [
        'document_id'                       => 'required',
        'document_type'                     => 'required',
    ];

    protected function validateRange(array $input)
    {
        //Only query for last 7 days
        if (($input['to'] < $input['from']) or
            ((Carbon::now()->getTimestamp() - $input['from']) > 604800))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The date range is invalid', null, null );
        }

    }

    protected function validateIfsc(array $input)
    {
        if (isset($input[Entity::BANK_ACCOUNT][Entity::IFSC]) === false)
        {
            return;
        }

        $ifsc = $input[Entity::BANK_ACCOUNT][Entity::IFSC];

        if (IFSC::validate($ifsc) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid IFSC Code in Bank Account');
        }
    }

    protected function validateTokenMaxAmount(array $input)
    {
        if (isset($input[Entity::RECURRING_TOKEN][Entity::MAX_AMOUNT]) === false)
        {
            return;
        }

        if (isset($input[Entity::AUTH_TYPE]) === false)
        {
            return;
        }

        $tokenMaxAmount = $input[Entity::RECURRING_TOKEN][Entity::MAX_AMOUNT];

        $defaultMaxAmount = Token\Entity::EMANDATE_MAX_AMOUNT_LIMIT;

        if (($input[Entity::AUTH_TYPE] === AuthType::AADHAAR_FP) or
            ($input[Entity::AUTH_TYPE] === AuthType::AADHAAR))
        {
            $defaultMaxAmount = Token\Entity::AADHAAR_EMANDATE_MAX_AMOUNT_LIMIT;
        }

        if ($tokenMaxAmount > $defaultMaxAmount)
        {
            throw new Exception\BadRequestValidationFailureException(
                'token_max_amount exceeds maximum amount allowed.',
                'token_max_amount',
                ['token_max_amount' => $tokenMaxAmount]);
        }
    }

    protected function validateTokenExpireBy(array $input)
    {
        if (isset($input[Entity::RECURRING_TOKEN][Entity::EXPIRE_BY]) === false)
        {
            return;
        }

        $tokenExpireBy = $input[Entity::RECURRING_TOKEN][Entity::EXPIRE_BY];

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($tokenExpireBy <= $currentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'recurring_token.expire_by should be greater than the current time',
                null,
                [
                    'expire_by'         => $tokenExpireBy,
                    'current_time'      => $currentTime,
                    'payment_id'        => $this->entity->getId(),
                ]);
        }
    }

    protected function validateChargeAccount(array $input)
    {
        if (isset($input[Entity::CHARGE_ACCOUNT]) === false)
        {
            return;
        }

        $merchant = $this->entity->merchant;
        $app = App::getFacadeRoot();

        if (($merchant->isFeatureEnabled(Feature\Constants::CHARGE_ACCOUNT) === false) or
            ($app['basicauth']->isPrivateAuth() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'charge account is/are not required and should not be sent');
        }
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateCod(array $input)
    {
        $payment = $this->entity;

        if ((isset($input[Payment\Entity::METHOD]) === false) or
            ($input[Payment\Entity::METHOD]) !== Method::COD)
        {
            return;
        }

        if (isset($input[Payment\Entity::ORDER_ID]) === true)
        {
            return;
        }

        $message = 'Cannot create Cash on delivery payment without corresponding order.';

        throw new Exception\BadRequestValidationFailureException($message, 'order_id');
    }

    protected function validateAuthType(array $input)
    {
        if (isset($input[Entity::AUTH_TYPE]) === false)
        {
            return;
        }

        AuthType::validateAuthType($input[Entity::AUTH_TYPE], $input[Entity::METHOD]);

        $merchant = $this->entity->merchant;

        AuthType::validateFeatureBasedAuth($merchant, $input[Entity::AUTH_TYPE]);
    }

    protected function validatePreferredAuth(array $input)
    {
        if (isset($input[Entity::PREFERRED_AUTH]) === false)
        {
            return;
        }

        $uniqueAuthentications = array_unique($input[Entity::PREFERRED_AUTH]);

        foreach ($uniqueAuthentications as $authentication)
        {
            AuthType::validateAuthType($authentication, $input[Entity::METHOD]);
        }
    }

    protected function validateUpiExpiryTime(array $input)
    {
        if (isset($input['upi']['expiry_time']) === false)
        {
            return;
        }

        $app = App::getFacadeRoot();

        if ($app['basicauth']->isPrivateAuth() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'upi is/are not required and should not be sent');
        }
    }

    protected function validateOrderId(array $input)
    {
        $merchant = $this->entity->merchant;

        $feature = Feature\Constants::ORDER_ID_MANDATORY;

        if (($merchant->isFeatureEnabled($feature) === true) and
            (isset($input[Payment\Entity::ORDER_ID]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED_MISSING_ORDER_ID);
        }
        else if (isset($input[Payment\Entity::ORDER_ID]) === true and
                   $merchant->isFeatureEnabled(Feature\Constants::PAYMENT_CONFIG_ENABLED) === true)
        {
            return (new Config\Validator())->validatePaymentForConfig($input, $merchant);
        }
    }

    protected function validateEmail(array $input)
    {
        // The payments received on these receivers are push based. We can't really know the
        // email of person making a payment
        if ((isset($input[Entity::RECEIVER]) === true) and
            (empty($input[Entity::RECEIVER]['type']) === false))
        {
            return;
        }

        //
        // When razorpay_wallet feature is enabled, payment request has
        // only the wallet_user_id which is sent over to wallet service.
        //
        if ($this->entity->merchant->hasRazorpaywalletFeature() === true)
        {
            return;
        }

        $allowedPaymentMethods = [
            Payment\Method::AEPS,
            Payment\Method::TRANSFER,
            Payment\Method::BANK_TRANSFER,
            Payment\Method::OFFLINE,
            Payment\Method::INTL_BANK_TRANSFER,
        ];

        if ((in_array($input[Entity::METHOD], $allowedPaymentMethods, true) === false) and
            (empty($input[Entity::EMAIL]) === true)
        )
        {
            throw new Exception\BadRequestValidationFailureException(
                'The email field is required.', Entity::EMAIL);
        }
    }

    protected function validateMethod(array $input)
    {
        $method = $input[Entity::METHOD];

        if (isset($input[Entity::PROVIDER]) and $input[Entity::PROVIDER] === Entity::GOOGLE_PAY)
        {
            if ($method === Method::UNSELECTED)
            {
                return;
            }

            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $method .
                '. Method should not be passed for payments where provider is google_pay');
        }

        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $method);
        }
    }

    protected function validateUpiProvider($attribute, $upiProvider)
    {
        if (UpiProvider::isValidOmnichannelProvider($upiProvider) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid upi provider given: ' . $upiProvider);
        }
    }

    protected function validateReceiver($attribute, $receiver)
    {
        if (Receiver::areTypesValid([$receiver['type']]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid receiver type: ' . $receiver['type']);
        }

        $requiredLength = 17;

        if ($receiver['type'] === Receiver::VPA)
        {
            $requiredLength = 18;
        }

        if ($receiver['type'] === Receiver::POS)
        {
            return;
        }

        if (strlen($receiver['id']) !== $requiredLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The receiver.id must be ' . $requiredLength . ' characters for receiver.type.' . $receiver['type']);
        }
    }

    protected function validateTestSuccess(array $input)
    {
        $app = App::getFacadeRoot();

        if (isset($input['test_success']) === false)
        {
            return;
        }

        if (($app['rzp.mode'] !== Mode::TEST) or
            ($app['basicauth']->isProxyAuth() === false) or
            (isset($input[Entity::SUBSCRIPTION_ID]) === false) or
            (isset($input[Entity::TOKEN]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'test_success cannot be sent.',
                'test_success',
                $input['test_success']);
        }
    }

    protected function isUpiTransferOrBharatQr()
    {
        $app = App::getFacadeRoot();

        $routeName = $app['router']->currentRouteName();

        return in_array($routeName,['upi_transfer_process',
                                    'upi_transfer_process_test',
                                    'gateway_payment_callback_bharatqr',
                                    'bharat_qr_pay_test',
                                    'payment_callback_bharatqr_internal']);
    }

    protected function isBharatQr()
    {
        $app = App::getFacadeRoot();

        $routeName = $app['router']->currentRouteName();

        return in_array($routeName, ['gateway_payment_callback_bharatqr',
                                     'payment_callback_bharatqr_internal',
                                     'bharat_qr_pay_test']);
    }

    protected function validateVpa($attribute, $vpa)
    {
        (new Vpa\Validator)->validateAddress($attribute, $vpa);

        $vpaParts = explode('@', $vpa);

        if ((ProviderCode::validate($vpaParts[1]) === false) and
            ($this->isBharatQr() === false))
        {
            // Invalid VPA
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                $attribute,
                [
                    'vpa' => $vpa
                ]);
        }

        if ((Reconciliate::$isReconRunning === true) or ($this->isUpiTransferOrBharatQr()  === true))
        {
            return;
        }

    }

    protected function validateCallbackUrl($attributes, $callbackUrl)
    {
        if (empty($callbackUrl) === true)
        {
            return;
        }

        $app = App::getFacadeRoot();

        $merchant = $app['basicauth']->getMerchant();

        if ($merchant->isFeatureEnabled(Feature\Constants::CALLBACK_URL_VALIDATION) === true)
        {
            $merchantUrlArray = explode('.', parse_url($merchant->getWebsite(), PHP_URL_HOST));
            $callbackUrlArray = explode('.', parse_url($callbackUrl, PHP_URL_HOST));

            // case where https://example.com
            if (count($merchantUrlArray) === 2)
            {
                array_unshift($merchantUrlArray, '');
            }

            // case where https://example.com
            if (count($callbackUrlArray) === 2)
            {
                array_unshift($callbackUrlArray, '');
            }

            if ((empty($callbackUrlArray) === true) or
                ($merchantUrlArray[1] !== $callbackUrlArray[1]) or
                ($merchantUrlArray[2] !== $callbackUrlArray[2]))
            {
                $traceData = [
                    'merchant_website' => $merchant->getWebsite(),
                    'callback_url'     => $callbackUrl,
                ];

                throw new Exception\BadRequestValidationFailureException(
                    'Invalid callback url',
                    'callback_url',
                    $traceData
                );
            }
        }

    }

    protected function validateCallbackUrlCheck(array $input)
    {
        $callbackUrl = null;
        $method = null;
        if ((isset($input[Entity::CALLBACK_URL])) === true)
        {
            $callbackUrl = $input[Entity::CALLBACK_URL];
        }
        if ((isset($input[Entity::METHOD])) === true)
        {
            $method = $input[Entity::METHOD];
        }

        if ((empty($callbackUrl) === true) or
             ($method === Payment\Method::APP))
        {
            return;
        }

        if($this->validateUrl(Entity::CALLBACK_URL, $callbackUrl) === false){

            $traceData = [
                'callback_url' => $callbackUrl,
            ];

            throw new Exception\BadRequestValidationFailureException(
                'Invalid callback url',
                'callback_url',
                $traceData
            );

        }

        $app = App::getFacadeRoot();

        $merchant = $app['basicauth']->getMerchant();

        if ($merchant->isFeatureEnabled(Feature\Constants::CALLBACK_URL_VALIDATION) === true)

        {
            $merchantUrlArray = explode(".", parse_url($merchant->getWebsite(), PHP_URL_HOST));
            $callbackUrlArray = explode(".", parse_url($callbackUrl, PHP_URL_HOST));

            // case where https://example.com
            if (count($merchantUrlArray) === 2)
            {
                array_unshift($merchantUrlArray, "");
            }

            // case where https://example.com
            if (count($callbackUrlArray) === 2)
            {
                array_unshift($callbackUrlArray, "");
            }

            if ((empty($callbackUrlArray) === true) or
                ($merchantUrlArray[1] !== $callbackUrlArray[1]) or
                ($merchantUrlArray[2] !== $callbackUrlArray[2]))
            {
                $traceData = [
                    'merchant_website' => $merchant->getWebsite(),
                    'callback_url'     => $callbackUrl,
                ];

                throw new Exception\BadRequestValidationFailureException(
                    'Invalid callback url',
                    'callback_url',
                    $traceData
                );
            }
        }
    }

    protected function validateWallet($attribute, $value)
    {
        Wallet::validateExists($value);
    }

    protected function validatePaymentProvider(array $input)
    {
        switch ($input['method'])
        {
            case Payment\Method::CARDLESS_EMI:
                $isDisabledInstrument = in_array($input['provider'], CardlessEmiProvider::$disabledInstruments, true);

                if ((CardlessEmi::exists($input['provider']) === false) or ($isDisabledInstrument === true))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for cardless emi',
                        'provider',
                        $input['provider']);
                }
                break;

            case Payment\Method::PAYLATER:
                if (Payment\Processor\PayLater::exists($input['provider']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for Pay Later',
                        'provider',
                        $input['provider']);
                }
                break;
            case Payment\Method::APP:
                if (Payment\Processor\App::isValidApp($input['provider']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for App',
                        'provider',
                        $input['provider']);
                }
                break;

            default:
                return;
        }
    }

    protected function validateCardKey(array $input)
    {
        if (($input['method'] !== Payment\Method::CARD) and
            ($input['method'] !== Payment\Method::EMI))
        {
            return;
        }

        /*
         * Checking if the card payment is of Google Pay. If it is of Google Pay then there are no
         * card details.
         */
        if (((isset($input['application'])) === true) and
            ($input['application'] === 'google_pay'))
        {
            return;
        }

        if ((isset($input['recurring']) === true) and
            ($input['recurring'] === '1') and
            (empty($input['token']) === false))
        {
            return;
        }

        // moto payment with token doesn't require any card details
        if ((isset($input['auth_type']) === true) and
            ($input['auth_type'] === AuthType::SKIP) and
            (empty($input['token']) === false))
        {
            return;
        }

        if ((array_key_exists('card', $input) === false) or
            ($input['card'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED);
        }

        if (is_array($input['card']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_IS_NOT_ARRAY);
        }
    }

    protected function validateAmount(array $input)
    {
        $amount = (int) $input['amount'];

        $method = $input['method'];

        $receiverType = null;

        $isGooglePayPayment = false;

        if ((isset($input[Entity::PROVIDER]) and
            ($input[Entity::PROVIDER] === Entity::GOOGLE_PAY) and
            ($method === Method::UNSELECTED)))
        {
            $isGooglePayPayment = true;
        }

        // No limit on amount for payments of method defined in Method::$methodsWithoutAmountValidation
        if (in_array($method, Method::$methodsWithoutAmountValidation, true) === true)
        {
            return;
        }

        if (isset($input[Entity::RECEIVER]) === true)
        {
            $receiverType = $input[Entity::RECEIVER]['type'];
        }

        // The payments received on these receivers are push based. We can't really control after
        // we already received a payments. So removing amount validation check on it
        if (empty($receiverType) === false)
        {
            return;
        }

        if (($method !== Payment\Method::EMANDATE) and
            ($method !== Payment\Method::NACH))
        {
            $this->validateInputValues('min_amount_check', $input);
        }

        if (($method === Payment\Method::UPI) or ($isGooglePayPayment === true))
        {
            if ($amount > 20000000)
            {
                if ($isGooglePayPayment === true)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Amount cannot be greater than ₹200000.00');
                }

                throw new Exception\BadRequestValidationFailureException(
                    'Amount for UPI payment cannot be greater than ₹200000.00');
            }

            if ($this->isFlowIntent($input))
            {
                return;
            }

            if (isset($input['vpa']) === true)
            {
                $vpa = $input['vpa'];

                $handle = substr($vpa, strpos($vpa, '@') + 1);

                if ((isset(self::$pspAmountLimit[$handle]) === true) and
                    ($amount > self::$pspAmountLimit[$handle]))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Maximum amount for UPI payment can be Rs ' . (self::$pspAmountLimit[$handle] / 100));
                }
            }
        }

        $isInternational = $this->entity->fetchInternationalFromInput($input);
        $maxAmountAllowed = $this->entity->merchant->getMaxPaymentAmountTransactionType($isInternational, $method);

        $currency = $input['currency'];

        $baseAmount = $amount;

        if ($currency != $this->entity->merchant->getCurrency())
        {
            $baseAmount = (new CurrencyCore)->getBaseAmount($amount, $currency, $this->entity->merchant->getCurrency());
        }

        if (($baseAmount > $maxAmountAllowed) === true)
        {
            $this->trace->count(Metric::PAYMENT_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT, [
                'business_type' => $this->entity->merchant->merchantDetail->getBusinessType() ?? '',
            ]);

            $meta_data = [];

            if (($method === Payment\Method::PAYLATER) and (isset($input[Entity::PROVIDER]) and
                    (($input[Entity::PROVIDER] === Payment\Processor\PayLater::ICICI ) or
                        ($input[Entity::PROVIDER] === Payment\Processor\PayLater::GETSIMPL)))) {

                if(array_key_exists('order_id', $input))
                {
                    $meta_data['order_id'] = $input['order_id'];
                }
            }

            $meta_data['amount'] = $amount;

            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds maximum amount allowed.',
                'amount',
                $meta_data);
        }
        // check for min amount > 100 for payment currency not equal to merchant currency
        if ( $currency != $this->entity->merchant->getCurrency() && ($baseAmount < 100) === true)
        {
            $this->trace->count(Metric::PAYMENT_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT, [
                'business_type' => $this->entity->merchant->merchantDetail->getBusinessType() ?? '',
            ]);

            throw new Exception\BadRequestValidationFailureException(
                'The amount must be atleast INR 1.00.',
                'amount',
                ['amount' => $baseAmount]);
        }

        // For OPGSP import flow, max trasaction limit is USD 2000
        if($this->entity->merchant->isOpgspImportEnabled())
        {
            $opgspLimitAmountUSD = ConfigKey::get(ConfigKey::DEFAULT_OPGSP_TRANSACTION_LIMIT_USD);

            if($opgspLimitAmountUSD === null)
            {
                $opgspLimitAmountUSD = Constants::OPGSP_TRANSACTION_LIMIT_USD;
            }

            $opgspLimitAmountINR = (new CurrencyCore)->getBaseAmount($opgspLimitAmountUSD, Currency::USD);

            if($baseAmount > $opgspLimitAmountINR)
            {
                $this->trace->count(Metric::PAYMENT_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT, [
                    'business_type' => $this->entity->merchant->merchantDetail->getBusinessType() ?? '',
                ]);

                throw new Exception\BadRequestValidationFailureException(
                    'Amount exceeds maximum amount allowed.',
                    'amount',
                    ['amount' => $baseAmount,
                      'opgsp' =>  $opgspLimitAmountINR, ]);
            }
        }
    }

    public function validatePosPaymentCreation($input)
    {
        (new Validator)->validateInput('pos_create',$input);

        $paymentFetch = (new PaymentMeta\Repository())->findByReferenceId($input['meta']['reference_id']);

        if(is_null($paymentFetch) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED_OR_VOIDED,null,[
                'error_code'            => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED_OR_VOIDED,
                'error_description'     => PublicErrorDescription::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED_OR_VOIDED
            ]);
        }

        if(in_array($input[Entity::METHOD],[Entity::CARD,'upi']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,null,[
                "error_code"        => ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,
                'error_description' => PublicErrorDescription::BAD_REQUEST_INVALID_PAYMENT_METHOD
            ]);
        }

    }

    public function validateUpiVpaPsp(string $vpa, array $excludedPsps)
    {
        $vpaParts = explode('@', $vpa);

        if (in_array($vpaParts[1], $excludedPsps, true) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_APP_NOT_SUPPORTED);
        }
    }

    public function validateCardAndCvv(array $input)
    {
        /*
            No card details when the payment is for Google Pay for cards.
        */
        if ((isset($input['application']) === true) and ($input['application'] === 'google_pay'))
        {
            return;
        }

        // card and cvv optional for amex and visa tokenized payments
        if (($this->entity->card->isAmex() ||  $this->entity->card->isVisa())
            && $this->entity->card->getTrivia() === '1')
        {
            if(empty($input['card']))
            {
                $this->trace->info(traceCode::CARD_CVV_OPTIONAL, []);
                return;
            }
            else if(empty($input['card']['cvv']))
            {
                $this->trace->info(traceCode::CVV_OPTIONAL, []);
                return;
            }
        }

        if (isset($input['card']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED);
        }

        //cvv is not mandatory for Visa Safe Click payments
        if ((isset($input['application']) === true) and ($input['application'] === 'visasafeclick'))
        {
            return;
        }

        // cvv not mandatory for vsc step up payments
        if ($this->entity->isVisaSafeClickStepUpPayment() === true)
        {
            return;
        }

        // For few axis org merchants who need to support commercial card, cvv is optional
        if ($this->entity->skipCvvCheck() === true)
        {
            return;
        }

        if (isset($input['card']['cvv']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_CVV_NOT_PROVIDED);
        }
    }

    public function validateMinAmountWithEmiPlanAmount($emiPlan)
    {
        if ($this->entity->getAmount() < $emiPlan->getMinAmount())
        {
            // We need to do this check here because currently amex has a higher limit of 5k.
            throw new Exception\BadRequestValidationFailureException(
                'Minimum amount allowed for EMI payment on this card must be ' . $emiPlan->getMinAmount());
        }
    }

    public function validateForPayout(string $mode)
    {
        if ($this->entity->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        if (($mode === Mode::LIVE) and
            ($this->entity->transaction->isSettled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT);
        }
    }

    protected function validateBank($input)
    {
        // @todo: Add validation for UPI method as well for tpv
        if (($input['method'] !== Payment\Method::NETBANKING) and
            ($input['method'] !== Payment\Method::EMANDATE))
        {
            return;
        }

        if (isset($input['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_PROVIDED);
        }

        $supported = false;
        $bank = $input['bank'];

        $method = $input['method'];

        switch ($method)
        {
            case Payment\Method::EMANDATE:
                $supported = Payment\Gateway::isSupportedEmandateBank($bank);
                break;

            case Payment\Method::UPI:
                $supported = Payment\Processor\Upi::isSupportedUpiBank($bank);
                break;

            case Payment\Method::NETBANKING:
                $supported = Payment\Processor\Netbanking::isSupportedBank($bank);
                break;
        }

        //
        // The bank is validated for emandate in `validateInitialRecurringForEmandate`
        //
        if ($supported === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                'bank');
        }
    }

    protected function validateContact($input)
    {
        // The payments received on these receivers are push based. We can't really know the
        // contact of person making a payment
        if ((isset($input[Entity::RECEIVER]) === true) and
            (empty($input[Entity::RECEIVER]['type']) === false))
        {
            return;
        }

        $allowedPaymentMethods = [
            Payment\Method::AEPS,
            Payment\Method::TRANSFER,
            Payment\Method::BANK_TRANSFER,
            Payment\Method::OFFLINE,
            Payment\Method::INTL_BANK_TRANSFER
        ];

        if ((in_array($input[Entity::METHOD], $allowedPaymentMethods, true) === false) and
            ((empty($input[Entity::CONTACT]) === true) and (empty($input[Entity::UPI_PROVIDER]) === true)))
        {
            //For Razorpay wallet payment if contact is not present
            //throw error if wallet_user_id is also not present
            if (($input['method'] === Payment\Method::WALLET) and ($input['wallet'] === Wallet::RAZORPAYWALLET) and
                (empty($input['wallet_user_id']) === false))
            {
                return;
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                'The contact field is required.', Entity::CONTACT);
            }
        }

        if ($input['method'] === Payment\Method::WALLET)
        {
            if (in_array($input['wallet'], Wallet::$indianContactWallets, true) === true)
            {
                $this->validateIndianContact($input['contact']);
            }
        }

        if (in_array($input['method'], [Payment\Method::CARDLESS_EMI, Payment\Method::PAYLATER], true) === true)
        {
            $this->validateIndianContact($input['contact']);
        }
    }

    protected function validateIndianContact($contact)
    {
        $number = new PhoneBook($contact, true);

        if ($number->isValidNumberForRegion('IN') === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED);
        }
    }

    protected function validateCustomerId($input)
    {
        if (isset($input[Entity::CUSTOMER_ID]) === false)
        {
            //
            // customer_id should always be sent in case of openwallet.
            // It's okay to not send otherwise. Gets handled in the main flow.
            //
            if ((isset($input[Entity::WALLET]) === true) and
                ($input[Entity::WALLET] === Merchant\Methods\Entity::OPENWALLET))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The customer id field is required when wallet is openwallet.',
                    'customer_id',
                    [
                        'wallet' => $input[Entity::WALLET]
                    ]);
            }

            return;
        }

        //
        // customer_id should not be sent for a razorpaywallet payment.
        // wallet_user_id should be sent instead.
        //
        if ((isset($input[Entity::WALLET]) === true) and
            ($input[Entity::WALLET] === Wallet::RAZORPAYWALLET))
        {
            throw new Exception\BadRequestValidationFailureException(
                'customer_id is not required and should not be sent.',
                'customer_id',
                [
                    'customer_id'   => $input[Entity::CUSTOMER_ID],
                    'wallet'        => $input[Entity::WALLET],
                ]
            );
        }

        //
        // Should not send customer_id for a subscription payment,
        // since subscription already has a customer associated.
        //
        if (isset($input[Entity::SUBSCRIPTION_ID]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'customer_id is not required and should not be sent',
                'customer_id',
                [
                    'customer_id'       => $input[Entity::CUSTOMER_ID],
                    'subscription_id'   => $input[Entity::SUBSCRIPTION_ID],
                ]);
        }
    }

    protected function validateFee($input)
    {
        if (isset($input['fee']))
        {
            $merchant = $this->entity->merchant;

            if ($merchant->isFeeBearerCustomerOrDynamic() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Attribute fee is not allowed and should not be sent');
            }
        }
        if ((isset($input['fee'])) and
            (empty($input['fee'])))
        {
            unset($input['fee']);
        }
    }

    protected function validateCurrency($attribute, $currency)
    {
        if (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                'currency');
        }
    }

    public static function validateStatus($status)
    {
        if (Status::isStatusValid($status) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }

    public static function validateStatusArray(array $status)
    {
        foreach ($status as $value)
        {
            self::validateStatus($value);
        }
    }

    public function validateHoldParameters(array $input)
    {
        if (isset($input[Entity::ON_HOLD]) === false)
        {
            return;
        }

        if (isset($input[Entity::ON_HOLD_UNTIL]) === true)
        {
            if ($input[Entity::ON_HOLD] === '0')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold field must be set to 1, if on_hold_until is sent');
            }

            $now = Carbon::now()->getTimestamp();

            if ($input[Entity::ON_HOLD_UNTIL] < $now)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold_until timestamp cannot be less than the current timestamp');
            }
        }
    }

    public function captureValidate(Payment\Entity $payment, int $amount, string $currency)
    {
        $app = App::getFacadeRoot();

        $app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_VALIDATION_INITIATED, $payment);

        try
        {
            $this->failIfCaptured($payment);

            $this->failIfNotAuthorized($payment);

            $this->failIfNotPending($payment);

            $this->failIfRefundConfigSetLateAuth($payment);

            // For Optimizer external pg payments, we should honor auto capture timeout
            $this->failIfRefundConfigSetLateAuthForOptimizerExternalPgPayment($payment);

            $this->failIfSmartCollectUnexpectedPayment($payment);

            $this->failIfIntlBankTransferUnexpectedPayment($payment);

            $this->captureAmountValidate($payment, $amount);

            $this->captureCurrencyValidate($payment, $currency);

            $this->captureUpiOtmExecuteValidate($payment);

            $app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_VALIDATION_SUCCESS, $payment);
        }
        catch (\Throwable $e)
        {
            $app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_VALIDATION_FAILED, $payment, $e);

            throw $e;
        }

    }

    public function cancelValidate($payment)
    {
        $this->failIfNotCreated($payment);
    }

    public function validateIsCaptured()
    {
        if ($this->entity->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }
    }

    public function captureAmountValidate(Payment\Entity $payment, int $amount)
    {
        if ($amount !== $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                Payment\Entity::AMOUNT,
                [
                    'capture_amount' => $amount,
                    'payment_amount' => $payment->getAmount(),
                    'payment_id'     => $payment->getId(),
                ]);
        }
    }

    protected function captureCurrencyValidate($payment, $currency)
    {
        if ($currency !== $payment->getCurrency())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_CURRENCY_MISMATCH,
                Payment\Entity::CURRENCY,
                [
                    'capture_currency' => $currency,
                    'payment_currency' => $payment->getCurrency(),
                    'payment_id'       => $payment->getId(),
                ]);
        }

    }

    protected function captureUpiOtmExecuteValidate(Payment\Entity $payment)
    {
        if ($payment->isUpiOtm() === true)
        {
            $upiMetadata = $payment->getUpiMetadata();

            $currentTimestamp = Carbon::now()->getTimestamp();

            if ($upiMetadata->inTimeRange($currentTimestamp) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_INVALID_EXECUTION_TIME,
                    null,
                    [
                        'start_time'   => $upiMetadata->getStartTime(),
                        'end_time'     => $upiMetadata->getEndTime(),
                        'current_time' => $currentTimestamp,
                        'payment_id'   => $payment->getId(),
                        'merchant_id'  => $payment->merchant->getId(),
                    ]);
            }
        }
    }

    protected function failIfNotCreated($payment)
    {
        if ($payment->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANCEL_ONLY_CREATED);
        }
    }

    protected function failIfCaptured($payment)
    {
        //
        // Don't continue if already captured
        //
        if ($payment->hasBeenCaptured() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $payment->getStatus(),
                    'captured_at'   => $payment->getCapturedAt(),
                ]);
        }
    }

    protected function failIfNotAuthorized($payment)
    {
        if ($payment->isCoD() === true)
        {
            return;
        }

        if ($payment->isAuthorized() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_ONLY_AUTHORIZED);
        }
    }

    protected function failIfNotPending($payment)
    {
        if ($payment->isCoD() === false)
        {
            return;
        }

        if ($payment->isPending() === true)
        {
            return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_ONLY_PENDING,
        null,
        [
            'method' => $payment->getMethod(),
        ]);
    }

    // Additional check for Optimizer
    protected function failIfRefundConfigSetLateAuthForOptimizerExternalPgPayment($payment)
    {
        if (($payment->hasOrder() === true) and
            ($payment->isLateAuthorized() === true) and
            ($payment->isOptimizerCaptureSettingsEnabled() === true))
        {
            $processor = new Payment\Processor\Processor($this->entity->merchant);

            $lateAuthConfig = $processor->getLateAuthPaymentConfig($payment);

            if (isset($lateAuthConfig) === false)
            {
                return;
            }

            $autoTimeoutDuration = $lateAuthConfig['capture_options']['automatic_expiry_period'];

            $difference = $processor->getTimeDifferenceInAuthorizeAndCreated($payment);

            // We only support auto capture for optimizer payments
            if ((empty($autoTimeoutDuration) === false) and ($difference > $autoTimeoutDuration))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CONFIG_MARKED_FOR_REFUND);
            }
        }
    }

    protected function failIfRefundConfigSetLateAuth($payment)
    {
        if (($payment->hasOrder() === true) and
            ($payment->isLateAuthorized() === true) and
            ($payment->isDirectSettlement() === false))
        {
            $processor = new Payment\Processor\Processor($this->entity->merchant);

            $lateAuthConfig = $processor->getLateAuthPaymentConfig($payment);

            if (isset($lateAuthConfig) === false)
            {
                return;
            }

            $autoTimeoutDuration = $lateAuthConfig['capture_options']['automatic_expiry_period'];

            if (isset($lateAuthConfig['capture_options']['manual_expiry_period']) === false)
            {
                $manualTimeoutDuration = $autoTimeoutDuration;
            }
            else
            {
                $manualTimeoutDuration = $lateAuthConfig['capture_options']['manual_expiry_period'];
            }

            $difference = $processor->getTimeDifferenceInAuthorizeAndCreated($payment);

            if ($difference > $manualTimeoutDuration)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CONFIG_MARKED_FOR_REFUND);
            }
        }
    }

    /**
     * Validates if a payment can be marked as acknowledged. Only captured payments can be acknowledged.
     * Note: A payment that has been captured and then refunded can be marked as acknowledged;
     *       but a payment authorized and then refunded cannot be marked as acknowledged.
     *
     * @throws Exception\BadRequestException
     */
    public function acknowledgeValidate()
    {
        $payment = $this->entity;

        if ($payment->hasBeenCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
                [
                    Entity::ID              => $payment->getId(),
                    Entity::STATUS          => $payment->getStatus(),
                    Entity::ACKNOWLEDGED_AT => $payment->getAcknowledgedAt(),
                ]);
        }

        if ($payment->isAcknowledged() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_ACKNOWLEDGED,
                [
                    Entity::ID              => $payment->getId(),
                    Entity::STATUS          => $payment->getStatus(),
                    Entity::ACKNOWLEDGED_AT => $payment->getAcknowledgedAt(),
                ]);
        }
    }

    public function validateGatewayForForceAuth()
    {
        $payment = $this->entity;

        $gateway = $payment->getGateway();
        $gateway_acquirer = $payment->terminal->getGatewayAcquirer();

        $allowedGateways = Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS;

        if ((in_array($gateway, $allowedGateways, true) === false) ||
            ($gateway == payment\Gateway::FULCRUM && $gateway_acquirer != payment\Gateway::ACQUIRER_AXIS))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot force authorize on this gateway',
                'gateway',
                $gateway);
        }
    }

    protected function validateDccCurrency($attribute, $dccCurrency)
    {
        if (Currency::isSupportedCurrency($dccCurrency) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid DCC Currency: ' . $dccCurrency);
        }
    }

    protected function validateUpiBlock($input)
    {
        if (isset($input['upi']['vpa']) === true)
        {
            $this->validateVpa('upi.vpa', $input['upi']['vpa']);
        }

        if ($this->isOtmPayment($input) === true)
        {
            $this->validateUpiBlockForOtm($input);
        }

        if (($this->isUpiRecurringPayment($input) === true) and
            (isset($input['upi']['vpa']) === true))
        {
            $vpa = $this->getUpiVpa($input);

            $this->validateUpiBlockForAutoPay($vpa);
        }
    }

    protected function validateUpiBlockForAutoPay($vpa)
    {
        $isSupportedVpa = ProviderCode::validateAutoPayPspProvider($vpa, $this->isTestMode());
        $isPspTestVpa   = ProviderCode::validateAutopayVpaHandleForPspTesting($vpa, $this->entity->merchant->getId());

        if (($isSupportedVpa === false) and
            ($isPspTestVpa === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_APP_AUTO_PAY_NOT_SUPPORTED,
                Entity::VPA,
                [
                    'vpa' => $vpa
                ],"App not Supported for Upi AutoPay");
        }
    }

    protected function validateUpiBlockForOtm($input)
    {

        $app = App::getFacadeRoot();
        /**
         * Currently, Blocking intent for upi mandate payments.
         */
        if ($this->isFlowIntent($input) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_INTENT_NOT_SUPPORTED,
                'upi.flow',
                $input);
        }

        if ($this->isFlowCollect($input) === true)
        {
            $vpa = $this->getUpiVpa($input);

            $vpaParts = explode('@', $vpa);

            /**
             * Check the vpa for supported PSP's. Else throw a validation error.
             */
            if (ProviderCode::validateOtmProvider($vpaParts[1], $app['rzp.mode']) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_UPI_APP_ONE_TIME_MANDATE_NOT_SUPPORTED,
                    'vpa',
                    [
                        'vpa' => $vpa
                    ]);
            }
        }

        if ((isset($input['upi']['start_time']) === false) or
            (isset($input['upi']['end_time']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_TIME_RANGE_REQUIRED,
                'upi',
                ['input' => $input]
            );
        }

        if ($this->getUpiStartTime($input) > $this->getUpiEndTime($input))
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_END_TIME_INVALID,
                'upi.end_time',
                ['input' => $input]
            );
        }

        $now = Carbon::now()->getTimestamp();

        if ($this->getUpiEndTime($input) < $now)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_END_TIME_INVALID,
                'upi.end_time',
                ['input' => $input]
            );
        }

        $diff = ($this->getUpiEndTime($input) - $this->getUpiStartTime($input));

        if ($diff > UpiMetadata\Entity::DEFAULT_OTM_EXECUTION_RANGE)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_UPI_END_TIME_OUT_OF_RANGE,
                'upi.end_time',
                ['input' => $input]
            );
        }
    }

    protected function validateWalletUserId($input)
    {

        if ($this->entity->merchant->hasRazorpaywalletFeature() === false and  (empty($input['wallet_user_id']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'wallet_user_id is not required and should not be sent.',
                'wallet_user_id'
            );
        }
        if (($input['method'] === Payment\Method::WALLET) and ($input['wallet'] === Wallet::RAZORPAYWALLET))
        {
            if (empty($input['wallet_user_id']) === true)
            {
                if (empty($input['contact']) === true) {
                    throw new Exception\BadRequestValidationFailureException(
                        'The wallet_user_id field is required when contact is not present.', Entity::REFERENCE14);
                }
            }
        }
    }

    protected function validateForceTerminalId($input)
    {
        $merchant = $this->entity->merchant;

        $feature = Feature\Constants::ALLOW_FORCE_TERMINAL_ID;

        if ($merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED_FEATURE_FORCE_TERMINAL_ID_NOT_ENABLED);
        }
    }

    public function validateUploadPaymentSupportingDocument($input)
    {
        if(!isset($_FILES) or !sizeof($_FILES)>0 or !isset($_FILES['file']))
        {
            throw new Exception\BadRequestException(Error\ErrorCode::BAD_REQUEST_FILE_NOT_FOUND);
        }

        if(!in_array($input['purpose'], \RZP\Models\GenericDocument\Constants::PURPOSE_TYPE))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid purpose type.', 'purpose');
        }
    }

    protected function failIfIntlBankTransferUnexpectedPayment(Entity $payment)
    {
        if ($payment->isB2BExportCurrencyCloudPayment() === true)
        {
            if (empty($payment->getReference2()) === true ||
                $payment->merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B) === false ||
                empty($payment->getReference16()) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_INVALID_CAPTURE);
            }
        }
    }


    protected function failIfSmartCollectUnexpectedPayment(Entity $payment)
    {
        $virtualAccount = null;

        if ($payment->isBankTransfer() === true)
        {
            $virtualAccount = $payment->bankTransfer->virtualAccount;
        }

        if ($payment->isUpiTransfer() === true)
        {
            $virtualAccount = $payment->upiTransfer->virtualAccount;
        }

        if (($virtualAccount !== null) and ($virtualAccount->isClosed() === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_CLOSED_VIRTUAL_ACCOUNT);
        }
    }

    /**
     * @param Entity $payment
     *
     * @throws BadRequestException
     */
    public function validatePaymentRelease(Entity $payment)
    {
        if ($payment->isCaptured() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED, $payment->getId());
        }

        $transaction = $payment->transaction;

        if ($transaction->isOnHold() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_TRANSACTION_NOT_ON_HOLD, $transaction->getId());
        }

        if ($transaction->isSettled() === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_SETTLED,
                $payment->getId(),
                [Entity::TRANSACTION_ID => $transaction->getId()]
            );
        }
    }
}
