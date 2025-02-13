<?php

namespace RZP\Models\Payment;

use App;
use Carbon\Carbon;
use Illuminate\Http\Response as HttpResponse;
use Google\Service\CloudControlsPartnerService\Partner;
use Lib\PhoneBook;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants\Procurer;
use RZP\Mail\Payment\Failed;
use RZP\Models\Address;
use RZP\Models\Address\Type;
use RZP\Models\BankAccount\Generator;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN;
use RZP\Models\Discount;
use RZP\Models\Notification;
use RZP\Models\Order\ProductType;
use RZP\Models\Upi\Turbo\Utils;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Merchant\RazorxTreatment;
use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Models\Emi;
use RZP\Gateway\Upi;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Offer;
use RZP\Models\QrCode;
use RZP\Models\Feature;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Currency;
use RZP\Models\Customer;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\PaymentLink;
use RZP\Models\UpiTransfer;
use RZP\Models\BankTransfer;
use RZP\Models\RewardPoint;
use RZP\Constants\Entity as E;
use RZP\Models\OfflinePayment;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Account;
use RZP\Models\Plan\Subscription;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\Traits\DualWrite;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Base\Traits\ExternalOwner;
use RZP\Models\Base\Traits\ExternalEntity;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Models\Payment\Processor\Constants;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\Fpx;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\QrPayment\Constants as QRConstant;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Models\CardMandate\CardMandateNotification;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;
use RZP\Models\Payment\Refund\TransactionTrackerMessages;
use RZP\Models\QrCode\NonVirtualAccountQrCode\RequestSource;
use RZP\Models\Partner\Commission\CommissionSourceInterface;
use RZP\Models\PaymentsUpi;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;
use RZP\Models\Payment\Processor\Constants as PaymentConstants;
use RZP\Constants\Entity as ConstantsEntity;
/**
 * @property Subscription\Entity    $subscription
 * @property Invoice\Entity         $invoice
 * @property Terminal\Entity        $terminal
 * @property Merchant\Entity        $merchant
 * @property Card\Entity            $card
 * @property BankTransfer\Entity    $bankTransfer
 * @property UpiTransfer\Entity     $upiTransfer
 * @property OfflinePayment\Entity  $offlinePayment
 * @property PaymentLink\Entity     $paymentLink
 * @property Order\Entity           $order
 * @property Transaction\Entity     $transaction
 * @property Emi\Entity             $emiPlan
 * @property Customer\Entity        $customer
 * @property PaymentMeta\Entity     $paymentMeta
 * @property Customer\Token\Entity  $localToken
 * @property Customer\Token\Entity  $globalToken
 * @property CardMandateNotification\Entity $cardMandateNotification
 * @property string                 $receiver_type
 * @property string                 $receiver_id
 * @property VpaEntity|QrV2\Entity  $receiver
 */
class Entity extends Base\PublicEntity implements CommissionSourceInterface
{
    use NotesTrait, ExternalOwner, ExternalEntity, DualWrite, AsvGetAttribute;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const AMOUNT                = 'amount';
    const BASE_AMOUNT           = 'base_amount';
    const METHOD                = 'method';
    const STATUS                = 'status';
    const AMOUNT_AUTHORIZED     = 'amount_authorized';
    const AMOUNT_REFUNDED       = 'amount_refunded';
    const BASE_AMOUNT_REFUNDED  = 'base_amount_refunded';
    const AMOUNT_CAPTURED       = 'amount_captured';
    const AMOUNT_TRANSFERRED    = 'amount_transferred';
    const AMOUNT_PAIDOUT        = 'amount_paidout';
    const TWO_FACTOR_AUTH       = 'two_factor_auth';
    const ORDER_ID              = 'order_id';
    const INVOICE_ID            = 'invoice_id';
    const TRANSFER_ID           = 'transfer_id';
    const PAYMENT_LINK_ID       = 'payment_link_id';
    const RECEIVER_ID           = 'receiver_id';
    const RECEIVER_TYPE         = 'receiver_type';
    const INTERNATIONAL         = 'international';
    const REFUND_STATUS         = 'refund_status';
    const CAPTURED              = 'captured';
    const DISPUTED              = 'disputed';
    const CURRENCY              = 'currency';
    const DESCRIPTION           = 'description';
    const ERROR_CODE            = 'error_code';
    const INTERNAL_ERROR_CODE   = 'internal_error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const CANCELLATION_REASON   = 'cancellation_reason';
    const CUSTOMER_ID           = 'customer_id';
    const GLOBAL_CUSTOMER_ID    = 'global_customer_id';
    const APP_ID                = 'app_id';
    const APP_TOKEN             = 'app_token';
    const TOKEN                 = 'token';
    const LOCAL_TOKEN           = 'localToken';
    const GLOBAL_TOKEN          = 'globalToken';
    const API_VAULT             = 'api_vault';
    const TOKEN_ID              = 'token_id';
    const GLOBAL_TOKEN_ID       = 'global_token_id';
    const VPA                   = 'vpa';
    const VPA_TOKEN             = 'vpa_token';
    const ON_HOLD               = 'on_hold';
    const ON_HOLD_UNTIL         = 'on_hold_until';
    const EMAIL                 = 'email';
    const CONTACT               = 'contact';
    const NOTES                 = 'notes';
    const BANK                  = 'bank';
    const CARD_ID               = 'card_id';
    const WALLET                = 'wallet';
    const EMI_PLAN_ID           = 'emi_plan_id';
    const EMI_DURATION          = 'emi_duration';
    const EMI_SUBVENTION        = 'emi_subvention';
    const TRANSACTION_ID        = 'transaction_id';
    const AUTO_CAPTURED         = 'auto_captured';
    const AUTHENTICATED_AT      = 'authenticated_at';
    const AUTHORIZED_AT         = 'authorized_at';
    const CAPTURED_AT           = 'captured_at';
    const GATEWAY               = 'gateway';
    const TERMINAL_ID           = 'terminal_id';
    const GATEWAY_PROVIDER      = 'gateway_provider';
    const BATCH_ID              = 'batch_id';
    const REFERENCE1            = 'reference1';
    const REFERENCE2            = 'reference2';
    const CAPTURE               = 'reference3';
    const CPS_ROUTE             = 'cps_route';
    const CONVENIENCE_FEE_GST   = 'reference5';
    const IS_PUSHED_TO_KAFKA    = 'reference6';
    const CONVENIENCE_FEE       = 'reference9';
    const FEE_BEARER            = 'fee_bearer';
    //Reference13 is repurposed to store source channel for online and in person payments [possible values : online and in_person]
    const SOURCE_CHANNEL        = 'reference13';
    // Reference14 has been used to store razorpay wallet user id
    const REFERENCE14           = 'reference14';
    // From 15 to 17 are blank columns of various types(refer migration file) to be consumed after renaming when needed
    const REFERENCE16           = 'reference16';
    const REFERENCE17           = 'reference17'; // used to store gateway_error_code and gateway_error_description
    const SIGNED                = 'signed';
    const VERIFIED              = 'verified';
    const GATEWAY_CAPTURED      = 'gateway_captured';
    // This is the bucket for the next verify and not the current verify.
    const VERIFY_BUCKET         = 'verify_bucket';
    const VERIFY_AT             = 'verify_at';
    const CALLBACK_URL          = 'callback_url';
    const TAX                   = 'tax';
    const OTP_ATTEMPTS          = 'otp_attempts';
    const OTP_COUNT             = 'otp_count';
    const FEE                   = 'fee';
    const MDR                   = 'mdr';
    const RECURRING             = 'recurring';
    const SAVE                  = 'save';
    const LATE_AUTHORIZED       = 'late_authorized';
    const CONVERT_CURRENCY      = 'convert_currency';
    const AUTH_TYPE             = 'auth_type';
    const ACKNOWLEDGED_AT       = 'acknowledged_at';
    const REFUND_AT             = 'refund_at';

    const MAX_AMOUNT            = 'max_amount';
    const EXPIRE_BY             = 'expire_by';
    const RECURRING_TOKEN       = 'recurring_token';
    const NOTIFICATION_ID       = 'notification_id';

    const SUBSCRIPTION_ID       = 'subscription_id';

    const PREFERRED_AUTH        = 'preferred_auth';

    const PUBLIC_KEY            = 'public_key';

    // Used by merchant dashboard to fetch payments based on utr
    const BANK_REFERENCE        = 'bank_reference';

    const GIFT_CARDS            = 'gift_cards';

    const DEFAULT_CURRENCY      = 'INR';

    const ACQUIRER_DATA         = 'acquirer_data';

    const AUTH_CODE             = 'auth_code';

    // Query params
    const TRANSFERRED           = 'transferred';

    // Relations
    const CARD                  = 'card';
    const EMI                   = 'emi';
    const SENDER_ADDRESS        = 'sender_address';
    const EMI_PLAN              = 'emi_plan';
    const DISPUTES              = 'disputes';
    const TRANSFER              = 'transfer';
    const REFUNDS               = 'refunds';
    const TRANSACTION           = 'transaction';
    const OFFERS                = 'offers';
    const SETTLEMENT            = 'settlement';

    // Tells us whether this payment is a initial or auto recurring type
    const RECURRING_TYPE        = 'recurring_type';

    const METADATA              = 'metadata';
    const BILLING_ADDRESS       = 'billing_address';

    const DEVICE_FINGERPRINT    = 'device_fingerprint';

    const RECEIVER              = 'receiver';
    const AADHAAR               = 'aadhaar';
    const BANK_ACCOUNT          = 'bank_account';
    const NAME                  = 'name';
    const IFSC                  = 'ifsc';
    const ACCOUNT_NUMBER        = 'account_number';

    const PROVIDER              = 'provider';

    const OFFER_ID              = 'offer_id';
    const SETTLED_BY            = 'settled_by';

    const AUTHENTICATION_GATEWAY = 'authentication_gateway';

    const VIRTUAL_ACCOUNT_ID     = 'virtual_account_id';
    const VIRTUAL_ACCOUNT        = 'virtual_account';

    const INTL_BANK_TRANSFER     = 'intl_bank_transfer';

    const ERROR_SOURCE           = 'error_source';

    const ERROR_STEP             = 'error_step';

    const ERROR_REASON           = 'error_reason';

    const DETAILED_REASON        = 'detailed_reason';

    const VA_TRANSACTION_ID       = 'va_transaction_id';

    const ORDER                  = 'order';

    const GATEWAY_DATA             = 'gateway_data';

    const GATEWAY_TXN_ID             = 'gateway_txn_id';

    const GATEWAY_ERROR_CODE        = 'gateway_error_code';
    const GATEWAY_ERROR_DESCRIPTION = 'gateway_error_description';

    const REFUND_AUTHORIZED_PAYMENT = 'refund_authorized_payment';
    const CAPTURE_REFUNDED_PAYMENT  = 'capture_refunded_payment';

    const FUNDS_SPLIT = 'funds_split';

    const OPTIMIZER_PROVIDER = 'optimizer_provider';
    const WALLET_AMOUNT = 'wallet_amount';
    const WALLET_USER_ID = 'wallet_user_id';
    const SPLIT_AMOUNT = 'split_amount';
    const FLOW         = 'flow';

    // constants and defaults
    const CURRENCY_LENGTH                   = 3;
    const MIN_PAYMENT_AMOUNT                = 100;
    const PAYMENT_TIMEOUT_DEFAULT_OLD       = 720;      // 12 Mins
    const PAYMENT_TIMEOUT_BILLDESK          = 259200;   // 3 Days
    const PAYMENT_TIMEOUT_NETBANKING        = 4500;     // 75 Mins
    const PAYMENT_TIMEOUT_WALLET            = 4500;     // 75 Mins
    const PAYMENT_TIMEOUT_DEFAULT           = 2700;     // 45 Mins
    const PAYMENT_TIMEOUT_CARDLESSEMI       = 1800;     //30 Mins
    const PAYMENT_TIMEOUT_FILE_BASED_DEBIT  = 604800;   // 7 Days
    const BASE_CURRENCY                     = 'base_currency';
    const PAYMENT_TIMEOUT_NACH              = 86400 * 30;  // 30 Days
    const PAYMENT_TIMEOUT_UPI_RECURRING     = 259200;   // 3 Days
    const PAYMENT_TIMEOUT_CARD_RECURRING_MANDATE = 259200;   // 3 Days
    const PAYMENT_TIMEOUT_CARD_RECURRING_MANDATE_WITH_AFA = 259200;   // 4 Days
    const PAYMENT_TIMEOUT_COD_PENDING       = 86400 * 45; // 45days
    const MCC_MARKDOWN_PERCENTAGE           = 1;
    const PAYMENT_TIMEOUT_EMANDATE_RECURRING = 604800;   // 7 Days
    const PAYMENT_UPI_COLLECT_MAX_EXPIRY_WINDOW = 345600; // 4 Days
    const PAYMENT_TIMEOUT_NETBANKING_CORP      =  432000;  // 5 days

    // payment services
    const API                               = 0;
    const CORE_PAYMENT_SERVICE              = 1;
    const CARD_PAYMENT_SERVICE              = 2;
    const REARCH_CARD_PAYMENT_SERVICE       = 5;
    const NB_PLUS_SERVICE                   = 3;
    const UPI_PAYMENT_SERVICE               = 4;
    const NB_PLUS_SERVICE_PAYMENTS          = 6;
    const REARCH_UPI_PAYMENT_SERVICE        = 7;
    const REARCH_PCP_PAYMENT_SERVICE        = 8;
    const EMANDATE_PAYMENT_SERVICE          = 9;

    const REARCH_OPTIMISER_PAYMENT_SERVICE  = 100;

    const REARCH_PAYMENT_SERVICES           = [self::REARCH_CARD_PAYMENT_SERVICE, self::REARCH_UPI_PAYMENT_SERVICE, self::REARCH_PCP_PAYMENT_SERVICE,self::NB_PLUS_SERVICE_PAYMENTS, self::REARCH_OPTIMISER_PAYMENT_SERVICE];
    const FORMATTED_AMOUNT                  = 'formatted_amount';
    const FORMATTED_CREATED_AT              = 'formatted_created_at';
    const HOSTED_TIME_FORMAT                = 'j M Y';

    const UPI_PROVIDER                      = 'upi_provider';

    // To identify GPay Card Payments

    protected $application                  = null;

    // To identify GPay payments in verify flow
    protected $isGooglePayMethodChangeApplicable = false;

    const ACCOUNT_ID                        = 'account_id';

    const CHARGE_ACCOUNT                    = 'charge_account';

    const CHARGE_ACCOUNT_MERCHANT           = 'charge_account_merchant';

    const APP_PRESENT                       = 'app_present';

    const DCC                               = 'dcc';
    const MCC                               = 'mcc';
    const DCC_MARKUP_AMOUNT                 = 'dcc_markup_amount';
    const FOREX_RATE_RECEIVED               = 'forex_rate_received';
    const FOREX_RATE_APPLIED                = 'forex_rate_applied';

    const FORCE_TERMINAL_ID                 = 'force_terminal_id';
    const GOOGLE_PAY                        = 'google_pay';

    const FILE        = 'file';
    const SIGNED_FORM = 'signed_form';
    const NACH        = 'nach';
    const RRN         = 'rrn';
    const HDFC        = 'hdfc';

    // meta field in the input
    const META                              = 'meta';

    // cancellation reasons for unintended payments.
    const UNINTENDED_PAYMENT_OPT_OUT         = 'unintended_payment_opt_out';
    const UNINTENDED_PAYMENT_EXPIRED         = 'unintended_payment_expired';

    const B2BExportInvoice                   = "b2b_export_invoice";

    const PAYER_ACCOUNT_TYPE                 = "payer_account_type";
    const UPI                                = "upi";
    const UPI_METADATA                       = "upi_metadata";

    const feeCurrencyAmount                      = "fee_currency_amount";

    const REWARD_ID                               = "reward_id";
    const REWARD                                  = "reward";
    const PROCESSING_FEE                          = "processing_fee";


    const OFFER_BENEFITS                          = 'offer_benefits';

    /**
     * This constant is only used during creation of dummy payment array for routing
     */
    const GST_QR                                  = 'gst_qr';

    const FEE_MODEL_OVERRIDE_MERCHANT_IDS = [Pricing\BuyPricing::BPCL_TEST_MERCHANT_ID, Pricing\BuyPricing::BPCL_MERCHANT_ID, Pricing\BuyPricing::BPCL_MERCHANT_ID2, Pricing\BuyPricing::BPCL_MERCHANT_ID3 ];

    protected static $sign      = 'pay';

    protected $entity           = 'payment';

    protected $metadata         = [];

    protected $billingAddress = [];

    protected $generateIdOnCreate = true;

    protected $forceTerminalId    = null;

    protected $skipCvvCheckFlag = null;

    protected $issuer;

    protected $googlePayMethods   = [];

    protected $googlePayCardNetworks   = [];

    const SODEXO = 'sodexo';

    const GatewayMerchantId = 'gateway_merchant_id';
    const GatewayTerminalId = 'gateway_terminal_id';
    const DeviceId          = 'device_id';
    const InternalStatus     = 'internal_status';

    const REFUND_UNEXPECTED_PAYMENT = 'refund_unexpected_payment';

    protected $fillable = [
        self::AMOUNT_CAPTURED,
        self::ID,
        self::AMOUNT,
        self::METHOD,
        self::EMI_PLAN_ID,
        self::BANK,
        self::WALLET,
        self::CURRENCY,
        self::DESCRIPTION,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::CALLBACK_URL,
        self::FEE,
        self::TAX,
        self::RECURRING,
        self::SAVE,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::REFERENCE1,
        self::REFERENCE2,
        self::REFERENCE16,
        self::REFERENCE17,
        self::CPS_ROUTE,
        self::DISPUTED,
        self::AUTH_TYPE,
        self::RECURRING_TYPE,
        self::AUTHENTICATION_GATEWAY,
        self::FEE_BEARER,
        self::REWARD_ID,
        self::REWARD,
        self::DeviceId,
        self::SOURCE_CHANNEL,
        self::GST_QR, // Added here to support entry in dummy payment array for routing
    ];

    protected $visible = [
        self::AMOUNT_CAPTURED,
        self::ID,
        self::PUBLIC_ID,
        self::DeviceId,
        self::METHOD,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::BASE_CURRENCY,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::BASE_AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::CURRENCY,
        self::AMOUNT_PAIDOUT,
        self::STATUS,
        self::TWO_FACTOR_AUTH,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::DESCRIPTION,
        self::BANK,
        self::WALLET,
        self::EMI_PLAN_ID,
        self::EMI_SUBVENTION,
        self::CUSTOMER_ID,
        self::GLOBAL_CUSTOMER_ID,
        self::APP_TOKEN,
        self::TOKEN_ID,
        self::GLOBAL_TOKEN_ID,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ERROR_SOURCE,
        self::ERROR_STEP,
        self::ERROR_REASON,
        self::CANCELLATION_REASON,
        self::AUTHORIZED_AT,
        self::CAPTURED_AT,
        self::GATEWAY,
        self::CARD_ID,
        self::MERCHANT_ID,
        self::TERMINAL_ID,
        self::GATEWAY_PROVIDER,
        self::BATCH_ID,
        self::REFERENCE1,
        self::REFERENCE2,
        self::REFERENCE14,
        self::REFERENCE16,
        self::REFERENCE17,
        self::CPS_ROUTE,
        self::ACQUIRER_DATA,
        self::TRANSFER_ID,
        self::PAYMENT_LINK_ID,
        self::RECEIVER_ID,
        self::RECEIVER_TYPE,
        self::TRANSACTION_ID,
        self::AUTO_CAPTURED,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::INTERNATIONAL,
        self::SIGNED,
        self::VERIFIED,
        self::GATEWAY_CAPTURED,
        self::VERIFY_BUCKET,
        self::VERIFY_AT,
        self::CALLBACK_URL,
        self::RECURRING,
        self::SAVE,
        self::FEE,
        self::MDR,
        self::TAX,
        self::SETTLED_BY,
        self::OTP_ATTEMPTS,
        self::OTP_COUNT,
        self::LATE_AUTHORIZED,
        self::SUBSCRIPTION_ID,
        self::CONVERT_CURRENCY,
        self::AUTH_TYPE,
        self::DISPUTED,
        self::RECURRING_TYPE,
        self::ACKNOWLEDGED_AT,
        self::REFUND_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::AUTHENTICATION_GATEWAY,
        self::FEE_BEARER,
        self::SOURCE_CHANNEL,
        self::UPI,
        self::PROVIDER,
        self::UPI_METADATA,
        self::REFUND_AUTHORIZED_PAYMENT,
        self::CAPTURE_REFUNDED_PAYMENT,
        self::FUNDS_SPLIT,
        self::REWARD,
        self::REWARD_ID,
        self::GST_QR, // Added here to support entry in dummy payment array for routing
        self::REFUND_UNEXPECTED_PAYMENT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::AMOUNT_CAPTURED,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::BASE_CURRENCY,
        self::STATUS,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::TERMINAL_ID,
        self::LATE_AUTHORIZED,
        self::INTERNATIONAL,
        self::METHOD,
        self::REFUNDS,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::OFFERS,
        self::DESCRIPTION,
        self::CARD_ID,
        self::CARD,
        self::BANK,
        self::WALLET,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::NOTES,
        self::FEE,
        self::TAX,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ERROR_SOURCE,
        self::ERROR_STEP,
        self::ERROR_REASON,
        self::GATEWAY_DATA,
        self::ACQUIRER_DATA,
        self::GATEWAY_PROVIDER,
        // self::SUBSCRIPTION_ID,
        self::EMI,
        self::SENDER_ADDRESS,
        self::EMI_PLAN,
        self::DISPUTES,
        self::CREATED_AT,
        self::TRANSFER,
        self::ACCOUNT_ID,
        self::FEE_BEARER,
        self::PROVIDER,
        self::SETTLED_BY,
        self::OPTIMIZER_PROVIDER,
        self::TOKEN,
        self::UPI,
        self::UPI_METADATA,
        self::REWARD,
        self::REWARD_ID,
        self::AMOUNT_CAPTURED
    ];

    protected $webhook = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::BASE_CURRENCY,
        self::STATUS,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::TERMINAL_ID,
        self::LATE_AUTHORIZED,
        self::INTERNATIONAL,
        self::METHOD,
        self::REFUNDS,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::OFFERS,
        self::DESCRIPTION,
        self::CARD_ID,
        self::CARD,
        self::BANK,
        self::WALLET,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::NOTES,
        self::FEE,
        self::TAX,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ERROR_SOURCE,
        self::ERROR_STEP,
        self::ERROR_REASON,
        self::GATEWAY_DATA,
        self::ACQUIRER_DATA,
        self::GATEWAY_PROVIDER,
        self::EMI,
        self::EMI_PLAN,
        self::DISPUTES,
        self::CREATED_AT,
        self::TRANSFER,
        self::ACCOUNT_ID,
        self::FEE_BEARER,
        self::PROVIDER,
        self::SETTLED_BY,
        self::OPTIMIZER_PROVIDER,
        self::TOKEN,
        self::UPI,
        self::REWARD_ID,
        self::REWARD
    ];

    protected $reconAppInternal = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::STATUS,
        self::MERCHANT_ID
    ];

    /**
     * Relations to be returned when receiving expand[] query param in fetch
     * (eg. transaction, transaction.settlement with payment fetch)
     *
     * @var array
     */
    protected $expanded = [
        self::TRANSACTION,
        self::SETTLEMENT
    ];

    /**
     * Fields exposed to hosted page(invoice, subscriptions etc)
     * where there would mostly be no authentication.
     *
     * @var array
     */
    protected $hosted = [
        self::ID,
        self::STATUS,
        self::METHOD,
        self::AMOUNT,
        self::CREATED_AT,
    ];

    protected $adminRestricted = [
        self::ID,
        self::ACQUIRER_DATA,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::ORDER_ID,
        self::AMOUNT_REFUNDED,
        self::REFUND_AT,
        self::CREATED_AT,
        self::AUTHENTICATED_AT,
        self::AUTHORIZED_AT,
        self::UPDATED_AT,
        self::ERROR_DESCRIPTION,
        self::ERROR_SOURCE,
        self::ERROR_STEP,
        self::ERROR_REASON,
        Terminal\Entity::GATEWAY_TERMINAL_ID,
        self::FEE_BEARER,
    ];

    protected $adminRestrictedWithFeature = [
        'axis_org' => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::AMOUNT,
            Entity::METHOD,
            Entity::STATUS,
            Entity::SAVE,
            Entity::TERMINAL_ID,
            Terminal\Entity::GATEWAY_TERMINAL_ID,
            Entity::CREATED_AT,
            Entity::NOTES,
            Entity::EMAIL,
            'mode',
        ]
    ];

    protected $publicCustomer = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::CREATED_AT,
        self::CURRENCY,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::BASE_AMOUNT,
        self::BASE_CURRENCY,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::CARD_ID,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::SUBSCRIPTION_ID,
        self::AMOUNT_TRANSFERRED,
        self::GATEWAY_PROVIDER,
        self::GATEWAY_DATA,
        self::ACCOUNT_ID,
        self::TERMINAL_ID,
        self::FEE_BEARER,
        self::LATE_AUTHORIZED,
        self::DETAILED_REASON,
        self::PROVIDER,
        self::DCC,
        self::MCC,
        self::SETTLED_BY,
        self::OPTIMIZER_PROVIDER,
        self::INTERNATIONAL,
        self::FEE,
        self::TAX,
        self::UPI_METADATA,
        self::REWARD_ID,
    ];

    protected $appends = [self::PUBLIC_ID, self::CAPTURED, self::ACQUIRER_DATA, self::GATEWAY_PROVIDER];

    protected static $modifiers = [
        self::EMAIL,
        self::CONTACT,
        self::BANK,
        self::RECURRING,
        self::IFSC,
        self::VPA,
        'method_based_input',
        'convert_empty_strings_to_null',
        self::PREFERRED_AUTH,
        self::SAVE,
    ];

    protected static $generators = [
        'recurring',
        self::METADATA,
        self::VERIFY_AT,
        self::WALLET,
        self::BILLING_ADDRESS,
    ];

    protected $dates = [
        self::UPDATED_AT,
        self::CREATED_AT,
        self::AUTHENTICATED_AT,
        self::AUTHORIZED_AT,
        self::CAPTURED_AT,
        self::VERIFY_AT,
    ];

    protected $hiddenInReport = [self::ACQUIRER_DATA];

    protected $defaults = [
        self::STATUS               => Status::CREATED,
        self::REFUND_STATUS        => RefundStatus::NULL,
        self::SOURCE_CHANNEL       => PAYMENT\Constant::ONLINE,
        self::NOTES                => [],
        self::DESCRIPTION          => null,
        self::AMOUNT_REFUNDED      => 0,
        self::BASE_AMOUNT_REFUNDED => 0,
        self::AMOUNT_TRANSFERRED   => 0,
        self::AMOUNT_PAIDOUT       => 0,
        self::SIGNED               => 0,
        self::GATEWAY              => null,
        self::VERIFIED             => null,
        self::GATEWAY_CAPTURED     => null,
        self::CAPTURED_AT          => null,
        self::AUTO_CAPTURED        => 0,
        self::ON_HOLD              => 0,
        self::ON_HOLD_UNTIL        => null,
        self::CAPTURE              => false,
        self::SAVE                 => false,
        self::FEE                  => null,
        self::MDR                  => null,
        self::TAX                  => null,
        self::OTP_ATTEMPTS         => null,
        self::OTP_COUNT            => null,
        self::EMI_PLAN_ID          => null,
        self::LATE_AUTHORIZED      => 0,
        self::RECURRING            => false,
        self::INTERNATIONAL        => null,
        self::VERIFY_BUCKET        => null,
        self::TERMINAL_ID          => null,
        self::TRANSFER_ID          => null,
        self::PAYMENT_LINK_ID      => null,
        self::DISPUTED             => false,
        self::RECURRING_TYPE       => null,
        self::AUTH_TYPE            => null,
        self::ACKNOWLEDGED_AT      => null,
        self::REFUND_AT            => null,
        self::CPS_ROUTE            => self::API,
        self::AUTHENTICATION_GATEWAY => null,
        self::FEE_BEARER           => Merchant\FeeBearer::PLATFORM,
        self::IS_PUSHED_TO_KAFKA           => null,
        self::CONVENIENCE_FEE      => null,
        self::CONVENIENCE_FEE_GST  => null
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::BASE_AMOUNT_REFUNDED,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::AMOUNT_PAIDOUT,
        self::FEE,
        self::TAX,
        self::AMOUNT_CAPTURED,
    ];

    protected $casts = [
        self::RECURRING            => 'bool',
        self::BASE_AMOUNT          => 'int',
        self::BASE_AMOUNT_REFUNDED => 'int',
        self::AMOUNT_TRANSFERRED   => 'int',
        self::AMOUNT_AUTHORIZED    => 'int',
        self::AMOUNT_REFUNDED      => 'int',
        self::AMOUNT_PAIDOUT       => 'int',
        self::AUTO_CAPTURED        => 'bool',
        self::ON_HOLD              => 'bool',
        self::ON_HOLD_UNTIL        => 'int',
        self::SIGNED               => 'bool',
        self::AMOUNT               => 'int',
        self::FEE                  => 'int',
        self::TAX                  => 'int',
        self::SAVE                 => 'bool',
        self::INTERNATIONAL        => 'bool',
        self::GATEWAY_CAPTURED     => 'bool',
        self::LATE_AUTHORIZED      => 'bool',
        self::CONVERT_CURRENCY     => 'bool',
        self::DISPUTED             => 'bool',
        self::VERIFY_BUCKET        => 'int',
        self::CPS_ROUTE            => 'int',
    ];

    // list of cancellation reasons for unintended payments
    protected $unintendedPaymentCancellationReasons = [
        self::UNINTENDED_PAYMENT_OPT_OUT,
        self::UNINTENDED_PAYMENT_EXPIRED,
    ];

    // window in secs, used to fetch payments with same checkout id
    const PAYMENT_WINDOW = 1800;

    const DUMMY_EMAIL = 'void@razorpay.com';

    const DUMMY_PHONE = '+919999999999';

    const DUMMY_VPA = 'dummy@razorpay';

    protected $lateBalanceUpdate = false;

    protected $ignoredRelations = [
        self::ORDER,
        // Required as customer entity will be created via CMS and may not be present in API DB
        ConstantsEntity::CUSTOMER
    ];

    protected array $sensitiveFields = [
        self::INTERNATIONAL,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::FEE,
        self::TAX,
        self::AUTH_CODE
    ];

    // --------------------- Modifiers ---------------------------------------------

    protected function modifyEmail(& $input)
    {
        if (empty($input['email']) === true)
        {
            if ($this->isEmailOptionalForPartnerPayment() || $this->merchant->isEmailOptional())
            {
                $input['email'] = self::DUMMY_EMAIL;
            }
        }
    }

    protected function modifyVpa(& $input)
    {
        if (empty($input[self::VPA]) === false)
        {
            $vpaParts = explode('@', $input[self::VPA]);

            if (count($vpaParts) > 1)
            {
                $lastElement = count($vpaParts) - 1;

                $vpaParts[$lastElement] = strtolower($vpaParts[$lastElement]);

                $input[self::VPA] = implode('@', $vpaParts);
            }
        }
    }

    protected function modifyContact(& $input)
    {
        if (isset($input[Entity::UPI_PROVIDER]) === true)
        {
            return null;
        }

        if (empty($input['contact']) === true)
        {
            $isPhoneOptional = $this->merchant->isPhoneOptional();

            if ($isPhoneOptional === true)
            {
                $input['contact'] = self::DUMMY_PHONE;
            }
        }

        $contact = & $input['contact'];

        if (is_string($contact) === false)
        {
            return null;
        }

        // Do not modify contact in case of bank transfer and bharat qr
        // because in these cases contact is not passed in payment request
        // input but rather it is set internally from customer table.
        //
        // If Receiver is present it either bank transfer or bharat qr payment.
        if (empty($input['receiver']) === false)
        {
            return $input['contact'];
        }

        $contact = str_replace(' ', '', $contact);
        $contact = str_replace('-', '', $contact);
        $contact = str_replace('(', '', $contact);
        $contact = str_replace(')', '', $contact);

        // Remove the 0 at the start
        if ((strlen($contact) > 1) and
            ($contact[0] === '0'))
        {
            $contact = substr($contact, 1);
        }

        return $contact;
    }

    protected function modifyRecurring(& $input)
    {
        if (((isset($input[Entity::METHOD]) === true) and
             ($input[Entity::METHOD] === Method::EMANDATE)) or
            (empty($input[Entity::SUBSCRIPTION_ID]) === false))
        {
            $input['recurring'] = '1';
        }
    }

    protected function modifyIfsc(& $input)
    {
        if (isset($input[self::BANK_ACCOUNT][self::IFSC]) === true)
        {
            $input[self::BANK_ACCOUNT][self::IFSC] = strtoupper($input[self::BANK_ACCOUNT][self::IFSC]);
        }
    }

    protected function modifyMethodBasedInput(& $input)
    {
        if (isset($input['method']) === false)
        {
            return;
        }

        if (in_array($input['method'], Method::$bankMethods, true) === false)
        {
            unset($input['bank']);
        }

        if ($input['method'] !== Method::EMI)
        {
            unset($input['emi_duration']);
        }

        if ($input['method'] !== Method::WALLET)
        {
            unset($input['wallet']);
        }

        if ($input['method'] !== Method::UPI)
        {
            unset($input['vpa']);
        }
    }

    protected function modifyConvertEmptyStringsToNull(& $input)
    {
        $array = [
            Entity::CUSTOMER_ID,
            Entity::TOKEN,
            Entity::APP_TOKEN
        ];

        foreach ($array as $key)
        {
            if (empty($input[$key]))
            {
                unset($input[$key]);
            }
        }
    }

    protected function modifyBank(& $input)
    {
        if ((isset($input['method'])) and
            (in_array($input['method'], Method::$bankMethods, true) === false))
        {
            unset($input['bank']);
        }
    }

    protected function modifyWallet(& $input)
    {
        if ((isset($input['method'])) and
            ($input['method'] !== Method::WALLET))
        {
            unset($input['wallet']);
        }
    }

    protected function modifyPreferredAuth(&$input)
    {
        //
        // We give preference to auth type
        //
        if (empty($input[Entity::AUTH_TYPE]) === false)
        {
            unset($input[Entity::PREFERRED_AUTH]);
            return;
        }

        if (isset($input[Entity::PREFERRED_AUTH]) === false)
        {
            return;
        }

        $uniqueAuthentications = array_unique((array) $input[Entity::PREFERRED_AUTH]);

        unset($input[Entity::PREFERRED_AUTH]);

        $merchant = $this->merchant;

        $preferredAuth = [];

        foreach ($uniqueAuthentications as $authType)
        {
            if (AuthType::isFeatureBasedAuthEnabled($merchant, $authType) === true)
            {
                $preferredAuth[] = $authType;
            }
        }

        if (empty($preferredAuth) === false)
        {
            //
            // We add 3ds auth type by default for card/emi payments and
            // only if preferredAuth is not empty.
            //
            if (($input[Entity::METHOD] === Method::CARD) or
                ($input[Entity::METHOD] === Method::EMI))
            {
                $preferredAuth[] = AuthType::_3DS;
            }

            $input[Entity::PREFERRED_AUTH] = $preferredAuth;
        }
    }

    protected function modifySave(&$input)
    {
        if (isset($input[self::SAVE]) && is_bool($input[self::SAVE])) {
            $input[self::SAVE] = $input[self::SAVE] ? '1' : '0';
        }
    }

    // --------------------- Modifiers Ends ----------------------------------------

    // --------------------- Generators Ends ---------------------------------------

    protected function generateMetadata(&$input)
    {
        $this->metadata = $input['_'] ?? [];

        // Overriding extra attributes for S2S integration
        $this->metadata['ip'] = $input['ip'] ?? null;
        $this->metadata['user_agent'] = $input['user_agent'] ?? null;
        $this->metadata['preferred_auth'] = $input['preferred_auth'] ?? null;

        if (empty($input[self::NOTES]) === false)
        {
            $this->setIntegrationMetadataUsingNotes($input[self::NOTES]);
        }

        // We should only set referer if input['referer'] is defined
        // and metadata['referer'] is false because checkout also
        // sends us the referer info and we don't want to override it
        if ((isset($input['referer']) === true) and
            (isset($this->metadata['referer']) === false))
        {
            $this->metadata['referer'] = $input['referer'];
        }

        if (isset($input[Entity::UPI_PROVIDER]) === true)
        {
            $this->metadata[Entity::UPI_PROVIDER] = $input[Entity::UPI_PROVIDER];
        }

        if (isset($input[Entity::APP_PRESENT]) === true)
        {
            $this->metadata[Entity::APP_PRESENT] = (bool) $input[Entity::APP_PRESENT];
        }
    }

    /**
     * A lot of our plugins make their payments recognizable by sending
     * their own order id in the payment notes, eg. prestashop_order_id.
     * This is not a great way of identifying payments, since notes should
     * be a merchant-controlled field, and not used for our own logic.
     *
     * Ideally, we should recognise integrations from the '_' metadata sent
     * in the payment request. Until all the plugins can be updated,
     * however, we use the notes values to set integration in database.
     *
     * Notes from the order entity can also be used to identify integration.
     */
    public function setIntegrationMetadataUsingNotes(array $notes)
    {
        // If integration is already set by some other flow, don't overwrite it.
        if (empty($this->metadata[Analytics\Entity::INTEGRATION]) === false)
        {
            return;
        }

        // Check for presence of integration_order_id in notes
        foreach (Metadata::INTEGRATION_VALUES as $integration => $index)
        {
            $integrationOrderId = $integration . '_order_id';

            if (empty($notes[$integrationOrderId]) === false)
            {
                $this->metadata[Analytics\Entity::INTEGRATION] = $integration;

                return;
            }
        }

        // Some version of magento have magento_trans_id and not magento_order_id
        if (empty($notes['magento_trans_id']) === false)
        {
            $this->metadata[Analytics\Entity::INTEGRATION] = Metadata::MAGENTO;

            return;
        }

        // Shopify has its own format, sending the
        // name of the integration under notes[platform].
        if ((empty($notes['platform']) === false) and
            ($notes['platform'] === Metadata::SHOPIFY))
        {
            $this->metadata[Analytics\Entity::INTEGRATION] = Metadata::SHOPIFY;

            return;
        }
    }

    protected function generateRecurring($input)
    {
        if ($input[Entity::METHOD] === Method::EMANDATE)
        {
            $this->setAttribute(self::RECURRING, 1);
        }
    }

    protected function generateVerifyAt($input)
    {
        // We want to set verify at as created at + 120
        // so that for payments with status = created
        // we can pick them after 2 min for verify.
        $this->setVerifyAt(time() + 120);
    }

    protected function generateWallet($input)
    {
        if (($input[Entity::METHOD] === Method::CARDLESS_EMI) or
            ($input[Entity::METHOD] === Method::PAYLATER) or
            ($input[Entity::METHOD] === Method::APP) or
            ($input[Entity::METHOD] === Method::INTL_BANK_TRANSFER))
        {
            $this->setAttribute(self::WALLET, $input[self::PROVIDER]);
        }

        $isPACBPartnerFlow = (new \RZP\Models\Partner\Service())->isPaCbFeatureEnabledForPartner();
        if ($isPACBPartnerFlow && isset($input['bank_transfer']['type'])) {
            $this->setAttribute(self::WALLET, $input['bank_transfer']['type']);
        }
    }

    protected function generateBillingAddress($input)
    {
        if (isset($input[self::BILLING_ADDRESS]))
        {
            $this->billingAddress = $input[self::BILLING_ADDRESS];
        }
    }

    // --------------------- Generators Ends ---------------------------------------

    // ----------------------- Setters ---------------------------------------------

    public function setInternational()
    {
        if ($this->isGooglePayCard() === true)
        {
            return;
        }

        $isInternational = $this->isMethodCardOrEmi() ? $this->card->isInternational() : ($this->isMethodInternationalApp() ? true : false);

        if ($this->isB2BExportCurrencyCloudPayment())
        {
            $isInternational = true;
        }

        if ($isInternational === false) {
            $isInternational = (new \RZP\Models\Partner\Service())->isPaCbFeatureEnabledForPartner();
        }

        $this->setAttribute(self::INTERNATIONAL, $isInternational);
    }

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setAmountCaptured(int $amountCaptured){
        $this->setAttribute(self::AMOUNT_CAPTURED, $amountCaptured);
    }

    public function setBaseAmount(int $amount)
    {
        $this->setAttribute(self::BASE_AMOUNT, $amount);
    }

    public function setVpa(string $vpa)
    {
        $this->setAttribute(self::VPA, $vpa);
    }

    public function setAmountAuthorized()
    {
        $authAmount = $this->getAttribute(self::AMOUNT);

        $this->setAttribute(self::AMOUNT_AUTHORIZED, $authAmount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setTwoFactorAuth($status)
    {
        $this->setAttribute(self::TWO_FACTOR_AUTH, $status);
    }

    public function setRefundStatus($status)
    {
        $this->setAttribute(self::REFUND_STATUS, $status);
    }

    public function setAmountRefunded($amount)
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, $amount);
    }

    public function setBaseAmountRefunded($amount)
    {
        $this->setAttribute(self::BASE_AMOUNT_REFUNDED, $amount);
    }

    public function setAmountPaidout(int $amount)
    {
        $this->setAttribute(self::AMOUNT_PAIDOUT, $amount);
    }

    /**
     * This should be kept as protected so the gateway is only
     * set via associateTerminal function
     *
     * TODO: Temporarily made public for VA payments to set gateway externally
     * This should be removed after VA terminals are used in payment flow as well
     */
    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setError($errorCode, $errorDesc, $internalErrorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDesc);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);

        if ($internalErrorCode !== null)
        {
            $method = $this->getMethod();

            if($this->isUpiRecurring())
            {
                $method = 'upi_autopay';
            }

            $this->setDetailedError($internalErrorCode, $method);
        }
    }

    public function setDetailedError($code, $method)
    {
        $error = new Error($this->getAttribute(self::INTERNAL_ERROR_CODE));

        $error->setDetailedError($code, $method);

        $this->setAttribute(self::ERROR_DESCRIPTION,$error->getEnglishDescription());
    }

    public function setEmandateErrorDesc($errorDesc)
    {
        $finalErrorDesc = $this->getErrorDescription() . $errorDesc;

        $this->setAttribute(self::ERROR_DESCRIPTION, $finalErrorDesc);
    }

    public function setInternalErrorCode($internalErrorCode)
    {
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    public function setCancellationReason($cancellationReason)
    {
        $this->setAttribute(self::CANCELLATION_REASON, $cancellationReason);
    }

    public function setCaptureTimestamp()
    {
        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->setAttribute(self::CAPTURED_AT, $timestamp);
    }

    public function setAuthenticatedTimestamp($authenticateTimestamp = null)
    {
        if (is_null($authenticateTimestamp))
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $this->setAttribute(self::AUTHENTICATED_AT, $timestamp);
        }
        else
        {
            $this->setAttribute(self::AUTHENTICATED_AT, $authenticateTimestamp);
        }
    }

    public function setAuthenticatedAtNull()
    {
        $this->setAttribute(self::AUTHENTICATED_AT, null);
    }

    public function setAuthorizeTimestamp($authTimestamp = null)
    {
        if (is_null($authTimestamp))
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $this->setAttribute(self::AUTHORIZED_AT, $timestamp);
        }
        else
        {
            $this->setAttribute(self::AUTHORIZED_AT, $authTimestamp);
        }
    }

    public function setAuthorizedAtNull()
    {
        $this->setAttribute(self::AUTHORIZED_AT, null);
    }

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setForceTerminalId(string $terminalId)
    {
        $this->forceTerminalId = $terminalId;
    }

    /**
     * Recurring Type is null by default, and will
     * be set to initial or auto based on use case
     *
     * @param $type
     * @throws Exception\InvalidArgumentException
     */
    public function setRecurringType(string $type = null)
    {
        if ($type !== null)
        {
            RecurringType::validateRecurringType($type);
        }

        $this->setAttribute(self::RECURRING_TYPE, $type);
    }

    public function isRecurringTypeAuto()
    {
        return ($this->getAttribute(self::RECURRING_TYPE) === RecurringType::AUTO);
    }

    public function isRecurringTypeInitial()
    {
        return ($this->getAttribute(self::RECURRING_TYPE) === RecurringType::INITIAL);
    }

    public function isRecurringTypeCardChange()
    {
        return ($this->getAttribute(self::RECURRING_TYPE) === RecurringType::CARD_CHANGE);
    }

    public function setSigned($signed = true)
    {
        $this->setAttribute(self::SIGNED, $signed);
    }

    public function setOnHold(bool $onHold)
    {
        $this->setAttribute(self::ON_HOLD, $onHold);
    }

    public function setOnHoldUntil($holdUntil)
    {
        $this->setAttribute(self::ON_HOLD_UNTIL, $holdUntil);
    }

    public function setAutoCapturedTrue()
    {
        $this->setAttribute(self::AUTO_CAPTURED, true);
    }

    public function setCaptureTrue()
    {
        $this->setAttribute(self::CAPTURE, true);
    }

    public function setAutoCaptured($autoCaptured)
    {
        $this->setAttribute(self::AUTO_CAPTURED, $autoCaptured);
    }

    public function setNonVerifiable()
    {
        $this->setVerifyBucket(null);

        $this->setVerifyAt(null);
    }

    public function setVerifyBucket($verifyBucket = 0)
    {
        $this->setAttribute(self::VERIFY_BUCKET, $verifyBucket);
    }

    public function setVerifyAt($verifyAt)
    {
        $this->setAttribute(self::VERIFY_AT, $verifyAt);
    }

    public function setVerified($verified)
    {
        $this->setAttribute(self::VERIFIED, $verified);
    }

    public function setGatewayCaptured($gatewayCaptured)
    {
        $this->setAttribute(self::GATEWAY_CAPTURED, $gatewayCaptured);
    }

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFee($fee)
    {
        $this->setAttribute(self::FEE, $fee);
    }

    public function setMdr(int $mdr)
    {
        $this->setAttribute(self::MDR, $mdr);
    }

    public function setRecurring($recurring)
    {
        $this->setAttribute(self::RECURRING, $recurring);
    }

    public function setSettledBy($settledBy)
    {
        $this->setAttribute(self::SETTLED_BY, $settledBy);
    }

    public function setGatewayProvider($gatewayProvider)
    {
        $this->setAttribute(self::GATEWAY_PROVIDER, $gatewayProvider);
    }

    public function setErrorNull()
    {
        $this->setAttribute(self::ERROR_CODE, null);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, null);
        $this->setAttribute(self::ERROR_DESCRIPTION, null);

        // reset gateway error code and description in reference17.
        if (empty($this->getReference17()) === false)
        {
            $oldRef17 = json_decode($this->getReference17(), true) ?? [];

            if(empty($oldRef17) === true)
            {
                return;
            }

            unset($oldRef17[self::GATEWAY_ERROR_CODE]);
            unset($oldRef17[self::GATEWAY_ERROR_DESCRIPTION]);

            if(empty($oldRef17) === true)
            {
                $this->setAttribute(self::REFERENCE17, null);
            }
            else
            {
                $this->setAttribute(self::REFERENCE17, json_encode($oldRef17));
            }
        }
    }

    public function setEmiPlanId($planId)
    {
        $this->setAttribute(self::EMI_PLAN_ID, $planId);
    }

    public function setAuthenticationGateway($authenticationGateway)
    {
        $this->setAttribute(self::AUTHENTICATION_GATEWAY, $authenticationGateway);
    }

    public function setOtpAttempts($attempts)
    {
        $this->setAttribute(self::OTP_ATTEMPTS, $attempts);
    }

    public function setOtpCount($count)
    {
        $this->setAttribute(self::OTP_COUNT, $count);
    }

    public function setEmailAttribute($email)
    {
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    public function setSave($save)
    {
        $this->setAttribute(self::SAVE, $save);
    }

    public function incrementOtpAttempts()
    {
        $attempts = $this->getOtpAttemptsAttribute() + 1;

        $this->setOtpAttempts($attempts);
    }

    public function incrementOtpCount()
    {
        $count = $this->getOtpCountAttribute() + 1;

        $this->setOtpCount($count);
    }

    public function setLateAuthorized($lateAuthorized)
    {
        $this->setAttribute(self::LATE_AUTHORIZED, $lateAuthorized);
    }

    public function setConvertCurrency($convert)
    {
        $this->setAttribute(self::CONVERT_CURRENCY, $convert);
    }

    public function setApplication(string $applicationName)
    {
        $this->application = $applicationName;
    }

    /**
     * Set Gpay supported methods
     *
     * @param $googlePayMethods
     */
    public function setGooglePayMethods(array $googlePayMethods)
    {
        $this->googlePayMethods = $googlePayMethods;
    }

    /**
     * Sets the value of GPay card networks supported for a particular payment
     * This value is required in Gpay Card coproto response
     *
     * @param $googlePayCardNetworks
     */
    public function setGooglePayCardNetworks(array $googlePayCardNetworks)
    {
        $this->googlePayCardNetworks = $googlePayCardNetworks;
    }

    /**
     * Used to unset any Gpay supported method from googlePayMethods array
     * basis various conditions,
     * for eg. method not enabled on the merchant can be one such
     * condition. Finally whatever methods remain in this array
     * becomes a part of Gpay coproto response
     *
     * @param $googlePayMethod
     */
    public function unsetGooglePayMethod($googlePayMethod)
    {
        if (($key = array_search($googlePayMethod, $this->googlePayMethods)) !== false) {
            unset($this->googlePayMethods[$key]);
        }
    }

     /* Sets isGooglePayMethodChangeApplicable property true, when Payment method
     * is unselected and authentication gateway is google_pay.
     * Property is set to false otherwise.
     *
     */
    public function setIsGooglePayMethodChangeApplicable()
    {
        if ($this->isMethodlessGooglePay() !== true)
        {
            $this->isGooglePayMethodChangeApplicable = false;

            return;
        }

        $this->isGooglePayMethodChangeApplicable = true;
    }

    public function setAuthType($authType)
    {
        $this->setAttribute(self::AUTH_TYPE, $authType);
    }

    public function setLateBalanceUpdate()
    {
        $this->lateBalanceUpdate = true;
    }

    public function isLateBalanceUpdate()
    {
        return ($this->lateBalanceUpdate === true);
    }

    public function setMetadataKey($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    public function setIsPushedToKafka($isPushedToKafka)
    {
        $this->setAttribute(self::IS_PUSHED_TO_KAFKA, $isPushedToKafka);
    }

    public function getRecurringType()
    {
        return $this->getAttribute(self::RECURRING_TYPE);
    }

    public function getAuthenticationGateway()
    {
        return $this->getAttribute(self::AUTHENTICATION_GATEWAY);
    }

    public function getCpsRoute()
    {
        return $this->getAttribute(self::CPS_ROUTE);
    }

    public function setMetadata($input)
    {
        $this->metadata = $input['_'] ?? null;
    }

    public function setDescription($description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function setDisputed($disputed)
    {
        $this->setAttribute(self::DISPUTED, $disputed);
    }

    public function setReference1(string $reference1)
    {
        $this->setAttribute(self::REFERENCE1, $reference1);
    }

    public function setReference2(string $reference2)
    {
        $this->setAttribute(self::REFERENCE2, $reference2);
    }

    // set function for wallet_user_id sent in payment create request from razorpaywallet
    public function setReference14($walletUserId)
    {
        //If wallet_user_id has prefix iuser_, strip it to get 14 char id before storing it
        $prefix = "iuser_";
        if (substr($walletUserId, 0, strlen($prefix)) === $prefix) {
            $walletUserId = substr($walletUserId, strlen($prefix));
        }
        $this->setAttribute(self::REFERENCE14, $walletUserId);
    }

    public function setReference16(string $reference16)
    {
        $this->setAttribute(self::REFERENCE16, $reference16);
    }

    public function setReference17(string $reference17)
    {
        // used to store gateway_errors and visa safe click payment details
        $this->setAttribute(self::REFERENCE17, $reference17);
    }

    public function setContact($contact)
    {
        $this->setAttribute(self::CONTACT, $contact);
    }

    public function enableCpsRoute()
    {
        $this->setAttribute(self::CPS_ROUTE, 1);
    }

    public function disableCpsRoute()
    {
        $this->setAttribute(self::CPS_ROUTE, 0);
    }

    public function enableCardPaymentService()
    {
        $this->setAttribute(self::CPS_ROUTE, 2);
    }

    public function getRefundsAttribute()
    {
        return (new Refund\Repository())->findForPaymentId($this->getId());
    }

    public function enableNbPlusService()
    {
        $this->setAttribute(self::CPS_ROUTE, 3);
    }

    public function enablePCPService()
    {
        $this->setAttribute(self::CPS_ROUTE, 8);
    }

    public function enableUpiPaymentService()
    {
        $this->setAttribute(self::CPS_ROUTE, 4);
    }

    public function setMethod(string $method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function setAmountTransferred(string $amount)
    {
        $this->setAttribute(self::AMOUNT_TRANSFERRED, $amount);
    }

    public function decrementAmountTransferred(int $amount)
    {
        $newAmount = $this->getAmountTransferred() - $amount;

        if ($newAmount < 0 )
        {
            throw new Exception\LogicException(
                'Amount transferred is going negative',
                ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
                [
                    'payment_id'        => $this->getId(),
                    'amount'            => $amount,
                ]);
        }

        $this->setAmountTransferred($newAmount);

        if ($this->isExternal() === true)
        {
            return;
        }

        (new Repository)->saveOrFail($this);
    }

    public function setEmiSubvention(string $subvention)
    {
        $this->setAttribute(self::EMI_SUBVENTION, $subvention);
    }

    public function setAcknowledgedAt(int $timestamp)
    {
        $this->setAttribute(self::ACKNOWLEDGED_AT, $timestamp);
    }

    public function setRefundAt(int $timestamp = null)
    {
        $this->setAttribute(self::REFUND_AT, $timestamp);
    }

    public function setReceiverId(string $receiverId)
    {
        $this->setAttribute(self::RECEIVER_ID, $receiverId);
    }

    public function setReceiverType(string $receiverType)
    {
        $this->setAttribute(self::RECEIVER_TYPE, $receiverType);
    }

    public function setSourceChannel(string $sourceChannel)
    {
        $this->setAttribute(self::SOURCE_CHANNEL, $sourceChannel);
    }

    public function setSubscriptionId(string $subscriptionId)
    {
        $this->setAttribute(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    public function setFeeBearer($feeBearer)
    {
        $this->setAttribute(self::FEE_BEARER, $feeBearer);

    }

    public function setBatchId($batchId)
    {
        $this->setAttribute(self::BATCH_ID, $batchId);
    }

    public function setPublicKey($publicKey)
    {
        $this->setAttribute(self::PUBLIC_KEY, $publicKey);
    }
    // ----------------------- Setters Ends-----------------------------------------

    // ----------------------- Mutator ---------------------------------------------

    public function setAmountAttribute($amount)
    {
        $this->attributes[self::AMOUNT] = (int) $amount;
    }

    public function setRecurringAttribute($recurring)
    {
        $intVal = intval($recurring);

        $this->attributes[self::RECURRING] = boolval($intVal);
    }

    public function isOtpGenerateRequest()
    {
        if ($this->hasMetadata(Constants::REQUEST_TYPE) === false)
        {
            return false;
        }

        return ($this->getMetadata(Constants::REQUEST_TYPE, '') === Constants::REQUEST_TYPE_OTP);
    }

    protected function setContactAttribute($contact)
    {
        if ($contact === null)
        {
            $this->attributes[self::CONTACT] = null;

            return;
        }

        $number = new PhoneBook($contact, true);

        if ($number->isValidNumber() === true)
        {
            $this->attributes[self::CONTACT] = $number->format();
        }
        else
        {
            $normalizedNumber = $number->getRawInput();

            // Hack for tracing new invalid numbers
            // to get the stats
            $app = \App::getFacadeRoot();

            $app['trace']->info(
                TraceCode::PAYMENT_INVALID_CONTACT_NUMBER,
                ['number' => $contact, 'normalized_number' => $normalizedNumber]);

            $this->attributes[self::CONTACT] = $normalizedNumber;
        }
    }

    protected function setCancellationReasonAttribute($reason)
    {
        if ($reason !== null)
        {
            $reason = mb_strtolower($reason);

            $this->attributes[self::CANCELLATION_REASON] = mb_substr($reason, 0, 255);
        }

        $this->attributes[self::CANCELLATION_REASON] =  $reason;
    }

    protected function setReference1Attribute($reference1)
    {
        $trimmedReference1 = (blank($reference1) === true) ? null : trim($reference1);

        $this->attributes[self::REFERENCE1] =  $trimmedReference1;
    }

    protected function setReference2Attribute($reference2)
    {
        $trimmedReference2 = (blank($reference2) === true) ? null : trim($reference2);

        $this->attributes[self::REFERENCE2] =  $trimmedReference2;
    }

    protected function setReference16Attribute($reference16)
    {
        $trimmedReference16 = (blank($reference16) === true) ? null : trim($reference16);

        $this->attributes[self::REFERENCE16] =  $trimmedReference16;
    }

    protected function setReference17Attribute($reference17)
    {
        if ($reference17 !== null)
        {
            if ($this->isMethodCardOrEmi() === true)
            {
                $reference17Json = json_decode($reference17, true);

                if (($this->getAuthenticationGateway() === Gateway::VISA_SAFE_CLICK) and
                    (array_key_exists('product_enrollment_id', $reference17Json) === true))
                {
                    $reference17 = $reference17Json['product_enrollment_id'];
                }
                elseif ($this->isFailed() === true || $this->isCreated() === true)
                {
                    // Do Nothing.
                    // Set this condition to avoid setting value as null
                }
                else
                {
                    $reference17 = null;
                }
            }
        }

        $this->attributes[self::REFERENCE17] =  $reference17;
    }

    protected function setFeeBearerAttribute($feeBearer)
    {
        //As a part of wda migration some routes are being moving to WDA, We are
        // using forceFill method on the data array that is fetched from database
        // to typecast it into Payments Entity. setFeeBearerAttribute expects a string
        // type value for $feeBearer, but on applying forceFill method on the array,
        // the value is getting set to a numeric type instead of string. So we are
        // converting it back to string using getBearerStringForValue method and then
        // getting the required value.

        if( is_numeric($feeBearer))
       {
           $feeBearer = Merchant\FeeBearer::getBearerStringForValue($feeBearer);
       }

        $this->attributes[self::FEE_BEARER] = Merchant\FeeBearer::getValueForBearerString($feeBearer);
    }

    // As part of Token migration the relations between token entity
    // need to be ignored since token creation and store has moved to
    // tokens service.
    public function setTokenRelations()
    {
        $this->ignoredRelations = [self::ORDER, self::LOCAL_TOKEN];
    }

    public function removeGlobalTokenRelations()
    {
        $this->ignoredRelations = [self::ORDER, self::GLOBAL_TOKEN];
    }

// ----------------------- Mutator Ends ----------------------------------------

// ----------------------- Accessor --------------------------------------------

    // TODO: Return a phonebook instance (like carbon) instead of string
    protected function getContactAttribute()
    {
        $contact = $this->attributes[self::CONTACT];

        if ($contact === null)
        {
            return null;
        }

        $phoneBook = new PhoneBook($contact, true);

        return (string) $phoneBook;
    }

    protected function getVerifiedAttribute()
    {
        $verified = $this->attributes[self::VERIFIED];

        if ($verified !== null)
        {
            $verified = (int) $verified;
        }

        return $verified;
    }

    protected function getCapturedAttribute()
    {
        return ($this->getAttribute(self::CAPTURED_AT) !== null);
    }

    protected function getPayerAccountTypeAttribute()
    {
        return $this->getAttribute(self::REFERENCE2);
    }

    protected function getAcquirerDataAttribute()
    {
        $acquirerData = [];

        switch ($this->getAttribute(self::METHOD))
        {
            case Method::CARD:

                $acquirerData = [
                    'auth_code' => $this->getAttribute(self::REFERENCE2),
                ];

                $productEnrollmentId = $this->getReference17();

                if ((isset($productEnrollmentId) === true) and
                    ($this->getAuthenticationGateway() === Gateway::VISA_SAFE_CLICK))
                {
                    $acquirerData['product_enrollment_id'] = $productEnrollmentId;
                }

                //return authentication_reference_number for Rupay cards
                if(isset($this->card) && ($this->card->isRuPay() ==true))
                {
                    $acquirerData['authentication_reference_number'] = $this->card->getReference4();
                }

                break;

            case Method::EMI:

                $acquirerData = [
                    'auth_code' => $this->getAttribute(self::REFERENCE2),
                ];

                break;

            case Method::NETBANKING:

                $acquirerData = [
                    'bank_transaction_id' => $this->getAttribute(self::REFERENCE1)
                ];
                break;

            case Method::WALLET:

                $merchant = $this->merchant;

                if ($merchant->getCountry() === 'MY')
                {
                    $acquirerData = [
                        'arn' => $this->getAttribute(self::REFERENCE2),
                        'transaction_id' => $this->getAttribute(self::REFERENCE1)
                    ];
                }
                else
                {
                    $acquirerData = [
                        'transaction_id' => $this->getAttribute(self::REFERENCE1)
                    ];
                }
                break;

            case Method::PAYLATER:
            case Method::CARDLESS_EMI:

                $acquirerData = [
                    'transaction_id' => $this->getAttribute(self::REFERENCE1)
                ];
                break;

            case Method::EMANDATE:

                $acquirerData = [];
                break;

            case Method::UPI:

                $acquirerData = [
                    'rrn' => $this->getReference16()
                ];

                $upiTransactionId = $this->getReference1();

                if (isset($upiTransactionId) === true)
                {
                    $acquirerData['upi_transaction_id'] = $upiTransactionId;
                }
                break;

            case Method::APP:

                $acquirerData['discount'] = 0;

                $acquirerData['amount'] = ($this->getAmount() / 100);

                $discount = $this->getDiscountIfApplicable();

                if ($discount !== null)
                {
                    $acquirerData['discount'] = $discount / 100;
                    $acquirerData['amount'] = ($this->getAmount() - $discount) / 100;
                }

                if ($this->isAppTwid())
                {
                    $acquirerData['transaction_id'] = $this->getAttribute(self::REFERENCE1);
                }

                break;

            case Method::FPX:
                $acquirerData = [
                    'fpx_data' => json_decode($this->getAttribute(self::REFERENCE2), true),
                    'arn' => $this->getAttribute(self::REFERENCE1)
                ];
                break;
        }

        // flipkart use case
        if ($this->isMethodCardOrEmi() === true)
        {
            if (empty($this->getReference1()) === false)
            {
                $acquirerData['arn'] = $this->getReference1();
            }

            if (empty($this->getReference16()) === false)
            {
                $acquirerData['rrn'] = $this->getReference16();
            }
        }

        return (new Dictionary($acquirerData));
    }

    public function getDiscountIfApplicable()
    {
        if (($this->isAppCred() === true) and
            ($this->discount !== null))
        {
            return $this->discount->getAmount();
        }

        if (($this->isCardlessEmiWalnut369() === true) and
            ($this->discount !== null))
        {
            return $this->discount->getAmount();
        }

        if ($this->isCardlessEmiLiquiloans() === true)
        {
            $discount = (new Discount\Repository())->fetchDiscountWithoutOffer($this->getId());
            if ($discount != null)
            {
                return $discount->getAmount();
            }
        }

        return null;
    }

    protected function getGatewayProviderAttribute()
    {
        $gatewayProvider = 'Razorpay';

        if ($this->terminal !== null)
        {
            if ($this->terminal->getProcurer() !== Procurer::RAZORPAY)
            {
                $gatewayProvider = $this->getGateway();
            }
        }

        return $gatewayProvider;
    }

    protected function getOtpAttemptsAttribute()
    {
        $attempts = $this->attributes[self::OTP_ATTEMPTS];

        if ($attempts !== null)
        {
            $attempts = (int) $attempts;
        }

        return $attempts;
    }

    protected function getOtpCountAttribute()
    {
        $count = $this->attributes[self::OTP_COUNT];

        if ($count !== null)
        {
            $count = (int) $count;
        }

        return $count;
    }

    protected function getMdrAttribute($mdr)
    {
        if ($mdr === null)
        {
            return $this->getFee();
        }

        return $mdr;
    }

    protected function getFeeBearerAttribute()
    {
        if (isset($this->attributes[self::FEE_BEARER]) === false)
        {
            $this->attributes[self::FEE_BEARER] = 0; // 0 -> platform fee bearer
        }

        return Merchant\FeeBearer::getBearerStringForValue($this->attributes[self::FEE_BEARER]);
    }

    public function getMetadata($key = null, $default = null)
    {
        if ($key === null)
        {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? $default;
    }

    public function getRefundStatus()
    {
        return $this->getAttribute(self::REFUND_STATUS);
    }

    public function getOnHold()
    {
        return $this->getAttribute(self::ON_HOLD);
    }

    public function getOnHoldUntil()
    {
        return $this->getAttribute(self::ON_HOLD_UNTIL);
    }

    public function getGatewayMerchantId()
    {
        return $this->getAttribute(self::GatewayMerchantId);
    }

    public function getGatewayTerminalId()
    {
        return $this->getAttribute(self::GatewayTerminalId);
    }

    public function getInternalStatus()
    {
        return $this->getAttribute(self::InternalStatus);
    }

    public function getDeviceId()
    {
        return $this->getAttribute(self::DeviceId);
    }

    public function getAuthenticatedAt()
    {
        return $this->getAttribute(self::AUTHENTICATED_AT);
    }

    public function getCapturedAt()
    {
        return $this->getAttribute(self::CAPTURED_AT);
    }

    public function getReceiverType()
    {
        return $this->getAttribute(self::RECEIVER_TYPE);
    }

    public function getSourceChannel()
    {
        $sourceChannel = $this->getAttribute(self::SOURCE_CHANNEL);

        if ($sourceChannel === null)
        {
            $sourceChannel = $this->getAttribute(Payment\Constant::SOURCE_CHANNEL);
        }

        return $sourceChannel;
    }

    /**
     * @param $shouldGetSavedFeeBearer
     * The caller to decide which value of feebearer to get
     * Function is called in primarily 2 flows
     * 1. payment/transaction related flows -> if merchant is platform/customer feebarer at time of payment, return merchant.fee_bearer
     * 2. flows to display value in merchant/admin dashboard[by setPublicFeeBearerAttribute] -> return saved value from payment entity stored in db.     *
     */
    public function getFeeBearer($shouldGetSavedFeeBearer = false)
    {
        if ($shouldGetSavedFeeBearer === true)
        {
            return $this->getAttribute(self::FEE_BEARER);
        }

        if (($this->merchant !== null) and
            ($this->merchant->isFeeBearerDynamic() === false))
        {
            return $this->merchant->getFeeBearer();
        }

        return $this->getAttribute(self::FEE_BEARER);
    }

    public function getPublicKey()
    {
        return $this->getAttribute(self::PUBLIC_KEY);
    }

    public function getIsPushedToKafka()
    {
        return $this->getAttribute(self::IS_PUSHED_TO_KAFKA);
    }

    public function getOrderAttribute()
    {
        $order = null;

        if ($this->relationLoaded('order') === true)
        {
            $order = $this->getRelation('order');
        }

        if ($order !== null)
        {
            return $order;
        }

        if (empty($this[self::ORDER_ID]) === true)
        {
            return null;
        }

        $order = (new Order\Repository)->findOrFailPublic($this[self::ORDER_ID]);

        $this->order()->associate($order);

        return $order;
    }

    public function getTerminalAttribute()
    {
        $terminal = null;

        if ($this->relationLoaded('terminal') === true)
        {
            $terminal = $this->getRelation('terminal');
        }

        if ($terminal !== null)
        {
            return $terminal;
        }

        if(!$this->canMerchantFetchTerminalsFromTS($this->getId()))
        {

            $terminal = $this->terminal()->first();

            if (empty($terminal) === false)
            {
                (new Terminal\Service())->pushTerminalReadMetrics( "payment",false);

                $this->terminal()->associate($terminal);

                return $terminal;
            }

        }

        if (empty($this->getTerminalId()))
        {
            return null;
        }

        $terminal = (new Terminal\Repository)->fetchTerminalsToAssociate("payment",$this->getTerminalId());

        $this->terminal()->associate($terminal);

        return $terminal;

    }

    private function canMerchantFetchTerminalsFromTS($paymentId): bool
    {
        $app = \App::getFacadeRoot();

        $rampUpTerminalsTraffic = $app['config']->get('applications.terminals_service.associate_terminals_from_ts');

        if($paymentId === null)
        {
            return false;
        }

        if($rampUpTerminalsTraffic == 0)
        {
            return false;
        }
        else if($rampUpTerminalsTraffic == 100)
        {
            return true;
        }
        else
        {

            $hash = abs(crc32($paymentId)) % 100;

            if($hash < $rampUpTerminalsTraffic)
            {
                return true;
            }
            else
            {
                return false;
            }

        }

    }

// ----------------------- Accessor Ends ---------------------------------------

    public function isCreated()
    {
        return ($this->getAttribute(self::STATUS) === Status::CREATED);
    }

    /**
     * A payment is considered just created for 5
     * minutes since creation
     * @return bool
     */
    public function justCreated()
    {
        $currentTime = time();

        $secondsSinceCreated = $currentTime - $this->getAttribute(self::CREATED_AT);

        return (bool) ($secondsSinceCreated <= (Processor\Processor::ASYNC_PAYMENT_TIMEOUT));
    }

    public function isAeps()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::AEPS);
    }

    public function isAuthorized()
    {
        return ($this->getAttribute(self::STATUS) === Status::AUTHORIZED);
    }

    public function isAuthenticated()
    {
        return ($this->getAttribute(self::STATUS) === Status::AUTHENTICATED);
    }

    public function isPending(): bool
    {
        return ($this->getAttribute(self::STATUS) === Status::PENDING);
    }

    public function isCreatedOrAuthorized()
    {
        return ($this->isCreated() or $this->isAuthorized());
    }

    public function hasBeenAuthenticated()
    {
        return ($this->isAttributeNotNull(self::AUTHENTICATED_AT));
    }

    public function hasBeenAuthorized()
    {
        return ($this->isAttributeNotNull(self::AUTHORIZED_AT));
    }

    public function hasNotBeenAuthorized()
    {
        return ($this->isAttributeNull(self::AUTHORIZED_AT));
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    public function hasCard()
    {
        return ($this->isAttributeNotNull(self::CARD_ID));
    }

    public function hasOrder()
    {
        return ($this->isAttributeNotNull(self::ORDER_ID));
    }

    public function hasSubscription()
    {
        return ($this->isAttributeNotNull(self::SUBSCRIPTION_ID));
    }

    public function hasInvoice()
    {
        return ($this->isAttributeNotNull(self::INVOICE_ID));
    }

    public function isProductTypeInvoice(): bool
    {
        if ($this->hasOrder() && $this->order !== null &&
            $this->order->getProductId() != null && $this->order->getProductType() === Order\ProductType::INVOICE
        ) {
            return true;
        }

       return false;
    }

    public function hasInvoiceOrProductTypeInvoice(): bool
    {
        if ($this->isAttributeNotNull(self::INVOICE_ID) ||
            $this->isProductTypeInvoice()) {
            return true;
        }

        return false;
    }

    public function hasTransfer()
    {
        return ($this->isAttributeNotNull(self::TRANSFER_ID));
    }

    public function hasPaymentLink(): bool
    {
        if($this->isAttributeNotNull(self::PAYMENT_LINK_ID) ||
            ($this->hasOrder() && $this->order->getProductId() != null && in_array($this->order->getProductType(), PaymentLink\Entity::paymentLinkEntityProductTypes())))
         {
            return true;
         }

        return false;
    }

    public function hasTerminal()
    {
        return $this->isAttributeNotNull(self::TERMINAL_ID);
    }

    public function getPaymentLinkId()
    {
        if($this->isAttributeNotNull(self::PAYMENT_LINK_ID)){
            return $this->getAttribute(self::PAYMENT_LINK_ID);
        }

        if($this->hasOrder() && $this->order->getProductId() != null && in_array($this->order->getProductType(), PaymentLink\Entity::paymentLinkEntityProductTypes())){
            return $this->order->getProductId();
        }

        return null;
    }

    public function hasReceiver()
    {
        return ($this->isAttributeNotNull(self::RECEIVER_ID));
    }

    public function hasMetadata($key = null)
    {
        if ($key === null)
        {
            return false;
        }

        return (isset($this->metadata[$key]) === true);
    }

    public function isCaptured()
    {
        return ($this->getAttribute(self::STATUS) === Status::CAPTURED);
    }

    public function isRefunded()
    {
        return ($this->getAttribute(self::STATUS) === Status::REFUNDED);
    }

    public function isPartiallyOrFullyRefunded()
    {
        return ! ($this->getAttribute(self::REFUND_STATUS) === RefundStatus::NULL);
    }

    public function isFullyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === RefundStatus::FULL);
    }

    public function reload()
    {
        try
        {
            if ($this->isExternal() === false)
            {
                $payment = (new Repository)->findOrFailArchived($this->{$this->primaryKey});

                $this->attributes = $payment->attributes;

                $this->original = $payment->original;

                return $this;
            }
        }
        catch (\Throwable $e) {}

        if ($this->entity === EntityConstants::PAYMENT && $this->hasTransfer())
        {
            return $this->fetchExternalTransferTypePaymentById($this->getId());
        }

        return $this->fetchExternalEntity($this->{$this->primaryKey}, '', []);
    }

    public function isPartiallyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === RefundStatus::PARTIAL);
    }

    public function isTransferred()
    {
        $isTransferred = false;
        $transferPayments = (new Transfer\Payment\Repository())->getTransferPayment($this->getId());

        $newAmountTransferred = 0;
        if ($transferPayments->count() > 0  === true)
        {
            $newAmountTransferred = $transferPayments[0]->getAmountTransferred();
        }

        if (($this->getAttribute(self::AMOUNT_TRANSFERRED) > 0 === true) or
            ($newAmountTransferred > 0 === true))
        {
            $isTransferred =  true;
        }

        return $isTransferred;
    }

    /**
     * @return bool
     * This checks if payment entity has amount transferred or not.
     */
    public function isTransferredInOldFlow()
    {
        return (($this->getAttribute(self::AMOUNT_TRANSFERRED) > 0) === true);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) === Status::FAILED);
    }

    public function isLateAuthorized()
    {
        return ($this->getAttribute(self::LATE_AUTHORIZED) === true);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) === $status);
    }

    public function isStatusCreatedOrFailed()
    {
        return (($this->isFailed()) or
                ($this->isCreated()));
    }

    public function hasBeenCaptured()
    {
        return ($this->getAttribute(self::CAPTURED_AT) !== null);
    }

    public function isGatewayCaptured()
    {
        return ($this->getAttribute(self::GATEWAY_CAPTURED) === true);
    }

    public function isCard()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::CARD);
    }

    public function isNetbanking()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::NETBANKING);
    }

    public function isFpx()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::FPX);
    }

    public function isDuitNowPay()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::DUITNOW_PAY);
    }

    public function isGiftcard()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::GIFT_CARDS);
    }

    public function isEmandate()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::EMANDATE);
    }

    public function isNach()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::NACH);
    }

    public function isEzetap()
    {
        return ($this->getAttribute(self::GATEWAY) === Payment\Gateway::HDFC_EZETAP);
    }

    public function isWallet()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::WALLET);
    }

    public function isRazorpayAccountPayment()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::RAZORPAY_ACCOUNT);
    }

    public function isAppTwid()
    {
        return  (($this->getAttribute(self::METHOD) === Payment\Method::APP) and
            ($this->getAttribute(self::WALLET) === AppMethod::TWID));
    }

    public function isAppCred()
    {
        return  (($this->getAttribute(self::METHOD) === Payment\Method::APP) and
                 ($this->getAttribute(self::WALLET) === AppMethod::CRED));
    }

    public function isCardlessEmiWalnut369()
    {
        return  (($this->getAttribute(self::METHOD) === Payment\Method::CARDLESS_EMI) and
            ($this->getAttribute(self::WALLET) === Payment\Processor\CardlessEmi::WALNUT369));
    }

    public function isCardlessEmiLiquiloans()
    {
        return  (($this->getAttribute(self::METHOD) === Payment\Method::CARDLESS_EMI) and
            ($this->getAttribute(self::WALLET) === Payment\Processor\CardlessEmi::LIQUILOANS));
    }

    public function isWalletPaypal()
    {
        return  (($this->getAttribute(self::METHOD) === Payment\Method::WALLET) and
                 ($this->getAttribute(self::WALLET) === Payment\Processor\Wallet::PAYPAL));
    }

    public function isEmi()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::EMI);
    }

    public function isCardlessEmi()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::CARDLESS_EMI);
    }

    public function isPayLater()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::PAYLATER);
    }

    public function isPinAuth()
    {
        return (($this->getAttribute(self::METHOD) === Payment\Method::CARD) and
            ($this->getAttribute(self::AUTH_TYPE) === AuthType::PIN));
    }

    public function isCardRecurring(): bool
    {
        return (($this->getAttribute(self::METHOD) === Payment\Method::CARD) and
            ($this->getAttribute(self::RECURRING) === true));
    }

    public function isCardAutoRecurring(): bool
    {
        return (($this->isCardRecurring()) and
                     ($this->isSecondRecurring()));
    }

    public function isUpi()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::UPI);
    }

    public function isUpiRecurring(): bool
    {
        return (($this->getAttribute(self::METHOD) === Payment\Method::UPI) and
                ($this->getAttribute(self::RECURRING) === true));
    }

    public function isUpiAutoRecurring(): bool
    {
        return ($this->isUpiRecurring() and
               ($this->isSecondRecurring()));
    }

    public function isEmandateRecurring(): bool
    {
        return (($this->getAttribute(self::METHOD) === Payment\Method::EMANDATE) and
                ($this->getAttribute(self::RECURRING) === true));
    }

    public function isEmandateAutoRecurring(): bool
    {
        return ($this->isEmandateRecurring() and
               ($this->isSecondRecurring()));
    }

    public function isNachRecurring(): bool
    {
        return (($this->getAttribute(self::METHOD) === Payment\Method::NACH) and
            ($this->getAttribute(self::RECURRING) === true));
    }

    public function isNachAutoRecurring(): bool
    {
        return ($this->isNachRecurring() and
            ($this->isSecondRecurring()));
    }

    /**
     * @return bool
     */
    public function isUpiIntentRecurring(): bool
    {
        return (($this->isUpiRecurring()) and
                ($this->isFlowIntent()));
    }

    public function isUpiOtm(): bool
    {
        if ($this->isUpi() === false)
        {
            return false;
        }

        $upiMetadata = $this->getUpiMetadata();

        return (($upiMetadata instanceof UpiMetadata\Entity) and
                ($upiMetadata->isOtm() === true));
    }

    /**
     * This is a method to get is in app upi flag
     * @return bool
     */
    public function isInAppUPI()
    {
        if ($this->isUpi() === false)
        {
            return false;
        }

        // if meta data does not exists in upi fetch and attach
        if ($this->hasMetadata(UpiMetadata\Entity::UPI_METADATA) === false)
        {
            $upiMetadata = $this->fetchUpiMetadataAttributeForValidation();

            // if upi metadata is null we can directly return;
            if($upiMetadata == null)
            {
                return false;
            }

            // feed metadata in case the metadata does not exist
            $this->metadata[UpiMetadata\Entity::UPI_METADATA] = $upiMetadata;
        }

        // Already upi metadata won't be null
        $upiMetadata = $this->getMetadata(UpiMetadata\Entity::UPI_METADATA);

        // return true in case of in app mode present
        return ((isset($upiMetadata[UpiMetadata\Entity::FLOW]) === true) and
                ($upiMetadata[UpiMetadata\Entity::FLOW] === UpiMetadata\Flow::IN_APP));
    }

    public function isInAppCreditCardOnUpi(): bool
    {
        return (
            ($this->isInAppUPI() === true) and
            ($this->getPayerAccountTypeAttribute() === PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT)
        );
    }

    public function fetchUpiMetadataAttributeForValidation()
    {
        // if payment id is not existing return null
        if($this->getId() == null)
        {
            return null;
        }

        if ($this->hasMetadata(UpiMetadata\Entity::UPI_METADATA) === true)
        {
            return $this->getMetadata(UpiMetadata\Entity::UPI_METADATA);
        }

        // inApp payments is not supported for re-arch
        if ($this->isExternal() === true)
        {
            return null;
        }

        return (new UpiMetadata\Repository())->fetchByPaymentId($this->getId());
    }

    public function isTransfer()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::TRANSFER);
    }

    public function isBankTransfer()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::BANK_TRANSFER);
    }

    public function isOfflineChallan()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::OFFLINE);
    }

    public function isRoutedThroughCardPayments()
    {
        return ($this->getAttribute(self::CPS_ROUTE) === Payment\Entity::CARD_PAYMENT_SERVICE);
    }

    public function isRoutedThroughNbPlus(): bool
    {
        $cps = $this->getAttribute(self::CPS_ROUTE);

        return ($cps === Payment\Entity::NB_PLUS_SERVICE) or ($cps === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS);
    }

    public function isRoutedThroughUpiPaymentService()
    {
        return ($this->getAttribute(self::CPS_ROUTE) === Payment\Entity::UPI_PAYMENT_SERVICE);
    }

    public function isRoutedThroughPaymentsUpiPaymentService()
    {
        return ($this->getAttribute(self::CPS_ROUTE) === Payment\Entity::REARCH_UPI_PAYMENT_SERVICE);
    }

    public function isPushPaymentMethod()
    {
        return ($this->isInternational() === false and $this->isBankTransfer() === true) or
               ($this->isBharatQr() === true) or
               ($this->isUpiTransfer() === true) or
               ($this->isUpi() === true);
    }

    public function isAuthenticationGatewayGooglePay(){
        return $this->getAuthenticationGateway() === self::GOOGLE_PAY;
    }

    public function isGooglePayCard()
    {
        return (($this->isCard()) and
                ($this->application === 'google_pay'));
    }

    /**
     * This purpose of this function is to identify Gpay payments
     * For Gpay payments, method will not be set
     */
    public function isGooglePay()
    {
        return (($this->getMethod() === Method::UNSELECTED) and
                ($this->application === self::GOOGLE_PAY));
    }

    /**
     * We can have multiple methods supported for GPay
     * Returns true if a particular method is enabled for Gpay payments
     *
     * @param $method
     * @return bool
     */
    public function isGooglePayMethodSupported($method)
    {
        if (($this->isGooglePay() === false) or
             empty($this->getGooglePayMethods()) === true)
        {
            return false;
        }

        return (in_array($method, $this->getGooglePayMethods()) === true);
    }

    public function isBharatQr()
    {
        return ($this->getAttribute(self::RECEIVER_TYPE) === Receiver::QR_CODE);
    }

    public function isPos()
    {
        return ($this->getAttribute(self::RECEIVER_TYPE) === Receiver::POS);
    }

    public function isCollectXPayment()
    {
        try {
            if ($this->merchant->isFeatureEnabled(Feature\Constants::COLLECTX_ENABLED) === true &&
                ($this->getAttribute(Entity::REFERENCE14) === Constant::COLLECTX) &&
                (($this->getAttribute(Entity::METHOD) == Payment\Method::UPI) ||
                    ($this->getAttribute(Entity::METHOD) === Payment\Method::BANK_TRANSFER)))
            {
                return true;
            }
        }
        catch(\Throwable $ex) {
            $app = \App::getFacadeRoot();

            $app['trace']->info(TraceCode::COLLECTX_PAYMENT_TRANSFER_CHECK_FAILED, [
                'merchant_id' => $this->merchant->getId()
            ]);

            return false;
        }

        return false;
    }

    public function identifierForCollectxPayment()
    {
        return ($this->getAttribute("reference14") === "collectx");
    }

    /**
     * @ToDo: This method needs fixing as it doesn't return the correct value
     *        during manual capture of payments as $metadata is empty.
     *
     * @return bool
     */
    public function isFlowIntent(): bool
    {
        if ($this->isGooglePayMethodSupported(Method::UPI))
        {
            return true;
        }

        return ($this->getMetadata('flow') === Flow::INTENT);
    }

    /** Fetches UpiMetadata if already not set in payments metadata
     * @return array|mixed|null
     */
    public function fetchUpiMetadata()
    {
        $upiMetadata = null;

        if ($this->hasMetadata(UpiMetadata\Entity::UPI_METADATA) === true)
        {
            $upiMetadata = $this->getMetadata(UpiMetadata\Entity::UPI_METADATA);
        }
        else
        {
            // if meta data does not exists in upi fetch and attach
            $upiMetadata = $this->fetchUpiMetadataAttributeForValidation();

        }

        if ((empty($upiMetadata) === false) and
            ($upiMetadata instanceof UpiMetadata\Entity === false))
        {
            return null;
        }

        return $upiMetadata;
    }

    public function isUpiQr(): bool
    {
        // By definition if receiver type is qr_code and flow is intent, its upi qr payment
        // Not relying on isBharatQr method as its implementation may change over time.
        return (($this->getAttribute(self::RECEIVER_TYPE) === Receiver::QR_CODE) and $this->isFlowIntent());
    }

    /**
     * UPI transfer is the case of smart collect where payment method is UPI and
     * receiver type will be VPA and is different from normal UPI transactions.
     *
     * @return bool
     */
    public function isUpiTransfer()
    {
        return (($this->isUpi() === true) and
                ($this->getAttribute(self::RECEIVER_TYPE) === Receiver::VPA));
    }

    /**
     * @param UpiMetadata\Entity $upiMetadata
     * @return bool
     */
    public function isUpiOtmPayment(UpiMetadata\Entity $upiMetadata)
    {
        if ((empty($upiMetadata) === true) or
            ($upiMetadata instanceof UpiMetadata\Entity === false))
        {
            return false;
        }

        if (empty($upiMetadata->getType()) === false)
        {
            return $upiMetadata->isOtm();
        }

        return false;
    }

    public function isCreditCardOnUpi(): bool
    {
        if ($this->isUpi() === false)
        {
            return false;
        }

        $PayerAccountType=$this->getPayerAccountTypeAttribute();
        if ($PayerAccountType === PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT) {
            return true;
        }

        return false;
    }

    public function isCreditLineOnUpi(): bool
    {
        if ($this->isUpi() === false)
        {
            return false;
        }

        $PayerAccountType=$this->getPayerAccountTypeAttribute();
        if ($PayerAccountType === PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT_LINE) {
            return true;
        }

        return false;
    }

    public function isPPIOnUpi(): bool
    {
        if ($this->isUpi() === false)
        {
            return false;
        }

        $PayerAccountType=$this->getPayerAccountTypeAttribute();
        if ($PayerAccountType === PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_PPIWALLET ||
            $PayerAccountType === PaymentsUpi\PayerAccountType::PAYER_ACCOUNT_TYPE_WALLET )
        {
            return true;
        }

        return false;
    }

    public function checkIfCCOnUPIPricingSplitzExperimentEnabled(): bool
    {
        $app = \App::getFacadeRoot();
        try
        {
            $merchantId=$this->getMerchantId();
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get('app.cc_on_upi_pricing_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];
            $response   = $app['splitzService']->evaluateRequest($properties);

            $app['trace']->info(TraceCode::SPLITZ_RESPONSE, [
                'properties'    => $properties,
                'merchant_id'   => $merchantId,
                'response'      => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if ($key == "result" && $value == "on")
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                null,
                TraceCode::CC_ON_UPI_PRICING_SPLITZ_ERROR);
        }

        return false;
    }

    public function checkIfCreditLineOnUPIPricingSplitzExperimentEnabled(): bool
    {
        $app = \App::getFacadeRoot();
        try
        {
            $merchantId=$this->getMerchantId();
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get('app.credit_line_on_upi_pricing_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];
            $response   = $app['splitzService']->evaluateRequest($properties);

            $app['trace']->info(TraceCode::SPLITZ_RESPONSE, [
                'properties'    => $properties,
                'merchant_id'   => $merchantId,
                'response'      => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if ($key == "result" && $value == "on")
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                null,
                TraceCode::CL_ON_UPI_PRICING_SPLITZ_ERROR);
        }

        return false;
    }

    public function checkIfPPIOnUPIPricingSplitzExperimentEnabled(): bool
    {
        $app = \App::getFacadeRoot();
        try
        {
            $merchantId=$this->getMerchantId();
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get('app.ppi_wallet_on_upi_pricing_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];
            $response   = $app['splitzService']->evaluateRequest($properties);

            $app['trace']->info(TraceCode::SPLITZ_RESPONSE, [
                'properties'    => $properties,
                'merchant_id'   => $merchantId,
                'response'      => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if ($key == "result" && $value == "on")
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                null,
                TraceCode::PPI_WALLET_ON_UPI_PRICING_SPLITZ_ERROR);
        }

        return false;
    }

    public function isQrV2UpiPayment(): bool
    {
        if ($this->isUpi() === false)
        {
            return false;
        }

        return $this->isQrV2Payment();
    }

    public function isQrV2Payment(): bool
    {
        if ($this->receiver_type !== Receiver::QR_CODE) {
            return false;
        }

        $receiver = $this->receiver;

        return (($receiver !== null) and
                ($receiver instanceof QrV2\Entity) and
                ($receiver->getStatus() !== null));
    }

    /**
     * @ToDo: Refactor to remove this ambiguous method. `getReceiver()` isn't
     *        necessary as we already have `receiver()` relationship defined.
     *        Also, the responsibility of `getReceiver()` shouldn't just be
     *        limited to QrCode as there are multiple payment receivers.
     *
     * @deprecated Please use receiver relationship instead.
     *
     * @return null|QrCode\Entity|QrV2\Entity
     */
    public function getReceiver()
    {
        return (new QrCode\Repository)->find($this->toArray()[self::RECEIVER_ID]);
    }

    public function isVisaSafeClickPayment()
    {
        return (($this->isCard()) and
            ($this->application === 'visasafeclick'));
    }

    public function isVisaSafeClickStepUpPayment()
    {
        return (($this->isCard()) and
            (isset($this->input[self::CARD]) === true) and
            (isset($this->input[self::CARD][Card\Entity::CVV]) === false) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::VISA_SAFE_CLICK) === true));
    }

    public function skipCvvCheck()
    {
        if ($this->skipCvvCheckFlag !== null)
        {
            return $this->skipCvvCheckFlag;
        }

        // return false for non card payments
        if ((isset($this->input[self::METHOD]) === false) or
            ($this->input[self::METHOD] !== self::CARD))
        {
            $this->skipCvvCheckFlag = false;

            return $this->skipCvvCheckFlag;
        }

        // return false if cvv is already present
        if (isset($this->input[self::CARD][Card\Entity::CVV]) === true)
        {
            $this->skipCvvCheckFlag = false;

            return $this->skipCvvCheckFlag;
        }

        $app = \App::getFacadeRoot();

        $experimentResult = $app['razorx']->getTreatment($this->merchant->getOrgId(),
            'skip_cvv', $app['rzp.mode']);

        $app['trace']->debug(TraceCode::SKIP_CVV_CHECK_RESULT, [
            'paymentId' => $this->getId(),
            'razorXResult' => $experimentResult,
        ]);

        $this->skipCvvCheckFlag =  (($this->isCard()) and
            ($experimentResult == "skip") and
            (isset($this->input[self::CARD]) === true) and
            (isset($this->input[self::CARD][Card\Entity::CVV]) === false) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::SKIP_CVV) === true));

        return $this->skipCvvCheckFlag;
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isMethod($method)
    {
        return ($this->getAttribute(self::METHOD) === $method);
    }

    public function isMethodCardOrEmi()
    {
        return (($this->isMethod(Payment\Method::CARD)) or
                ($this->isMethod(Payment\Method::EMI)));
    }

    public function isTpvMethod()
    {
        return (($this->isMethod(Payment\Method::UPI)) or
                ($this->isMethod(Payment\Method::NETBANKING)));
    }

    public function isCoD()
    {
        return $this->isMethod(Payment\Method::COD);
    }

    public function isOffline()
    {
        return $this->isMethod(Payment\Method::OFFLINE);
    }

    public function isSigned()
    {
        return ($this->getAttribute(self::SIGNED) === true);
    }

    public function isOnHold()
    {
        return $this->getOnHold();
    }

    public function isInternational()
    {
        return (bool) $this->getAttribute(self::INTERNATIONAL);
    }

    public function isOpenWalletPayment()
    {
        return ($this->getWallet() === Processor\Wallet::OPENWALLET);
    }

    public function isRazorpaywalletPayment()
    {
        return ($this->getWallet() === Processor\Wallet::RAZORPAYWALLET);
    }

    public function isCustomerMailAbsent(): bool
    {
        $email = $this->getEmail();

        return ((empty($email) === true) or ($email === self::DUMMY_EMAIL));
    }

    // mcc is supported only for lrs merchants, card and wallet paypal payments.
    public function isMccSupported()
    {
        return (($this->getAttribute(self::METHOD) === Method::CARD) or
                ($this->isCoD() === true) or
               (($this->getAttribute(self::METHOD) === Method::WALLET) and
                   ($this->getWallet() === Wallet::PAYPAL)) or
               ($this->merchant->isLRSFlowEnabled() === true) or
            ($this->isInternational() and $this->isBankTransfer())
        );
    }

    public function isFeeBearerCustomer()
    {
        if (($this->merchant !== null) and
            ($this->merchant->isFeeBearerDynamic() === false))
        {
            return $this->merchant->isFeeBearerCustomer() === true;
        }

        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::CUSTOMER;
    }

    public function isFeeBearerPlatform()
    {
        if (($this->merchant !== null) and
            ($this->merchant->isFeeBearerDynamic() === false))
        {
            return $this->merchant->isFeeBearerPlatform() === true;
        }

        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::PLATFORM;
    }


    /**
     * Checks if card should be saved depending on if the payment is emi or
     * the payment was a card payment and has an associated order on which an offer
     * was applied
     *
     * @return bool
     */
    public function shouldSaveCard(): bool
    {
        if (($this->isEmi() === true) or ($this->hasCardOffer() === true))
        {
            return true;
        }

        return false;
    }

    public function hasCardOffer(): bool
    {
        if (($this->isCard() === true) and ($this->hasOrder() === true))
        {
            $order = $this->order;

            if ($order->hasOffers() === true)
            {
                return true;
            }
        }

        return false;
    }

    public function hasEmiOffer(): bool
    {
        if (($this->isEmi() === true) and ($this->hasOrder() === true))
        {
            $order = $this->order;

            if ($order->hasOffers() === true)
            {
                return true;
            }
        }

        return false;
    }

    public function isDisputed(): bool
    {
        return $this->getAttribute(self::DISPUTED);
    }

// ----------------------- Getters ---------------------------------------------

    public function getHiddenInReport()
    {
        $gateway = $this->getGateway();

        if ((empty($gateway) === false) and
            ($gateway === E::PAYTM))
        {
            unset($this->hiddenInReport[self::ACQUIRER_DATA]);
        }

        return $this->hiddenInReport;
    }

    public function getVpaHandleFromVpa()
    {
        $vpa = $this->getAttribute(self::VPA);

        $vpaParts = explode('@', $vpa);

        $vpaHandle = end($vpaParts);

        return $vpaHandle;
    }

    public function getBankCodeFromVpa()
    {
        $vpaHandle = $this->getVpaHandleFromVpa();

        return ProviderCode::getBankCode($vpaHandle);
    }

    public function getTransferId()
    {
        return $this->getAttribute(self::TRANSFER_ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getAmountRefunded()
    {
        return $this->getAttribute(self::AMOUNT_REFUNDED);
    }

    public function getBaseAmountRefunded()
    {
        return $this->getAttribute(self::BASE_AMOUNT_REFUNDED);
    }

    public function getAmountUnrefunded()
    {
        return $this->getAmount() - $this->getAmountRefunded();
    }

    public function getAmountCaptured()
    {

        if($this->getAttribute(self::AMOUNT_CAPTURED) == null){
            return $this->getAttribute(self::AMOUNT);
        }
        return $this->getAttribute(self::AMOUNT_CAPTURED);
    }

    public function getBaseAmountUnrefunded()
    {
        return $this->getBaseAmount() - $this->getBaseAmountRefunded();
    }

    public function getAmountTransferred()
    {
        $transferPayments = (new Transfer\Payment\Repository())->getTransferPayment($this->getId());

        if  ($transferPayments->count() > 0  === true)
        {
            return $transferPayments[0]->getAmountTransferred();
        }

        return $this->getAttribute(self::AMOUNT_TRANSFERRED);
    }

    public function getAmountTransferredOld()
    {
        return $this->getAttribute(self::AMOUNT_TRANSFERRED);
    }

    public function getAmountUntransferred()
    {
        return $this->getAmount() - $this->getAmountTransferred();
    }

    public function getAmountAuthorized()
    {
        $this->getAttribute(self::AMOUNT_AUTHORIZED);
    }

    public function getDccMarkUpAmount()
    {
        $this->getAttribute(self::DCC_MARKUP_AMOUNT);
    }

    public function getGatewayTxnId()
    {
        return $this->getAttribute(self::GATEWAY_TXN_ID);
    }


    /**
     * Gets adjusted amount with respect to customer fee bearer merchants.
     * This amount is compared against the requested capture amount by merchant
     * and a few other places.
     *
     * @return int
     */
    public function getAdjustedAmountWrtCustFeeBearer(): int
    {
        $amount = $this->getAmount();

        if ($this->isFeeBearerCustomer() === true)
        {
            $amount -= $this->getFee();
        }

        return $amount;
    }

    /**
     * @param $originalPaymentFee
     * @return int
     * Gets Adjusted amount with respect to CFB merchants.
     * This amount is compared against the requested capture amount and
     * used in order entity update
     */
    public function getAdjustedAmountWrtMCCCustFeeBearer($originalPaymentFee): int
    {
        $amount = $this->getAmount();

        if ($this->isFeeBearerCustomer() === true and
            $this->getCurrency()!== Currency\Currency::INR and
            $originalPaymentFee!=null and
            (($this->isInternational() and
            $this->merchant->isCustomerFeeBearerAllowedOnInternational()) or
            ($this->merchant->isLRSFlowEnabled())))
        {
            $amount -= $originalPaymentFee;
        }
        else if ($this->isFeeBearerCustomer() === true)
        {
            $amount -= $this->getFee();
        }
        return $amount;
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getAmountPaidout()
    {
        return $this->getAttribute(self::AMOUNT_PAIDOUT);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getCallbackUrl()
    {
        return $this->getAttribute(self::CALLBACK_URL);
    }

    public function getCaptureTimestamp()
    {
        return $this->getAttribute(self::CAPTURED_AT);
    }

    public function getAuthenticatedTimestamp()
    {
        return $this->getAttribute(self::AUTHENTICATED_AT);
    }

    public function getAuthorizeTimestamp()
    {
        return $this->getAttribute(self::AUTHORIZED_AT);
    }

    public function getRefundAt()
    {
        return $this->getAttribute(self::REFUND_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getBankName()
    {
        $bankId = $this->getBank();

        if ($bankId !== null)
        {
            $method = $this->getMethod();

            if ($method == Method::FPX)
            {
                return Fpx::getName($bankId);
            }
            return Netbanking::getName($bankId);
        }
    }

    public function getWallet()
    {
        return $this->getAttribute(self::WALLET);
    }

    public function getFormattedCard()
    {
        return ($this->card !== null) ? $this->card->getFormatted() : null;
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getAutoCaptured()
    {
        return $this->getAttribute(self::AUTO_CAPTURED);
    }

    public function getCapture(): bool
    {
        return $this->getAttribute(self::CAPTURE) ?? false;
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function getGlobalCustomerId()
    {
        return $this->getAttribute(self::GLOBAL_CUSTOMER_ID);
    }

    public function getGlobalTokenId()
    {
        return $this->getAttribute(self::GLOBAL_TOKEN_ID);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getDaysSinceAuthorized()
    {
        $now = Carbon::now()->getTimestamp();

        $at = $this->getAuthorizeTimestamp();
        $diff = $now - $at;

        return floor($diff / (60 * 24 * 24));
    }

    public function getEmiPlanId()
    {
        return $this->getAttribute(self::EMI_PLAN_ID);
    }

    public function getSave()
    {
        return $this->getAttribute(self::SAVE);
    }

    public function isRecurring()
    {
        return ($this->getAttribute(self::RECURRING) === true);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function getApplication()
    {
        return $this->application;
    }

    public function getGooglePayMethods()
    {
        return $this->googlePayMethods;
    }

    public function getGooglePayCardNetworks()
    {
        return $this->googlePayCardNetworks;
    }

    public function getIsGooglePayMethodChangeApplicable()
    {
        return $this->isGooglePayMethodChangeApplicable;
    }

    public function getTwoFactorAuth()
    {
        return $this->getAttribute(self::TWO_FACTOR_AUTH);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getOtpCount()
    {
        return $this->getAttribute(self::OTP_COUNT);
    }

    public function getOtpAttempts()
    {
        return $this->getAttribute(self::OTP_ATTEMPTS);
    }

    public function getVerifyBucket()
    {
        return $this->getAttribute(self::VERIFY_BUCKET);
    }

    public function getVerifyAt()
    {
        return $this->getAttribute(self::VERIFY_AT);
    }

    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getSettledBy()
    {
        $settledBy = $this->getAttribute(self::SETTLED_BY);

        if ($settledBy === null)
        {
            $settledBy = 'Razorpay';
        }

        return $settledBy;
    }

    public function getReference1()
    {
        return $this->getAttribute(self::REFERENCE1);
    }

    public function getReference2()
    {
        return $this->getAttribute(self::REFERENCE2);
    }
    // Keeping the method to not break test flows
    public function getReference13()
    {
        return $this->getAttribute(self::SOURCE_CHANNEL);
    }

    public function getReference16()
    {
        return $this->getAttribute(self::REFERENCE16);
    }

    public function getReference17()
    {
        return $this->getAttribute(self::REFERENCE17);
    }

    public function getCardAttribute()
    {
        if ($this->relationLoaded('card') === true)
        {
            return $this->getRelation('card');
        }

        if ($this->hasCard() === true)
        {
            $card = (new Card\Repository)->findOrFail($this->getCardId());

            $this->card()->associate($card);

            return $card;
        }

        return null;
    }

    public function isSecondRecurring()
    {
        if (($this->isRecurring() === true) and
            ($this->isRecurringTypeAuto() === true))
        {
            return true;
        }

        return false;
    }

    public function isEmiMerchantSubvented()
    {
        return (Emi\Subvention::MERCHANT === $this->getAttribute(self::EMI_SUBVENTION));
    }

    public function getConvertCurrency()
    {
        return $this->getAttribute(self::CONVERT_CURRENCY);
    }

    public function getGatewayCaptured()
    {
        return $this->getAttribute(self::GATEWAY_CAPTURED);
    }

    /**
     * Get the rate at which currency conversion was applied to
     * the payment amount
     */
    public function getCurrencyConversionRate()
    {
        $baseAmount = $this->getBaseAmount();

        $paymentAmount = $this->getAmount();

        if ($paymentAmount === 0)
        {
            return 0;
        }

        return $baseAmount / $paymentAmount;
    }

    public function getMccCurrencyConversionRateApplied()
    {
        $baseAmount = $this->getBaseAmount();

        $paymentAmount = $this->getAmount();

        $rate = $baseAmount / $paymentAmount;

        return number_format($rate, 6, '.', ',');
    }

    public function getMccCurrencyConversionRateReceived()
    {
        $mccMarkup = (1-(self::MCC_MARKDOWN_PERCENTAGE/100));

        $rate = $this->getMccCurrencyConversionRateApplied()/ $mccMarkup;

        return number_format($rate, 6, '.', ',');
    }

    /**
     * This function returns the current payment method
     * and a detail string for that particular method
     * as a 2 length array. The array is numeric, instead
     * of associative because the detail key would be dependent
     * on the method itself otherwise (card.number, wallet.name, bank.name)
     * for eg.
     *
     * As such, we send a numeric array with the following details:
     *
     * ['card', $formattedCardNumber] (Just last 4 digits)
     * ['netbanking', $bankName] (Readable name for the bank)
     * ['wallet', $walletName] (Readable wallet name like PayTM)
     * @return array Payment Method Details
     */
    public function getMethodWithDetail()
    {
        // Gpay payment initially would have method set as unselected
        // which won't be there in our list of supported payment methods
        if (($this->getAuthenticationGateway() === self::GOOGLE_PAY) and
            ($this->getMethod() === Method::UNSELECTED))
        {
            return [$this->getMethod(), ''];
        }

        $method = Method::formatted($this->getMethod());

        switch($this->getMethod())
        {
            case Method::CARD:
            case Method::EMI:
                return [$method, $this->getFormattedCard()];
            case Method::NETBANKING:
                return [$method, $this->getBankName()];
            case Method::FPX:
                return [$method, $this->getBankName()];
            case Method::WALLET:
                return [$method, Processor\Wallet::getName($this->getWallet())];
            case Method::UPI:
                return [$method, $this->getVpa()];
            case Method::AEPS:
                return [$method, ''];
            case Method::BANK_TRANSFER:
                return [$method, ''];
            case Method::OFFLINE:
                return [$method, ''];
            case Method::EMANDATE:
                return [$method, $this->getBankName()];
            case Method::CARDLESS_EMI:
                /** Change the Walnut369 to Axio provider name in mail */
                if($this->getWallet() === Processor\CardlessEmi::WALNUT369)
                {
                    return [$method, Processor\CardlessEmi::AXIO];
                }
                return [$method, Processor\CardlessEmi::getName($this->getWallet())];
            case Method::PAYLATER:
                return [$method, Processor\PayLater::getName($this->getWallet())];
            case Method::APP:
                return [$method, Processor\App::getName($this->getWallet())];
            case Method::GIFT_CARDS:
                return [$method, Processor\GiftCard::getName($this->getGateway())];
        }
    }

    public function getIssuer()
    {
        $issuer = null;

        if (isset($this->issuer))
        {
            return $this->issuer;
        }
        else if ($this->hasCard() === true)
        {
            $issuer = $this->card->getIssuer();
        }
        else if (($this->isNetbanking() === true) or
                ($this->isEmandate() === true))
        {
            $issuer = $this->getBank();
        }
        else if ($this->isWallet() == true)
        {
            $issuer = $this->getWallet();
        }
        else if ($this->isUpi() === true)
        {
            $issuer = $this->getVpaHandleFromVpa();
        }
        else if ($this->isPayLater() === true)
        {
            $issuer = $this->getWallet();
        }
        else if ($this->isCardlessEmi() === true)
        {
            $issuer = $this->getWallet();
        }

        return $issuer;
    }

    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }

    public function getErrorDetails()
    {
        return [
            self::ERROR_CODE => $this->getAttribute(self::ERROR_CODE),
            self::ERROR_DESCRIPTION => $this->getAttribute(self::ERROR_DESCRIPTION),
        ];
    }

    public function getNetbankingReferenceId()
    {
        $netbankingRefId = null;

        if ($this->isNetbanking() === true)
        {
            $netbankingRefId = $this->getAttribute(self::REFERENCE1);
        }

        return $netbankingRefId;
    }

    /**
     * This is a heuristic method that tries to find
     * an order id the notes section
     * As of now, order_id is the first field inside notes
     * that ends with `_order_id`
     * We will shift to a standard field called `merchant_order_id`
     * as our ecommerce plugins are migrated
     * @return String order_id for the payment
     */
    public function getOrderId()
    {
        $notes = $this->getNotes();

        // Shortcut for direct order_id being set
        if (isset($notes['order_id']))
        {
            return $notes['order_id'];
        }

        foreach ($notes as $key => $value)
        {
            $orderIdSuffix = '_order_id';
            $ix = -1 * strlen($orderIdSuffix); // index from back
            if (substr($key, $ix) === $orderIdSuffix)
            {
                return $value;
            }
        }
        return false;
    }

    public function getApiOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function getInvoiceId()
    {
        return $this->getAttribute(self::INVOICE_ID);
    }

    public function getSubscriptionId()
    {
        return $this->getAttribute(self::SUBSCRIPTION_ID);
    }

    /**
     * @return Customer\Token\Entity
     */
    public function getGlobalOrLocalTokenEntity()
    {
        $token = null;

        if ($this->getTokenId() !== null)
        {
            $token = $this->getAttribute('localToken');
        }
        else if ($this->getGlobalTokenId() !== null)
        {
            $token = $this->getAttribute('globalToken');
        }

        return $token;
    }

    public function getForceTerminalId()
    {
        return $this->forceTerminalId;
    }

    public function getOptimiserProvider()
    {
        $app = \App::getFacadeRoot();

        try{
            if($app['basicauth']->isOptimiserDashboardRequest() === true)
            {
                if($this->terminal != null && $this->terminal->getProcurer() === 'merchant')
                {
                    return $this->terminal->getId();
                }
                else if($this->terminal == null) {
                    return '';
                }
                else
                {
                    return "Razorpay";
                }
            }
        } catch(\Throwable $e)
        {
            $app['trace']->traceException(
                $e,
                Trace::WARNING,
                TraceCode::OPTIMISER_PROVIDER_FETCH_FAILED,
                [
                    'payment_id' => $this->getId(),
                ]);
        }
        return '';
    }

    /**
     * Checks whether the recurring payment will
     * need to be authorized via sending a file
     */
    public function isFileBasedEmandateDebitPayment(): bool
    {
        if (($this->isEmandate() === true) and
            ($this->isRecurringTypeAuto() === true))
        {
            $gateway = $this->getGateway();

            //
            // This will be the case when during second recurring payment,
            // we are deciding whether to hit the gateway or not.
            // At that stage, the gateway is not yet set.
            //
            if ($gateway === null)
            {
                //
                // We don't really have to use gateway token here because
                // we are actually getting the gateway and not the terminal.
                // Gateway tokens need to be used when we are getting a terminal.
                // Since a token can have multiple terminals.
                // TODO: Check again ^
                //
                // Token will always be set if it's
                // emandate and recurring type is auto.
                //
                $gateway = $this->getGlobalOrLocalTokenEntity()->terminal->getGateway();
            }

            return (Gateway::isFileBasedEMandateDebitGateway($gateway) === true);
        }

        return false;
    }

    public function isFileBasedEmandateRegistrationPayment()
    {
        if (($this->isEmandate() === true) and
            ($this->isRecurringTypeInitial() === true))
        {
            $gateway = $this->getGateway();

            if ($gateway === null)
            {
                throw new Exception\LogicException(
                    'This function should not have been called when gateway is not set!',
                    ErrorCode::SERVER_ERROR_GATEWAY_NOT_SET,
                    [
                        'payment_id'        => $this->getId(),
                        'recurring_type'    => $this->getRecurringType(),
                        'method'            => $this->getMethod(),
                    ]);
            }

            if (($this->getAmount() > 0) and
                (Payment\Gateway::isDirectDebitEmandateGateway($gateway) === true))
            {
                return false;
            }

            return (Payment\Gateway::isFileBasedEMandateRegistrationGateway($gateway) === true);
        }

        return false;
    }

    public function isApiBasedEmandateAsyncPayment()
    {
        $token = $this->localToken;
        $gateway = $this->getGateway();

        if ($gateway === null)
        {
            $app = \App::getFacadeRoot();
            $app['trace']->critical(TraceCode::SERVER_ERROR_GATEWAY_NOT_SET,
                [
                    'payment_id'        => $this->getId(),
                    'recurring_type'    => $this->getRecurringType(),
                    'method'            => $this->getMethod(),
                ]);

            return false;
        }

        if (($this->isEmandate() === true) and
            (empty($token) === false) and
            (Payment\Gateway::isApiBasedAsyncEMandateGateway($gateway) === true))
        {

            $currentRecurringStatus = $token->getRecurringStatus();
            $isConfirmed = Token\RecurringStatus::isTokenStatusConfirmed($currentRecurringStatus);

            $isInitialAndTokenUnconfirmed = (($this->isRecurringTypeInitial() === true) and ($isConfirmed === false));
            $isAutoAndTokenConfirmed = (($this->isRecurringTypeAuto() === true) and ($isConfirmed === true));

            $app = \App::getFacadeRoot();
            $app['trace']->info(TraceCode::EMANDATE_AUTO_CAPTURE_REQUEST,[
                'payment_id'                         => $this->getId(),
                'current_recurring_status'           => $currentRecurringStatus,
                'is_confirmed'                       => $isConfirmed,
                'is_confirmed2'                      => ($isConfirmed === false),
                'is_recurring_type_initial'          => $this->isRecurringTypeInitial(),
                'is_recurring_type_initial2'         => ($this->isRecurringTypeInitial() === true),
                'is_initial_and_token_unconfirmed'   => $isInitialAndTokenUnconfirmed,
                'is_initial_and_token_unconfirmed2'  => ($this->isRecurringTypeInitial() === true) and ($isConfirmed === false),
                'is_recurring_type_auto'             => $this->isRecurringTypeAuto(),
                'is_auto_and_token_confirmed'        => $isAutoAndTokenConfirmed,
                'is_auto_and_token_confirmed2'       => ($this->isRecurringTypeAuto() === true) and ($isConfirmed === true),
            ]);

            return ($isInitialAndTokenUnconfirmed);
        }

        return false;
    }

    public function shouldCreateGatewayEntityForDebit(): bool
    {
        if (($this->isEmandate() === true) and
            ($this->isRecurringTypeAuto() === true))
        {
            $gateway = $this->getGlobalOrLocalTokenEntity()->terminal->getGateway();

            return Payment\Gateway::shouldCreateEnachGatewayEntity($gateway);
        }

        return false;
    }

    public function isAsyncEmandatePayment()
    {
        return (($this->isFileBasedEmandateDebitPayment() === true) or
                ($this->isFileBasedEmandateRegistrationPayment() === true));
    }

    public function getReferenceForGatewayToken()
    {
        if ($this->hasSubscription() === true)
        {
            $reference = $this->getSubscriptionId();
        }
        else
        {
            //
            // TODO: Get reference from checkout input.
            // This is required for charge at will local
            // recurring. Merchant passes this for every
            // new subscription.
            //
            // For subsequent charges, the reference won't
            // come from checkout input. It has to come from
            // the payment or something like that. So, for the
            // 2FA txns also, we should get from payment itself.
            // This requires us to store reference at a payment level,
            // like how we store subscription_id.
            //
            // For now, just returning back null.
            // Also, for Zoho, accept null, but for any
            // other merchant, throw an exception if it's null.
            // It should probably go in validateRecurringInput though.
            //

            $reference = null;
        }

        return $reference;
    }

    public function setPublicDetailedReasonAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        $internalErrorCode = $this->getInternalErrorCode();
        if ($internalErrorCode === null)
        {
            $array[self::ERROR_SOURCE] =  null;

            $array[self::ERROR_STEP] =  null;

            $array[self::ERROR_REASON] =  null;

            return;
        }

        $method = $this->getMethod();

        if($this->isUpiRecurring() === true)
        {
            $method = 'upi_autopay';
        }

        list($errorCodeJson,) = $app['error_mapper']->getErrorMapping($internalErrorCode, $method);

        $array[self::ERROR_SOURCE] = $errorCodeJson['source'] ?: null;

        $array[self::ERROR_STEP] = $errorCodeJson['step'] ?: null;

        $array[self::ERROR_REASON] = $errorCodeJson['reason'] ?: null;
    }

    public function setPublicDCCAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isAdminAuth() === true)
        {
            $paymentMetaEntity = $this->paymentMeta;

            $array[self::DCC] = $this->isDcc();

            $array[PaymentMeta\Entity::GATEWAY_AMOUNT] = $this->getGatewayAmount();

            $array[PaymentMeta\Entity::GATEWAY_CURRENCY] = $this->getGatewayCurrency();

            $array[PaymentMeta\Entity::FOREX_RATE] = ($paymentMetaEntity != null) ? $paymentMetaEntity->getForexRate() : null;

            $array[PaymentMeta\Entity::DCC_OFFERED] = ($paymentMetaEntity != null) ? $paymentMetaEntity->isDccOffered() : null;

            $array[PaymentMeta\Entity::DCC_MARK_UP_PERCENT] = ($paymentMetaEntity != null) ? $paymentMetaEntity->getDccMarkUpPercent() : null;

            $denominationFactorGatewayCurrency = Currency\Currency::DENOMINATION_FACTOR[$this->getGatewayCurrency()];
            $denominationFactorPaymentCurrency = Currency\Currency::DENOMINATION_FACTOR[$this->getCurrency()];
            $denominationFactor = $denominationFactorGatewayCurrency / $denominationFactorPaymentCurrency;

            $array[self::DCC_MARKUP_AMOUNT] = ($this->isDcc() === true) ?
                $this->getCurrencyConversionFee($this->getAmount(), $paymentMetaEntity->getForexRate(), $paymentMetaEntity->getDccMarkUpPercent(), $denominationFactor) : null;
        }
    }

    public function getPaymentMetaAttribute()
    {
        $app = \App::getFacadeRoot();

        if ($this->relationLoaded('paymentMeta') === true)
        {
            return $this->getRelation('paymentMeta');
        }

        // TODO: Check for Google when 2 diff payment meta's available
        $apiPaymentMeta = $this->paymentMeta()->first();

        if (isset($apiPaymentMeta) === true)
        {
            return $apiPaymentMeta;
        }

        try
        {
            $pgRouterPaymentMetaArray = $this->getAttribute('payment_meta_data');

            if (isset($pgRouterPaymentMetaArray) === true)
            {
                $paymentMetaInput = [
                    'gateway_amount'            => $pgRouterPaymentMetaArray['gateway_amount'],
                    'gateway_currency'          => $pgRouterPaymentMetaArray['gateway_currency'],
                    'forex_rate'                => $pgRouterPaymentMetaArray['forex_rate'],
                    'dcc_offered'               => true,
                    'payment_id'                => $this->getId(),
                    'dcc_mark_up_percent'       => $pgRouterPaymentMetaArray['dcc_mark_up_percent']
                ];
                if(isset($pgRouterPaymentMetaArray['mcc_applied']))
                {
                    $paymentMetaInput['mcc_applied'] = $pgRouterPaymentMetaArray['mcc_applied'];
                }
                if(isset($pgRouterPaymentMetaArray['mcc_forex_rate']))
                {
                    $paymentMetaInput['mcc_forex_rate'] = $pgRouterPaymentMetaArray['mcc_forex_rate'];
                }
                if(isset($pgRouterPaymentMetaArray['mcc_mark_down_percent']))
                {
                    $paymentMetaInput['mcc_mark_down_percent'] = $pgRouterPaymentMetaArray['mcc_mark_down_percent'];
                }

                $paymentMetaEntity = (new Payment\PaymentMeta\Entity)->forcefill($paymentMetaInput);

                $paymentMetaEntity->payment()->associate($this);

                $this->setRelation('paymentMeta', $paymentMetaEntity);

                $app['trace']->info(TraceCode::PG_ROUTER_PAYMENT_META_FORCE_FILLED, [
                    "payment_meta" => $paymentMetaEntity
                ]);

                return $paymentMetaEntity;
            }
            else
            {
                return null;
            }
        }
        catch (\Exception $ex)
        {
            $app['trace']->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PG_ROUTER_PAYMENT_META_FORCE_FILL_FAILED,
                [
                    'data' => $ex->getMessage()
                ]);
        }

        return null;
    }

    public function setPublicMCCAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isAdminAuth() === true)
        {
                $array[self::MCC] = $this->getConvertCurrency() === true;

                $array[self::FOREX_RATE_RECEIVED] = ($this->getConvertCurrency() === true) ? $this->getMccCurrencyConversionRateReceived() : null;

                $array[self::FOREX_RATE_APPLIED] = ($this->getConvertCurrency() === true) ? $this->getMccCurrencyConversionRateApplied() : null;
        }
    }

    /**
     * Partners who can onboard terminals would want to see terminal_id associated in their payments entity
     */
    public function setPublicTerminalIdAttribute(array & $array)
    {
        $merchant = $this->merchant;

        if ($merchant->isFeatureEnabledOnNonPurePlatformPartner(Feature\Constants::TERMINAL_ONBOARDING) === true)
        {
            $terminalId = $this->getTerminalId();

            $signedTerminalId = isset($terminalId) ? (new Terminal\Entity())->getSignedId($terminalId) : null;

            $array[self::TERMINAL_ID] = $signedTerminalId;

            return;
        }

        // unset terminal_id from public, if above condition is not true
        // not unsetting from $array as doing so will break anywhere someone do $payment['terminal'] in the code
        $key = array_search(self::TERMINAL_ID, $this->public);

        if ($key !== false)
        {
            unset($this->public[$key]);
        }
    }

    public function setPublicLateAuthorizedAttribute(array & $array)
    {
        $merchant = $this->merchant;

        $app = \App::getFacadeRoot();

        if ($merchant->isFeatureEnabledOnNonPurePlatformPartner(Feature\Constants::SEND_PAYMENT_LATE_AUTH) === true
        or $app['basicauth']->isOptimiserDashboardRequest() === true)
        {
            $lateAuth = $this->isLateAuthorized();

            $array[self::LATE_AUTHORIZED] = $lateAuth;

            return;
        }

        $key = array_search(self::LATE_AUTHORIZED, $this->public);

        if ($key !== false)
        {
            unset($this->public[$key]);
        }
    }

    /**
     * @param array $array
     *
     * Base amount should be set in public array iff
     * 1) route is privileged route
     * 2) the payment is non-inr payment
     */
    public function setPublicBaseAmountAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        if (($this->getCurrency() !== Currency\Currency::INR) or
            (($app['basicauth']->isProxyOrPrivilegeAuth() === true) and
                ($app['basicauth']->isCron() === false)))
        {
            return;
        }

        unset($array[self::BASE_AMOUNT]);
    }
    /**
     * @param array $array
     *
     * Base currency should be set in public array iff
     * 1) the payment is non-inr payment
     */
    public function setPublicBaseCurrencyAttribute(array & $array)
    {
        if ($this->getCurrency() === $this->merchant->getCurrency())
        {
            unset($array[self::BASE_CURRENCY]);
            return;
        }

        $array[self::BASE_CURRENCY] = $this->merchant->getCurrency();
    }

    public function setPublicOrderIdAttribute(array & $array)
    {
        if (isset($array[self::ORDER_ID]) and $array[self::ORDER_ID] != "")
        {
            $array[self::ORDER_ID] = Order\Entity::getSignedId($array[self::ORDER_ID]);
        }

        if (isset($array[self::ORDER_ID]) and $array[self::ORDER_ID] == "")
        {
            $array[self::ORDER_ID] = null;
        }
    }

    public function setPublicInternationalAttribute(array & $array)
    {
       if (($this->isUpi() === true) and
            ($this->isExternal() === true) and
            (is_bool($array[self::INTERNATIONAL]) === false))
       {
            $array[self::INTERNATIONAL] =  (bool) $array[self::INTERNATIONAL];
       }
    }

    public function setPublicFeeAttribute(array & $array)
    {
       if (($this->isUpi() === true) and
            ($this->isExternal() === true) and
            ((($this->isCaptured() === false) and
                    ($this->isFeeBearerPlatform() === true)) and
             ($this->isPartiallyOrFullyRefunded() === false)))
       {
            $array[self::FEE] =  null;
       }
    }

    public function setPublicTaxAttribute(array & $array)
    {
       if (($this->isUpi() === true) and
           ($this->isExternal() === true) and
           ((($this->isCaptured() === false) and
                   ($this->isFeeBearerPlatform() === true)) and
               ($this->isPartiallyOrFullyRefunded() === false)))
       {
            $array[self::TAX] =  null;
       }
    }

    public function setPublicProviderAttribute(array & $array)
    {

        if (($array[Entity::METHOD] === Method::APP) and
            (in_array($array[self::GATEWAY], Payment\Gateway::MULTIPLE_APPS_SUPPORTED_GATEWAYS) === false)
        ) {
            $array[self::PROVIDER] = $array[self::GATEWAY];
            unset($array[self::WALLET]);
        }

        else if (($array[Entity::METHOD] === Method::APP) and
            (in_array($array[self::GATEWAY], Payment\Gateway::MULTIPLE_APPS_SUPPORTED_GATEWAYS) === true)
        ) {
            $array[self::PROVIDER] = $array[self::WALLET];
            unset($array[self::WALLET]);
        }

        else if ($this->isMethodlessGooglePay() or $this->isPostMethodGooglePay())
        {
            $array[self::PROVIDER] = self::GOOGLE_PAY;
        }
    }

    public function setPublicInvoiceIdAttribute(array & $array)
    {
        if (isset($array[self::INVOICE_ID]))
        {
            $array[self::INVOICE_ID] = Invoice\Entity::getSignedId($array[self::INVOICE_ID]);

            return;
        }

        // Adding the below code for compatibility reasons.
        // For new pl service, invoice_id column is deprecated.
        // But there are some merchants using old invoices contract that depend on invoice_id value in payments fetch.
        // Since they were silently migrated to new service, we have to provide invoice_id till they switch their integrations to the new contract.
        $order = $this->order;

        $merchant = $this->merchant;

        if ($order === null or $merchant === null)
        {
            return;
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::PAYMENTLINKS_COMPATIBILITY_V2) === false)
        {
            return;
        }

        if ($order->getProductType() === Order\ProductType::PAYMENT_LINK_V2)
        {
           $productId =  $order->getProductId();

           $array[self::INVOICE_ID] = Invoice\Entity::getSignedId($productId);
        }
    }

    public function getPublicOrderId()
    {
        if ($this->hasOrder() === true)
        {
            return Order\Entity::getSignedId($this->getApiOrderId());
        }
    }

    public function setPublicCardIdAttribute(array & $array)
    {
        if (empty($array[self::CARD_ID]) === false)
        {
            $array[self::CARD_ID] =
                Card\Entity::getIdPrefix() . $this->getAttribute(self::CARD_ID);
        }
    }

    public function setPublicCustomerIdAttribute(array & $array)
    {
        if (empty($array[self::CUSTOMER_ID]) === false)
        {
            $customerId = $this->getAttribute(self::CUSTOMER_ID);

            $array[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
        }
        else
        {
            unset($array[self::CUSTOMER_ID]);
        }
    }

    public function setPublicTokenIdAttribute(array & $array)
    {
        if (empty($array[self::TOKEN_ID]) === false)
        {
            $tokenId = $this->getAttribute(self::TOKEN_ID);

            $array[self::TOKEN_ID] = Customer\Token\Entity::getSignedId($tokenId);
        }
        else
        {
            unset($array[self::TOKEN_ID]);
        }
    }

    public function setPublicSubscriptionIdAttribute(array & $array)
    {
        $subscriptionId = $this->getSubscriptionId();

        if (empty($subscriptionId) === false)
        {
            $array[self::SUBSCRIPTION_ID] = Subscription\Entity::getSignedId($subscriptionId);
        }
        else
        {
            unset($array[self::SUBSCRIPTION_ID]);
        }
    }

    public function setPublicRewardIdAttribute(array & $array)
    {
        if (isset($array[self::REWARD_ID]) and $array[self::REWARD_ID] != "")
        {
            $array[self::REWARD_ID] = RewardPoint\Entity::getSignedId($array[self::REWARD_ID]);
        }
        else
        {
            unset($array[self::REWARD_ID]);
        }
}

    public function setPublicAmountTransferredAttribute(array & $attributes)
    {
        //
        // The `amount_transferred` attributes is only needed for
        // for dashboard and should be hidden in private API
        // requests
        //
        $app = \App::getFacadeRoot();

        if (($app['basicauth']->isProxyOrPrivilegeAuth() === false) or
            (($app['basicauth']->isProxyOrPrivilegeAuth() === true) and
                ($app['basicauth']->isCron() === true)))
        {
            unset($attributes[self::AMOUNT_TRANSFERRED]);

            return;
        }

        $attributes[self::AMOUNT_TRANSFERRED] = $this->getAmountTransferred();
    }

    public function setPublicGatewayProviderAttribute(array & $array)
    {
        if (($this->merchant === null) or
            ($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_GATEWAY_PROVIDER) === false))
        {
            unset($array[self::GATEWAY_PROVIDER]);
        }
    }

    public function setPublicSettledByAttribute(array & $array)
    {
        if ($this->merchant === null)
        {
            unset($array[self::SETTLED_BY]);
            return;
        }

        if ($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_SETTLED_BY) === false)
        {
            $app = \App::getFacadeRoot();
            if($app['basicauth']->isOptimiserDashboardRequest() === false)
            {
                unset($array[self::SETTLED_BY]);
                return;
            }
        }

        if (isset(Payment\Gateway::DIRECT_SETTLEMENT_ORG_NAME[$array[self::SETTLED_BY]]) === true)
        {
            $array[self::SETTLED_BY] = Payment\Gateway::DIRECT_SETTLEMENT_ORG_NAME[$array[self::SETTLED_BY]];
        }
    }

    public function setPublicAccountIdAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        $auth = $app['basicauth'];

        /*
         *  Set accounttId attribute if
         * 1) we are in non privileged auth and payment merchant id is different from auth merchant id
         */

        if ($auth->isPrivilegeAuth() === true)
        {
            return;
        }

        if (($auth->getMerchant() === null) or
            ($auth->getMerchant()->getId() === $this->getMerchantId()))
        {
            return;
        }

        $array[self::ACCOUNT_ID] = Account\Entity::getSignedId($array[self::MERCHANT_ID]);
    }

    public function setPublicFeeBearerAttribute(array & $array)
    {
        unset($array[self::FEE_BEARER]);

        $app = \App::getFacadeRoot();

        $allowedProxyRoutes = [
            'payment_fetch_by_id',
        ];

        $allowedAdminRoutes = [
            'admin_fetch_entity_by_id',
            'admin_fetch_entity_multiple',
            'payment_verify',
            'payment_verify_bulk',
        ];

        $route = $app['request.ctx']->getRoute();

        if (($app['basicauth']->isProxyAuth() === true) and
            (in_array($route, $allowedProxyRoutes) === true))
        {
            $array[self::FEE_BEARER] = $this->getFeeBearer(true);

            return;
        }

        if (($app['basicauth']->isAdminAuth() === true) and
            (in_array($route, $allowedAdminRoutes) === true))
        {
            $array[self::FEE_BEARER] = $this->getFeeBearer(true);

            return;
        }

    }

    public function setPublicGatewayDataAttribute(array & $array)
    {
        if (($this->merchant !== null) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_GATEWAY_ERRORS) === true) and
            (empty($this->getReference17()) === false) and
            ($this->isStatusCreatedOrFailed() === true)
        )
        {
            $ref17 = json_decode($this->getReference17(), true) ?? [];

            $array[self::GATEWAY_DATA][self::ERROR_CODE]         = $ref17[self::GATEWAY_ERROR_CODE] ?? "";
            $array[self::GATEWAY_DATA][self::ERROR_DESCRIPTION]  = $ref17[self::GATEWAY_ERROR_DESCRIPTION] ?? "";

            return;
        }

        unset($array[self::GATEWAY_DATA]);
    }

    public function setPublicOptimizerProviderAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        try{
            // We only want to set the Provider while serving requests from optimiser dashboard

            if($app['basicauth']->isOptimiserDashboardRequest() === true)
            {
                if($this->terminal != null && $this->terminal->getProcurer() === 'merchant')
                {
                    $array[self::OPTIMIZER_PROVIDER] = $this->terminal->getId();
                }
                else if($this->terminal == null) {
                    $array[self::OPTIMIZER_PROVIDER] = '';
                }
                else
                {
                    $array[self::OPTIMIZER_PROVIDER] = "Razorpay";
                }
            }
        } catch(\Throwable $e)
        {
            $app['trace']->traceException(
                $e,
                Trace::WARNING,
                TraceCode::FETCH_PAYMENTS_FAILED_TO_SET_OPTIMIZER_PROVIDER,
                [
                    'payment_id' => $this->getId(),
                ]);
            $array[self::OPTIMIZER_PROVIDER] = '';
        }
    }

    public function setPublicUpiMetadataAttribute(array &$data)
    {
        unset($data[self::UPI_METADATA]);

        // If the payment is not an upi in_app payment, we simply return
        if ($this->isInAppUPI() === false)
        {
            return;
        }

        $admissibleRoutes = [
            'payment_fetch_by_id',
            'payment_fetch_multiple',
        ];

        $app = \App::getFacadeRoot();
        $route = $app['request.ctx']->getRoute();

        // If the route is not among the admissible routes OR the request is not over proxy auth, we return
        if ((in_array($route, $admissibleRoutes) === false) or
            ($app['basicauth']->isProxyAuth() === false))
        {
            return;
        }

        // Otherwise, we populate the upi_metadata object inside the payment object as follows
        $data[self::UPI_METADATA][UpiMetadata\Entity::FLOW] = UpiMetadata\Flow::IN_APP;
    }

    public function associateTerminal($terminal)
    {
        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $this->toArrayAdmin()]);
        }

        $this->terminal()->associate($terminal);

        $gateway = $terminal->getGateway();

        $this->setGateway($gateway);

        $this->setSettledBy('Razorpay');

        if ($terminal->isDirectSettlement() === true)
        {
            $settledBy = Payment\Gateway::DIRECT_SETTLEMENT_GATEWAYS[$gateway];

            if (is_array($settledBy) === true)
            {
                $acquirers = Payment\Gateway::DIRECT_SETTLEMENT_GATEWAYS[$gateway];

                $settledBy = $acquirers['default'];

                if (($terminal->getGatewayAcquirer() !== null) and
                    (array_key_exists($terminal->getGatewayAcquirer(), $acquirers) === true))
                {
                    $settledBy = $acquirers[$terminal->getGatewayAcquirer()];
                }
            }

            $this->setSettledBy($settledBy);
        }

        $this->setRelation('terminal', $terminal);
    }


    public function disassociateTerminal()
    {
        if ($this->terminal === null)
        {
            return;
        }

        $this->terminal()->dissociate();

        $this->setGateway(null);

        $this->setSettledBy(null);
    }

// ----------------------- Getters Ends-----------------------------------------

    public function toArrayWithCard()
    {
        $data = $this->getAttributes();

        $card = $this->card()->first();

        if ($card === null)
        {
            throw new Exception\LogicException(
                'Associated card not found for the current payment entity',
                null,
                [
                    'payment_id'    => $this->getId(),
                ]);
        }

        $cardData = $card->getAttributes();

        $data['card'] = $cardData;

        return $data;
    }
    public function toArrayAdmin()
    {
        $settledBy = $this->getSettledBy();

        $attributes = parent::toArrayAdmin();

        $attributes[Entity::SETTLED_BY] = $settledBy;

        $this->setConvenienceFeeAttributesForDashboard($attributes);

        $this->setUpiIfApplicable($attributes);

        $this->setChannelIfUpiPoDTransaction($attributes);

        return $attributes;
    }

    public function setChannelIfUpiPoDTransaction(array &$attributes)
    {
        if (!($this->receiver instanceof QrV2\Entity) or
            $this->receiver?->getUsageType()  !== QrV2\UsageType::SINGLE_USE)
        {
	            return;
        }

        $analytics = $this->analytics;

        if (isset($analytics))
        {
            $isLibraryInvalid =
                (
                    empty($analytics[Payment\Analytics\Entity::LIBRARY]) or
                    ($analytics[Payment\Analytics\Entity::LIBRARY] !== Metadata::LIBRARY_VALUES[Metadata::CHECKOUTJS] and
                     $analytics[Payment\Analytics\Entity::LIBRARY] !== Metadata::LIBRARY_VALUES[Metadata::HOSTED])
                );

            $isDeviceInvalid =
                (empty($analytics[Payment\Analytics\Entity::DEVICE]) or
                ($analytics[Payment\Analytics\Entity::DEVICE] !== Metadata::DEVICE_VALUES[Metadata::DESKTOP]));


            if ($isLibraryInvalid && $isDeviceInvalid)
            {
                $attributes['channel'] = 'offline_pod';
            }
        }
    }

    public function toArrayRecon()
    {
        $attributes = parent::toArrayRecon();

        $attributes[self::MERCHANT_ID] = $this->getMerchantId();

        return $attributes;
    }

    public function toArrayAdminRestricted(array $attributes)
    {
        $attributes = parent::toArrayAdminRestricted($attributes);

        try {

            if( (new Terminal\Service())->removeAPITerminalReads(__FUNCTION__))
            {
                (new Terminal\Service())->pushTerminalDirectReadMetrics(__FUNCTION__, true);

                $terminal = $this->terminal;
            }
            else
            {
                (new Terminal\Service())->pushTerminalDirectReadMetrics(__FUNCTION__, false);

                $terminal = $this->terminal()->first();
            }

        } catch(\Throwable $ex) {
            (new Terminal\Service())->pushTerminalDirectReadErrorMetrics(__FUNCTION__, $ex);

            throw $ex;
        }

        if ($terminal === null)
        {
            return $attributes;
        }

        $gatewayTerminalId = $terminal->getGatewayTerminalId();

        $attributes[Terminal\Entity::GATEWAY_TERMINAL_ID] = $gatewayTerminalId;

        return $attributes;
    }

    public function toArrayAdminRestrictedWithFeature(array $attributes, $orgType, $orgFeature)
    {
        $attributes = parent::toArrayAdminRestrictedWithFeature($attributes, $orgType, $orgFeature);

        try {
            if( (new Terminal\Service())->removeAPITerminalReads(__FUNCTION__))
            {
                (new Terminal\Service())->pushTerminalDirectReadMetrics(__FUNCTION__, true);

                $terminal = $this->terminal;
            }
            else
            {
                (new Terminal\Service())->pushTerminalDirectReadMetrics(__FUNCTION__, false);

                $terminal = $this->terminal()->first();
            }
        }
        catch(\Throwable $ex)
        {
            (new Terminal\Service())->pushTerminalDirectReadErrorMetrics(__FUNCTION__, $ex);

            throw $ex;
        }


        if ($terminal === null)
        {
            return $attributes;
        }

        $gatewayTerminalId = $terminal->getGatewayTerminalId();

        $attributes[Terminal\Entity::GATEWAY_TERMINAL_ID] = $gatewayTerminalId;

        return $attributes;
    }

    public function toArrayDashboard()
    {
        $data = $this->toArray();

        $data[self::ID] = $this->getPublicId();

        if ($this->isMethodCardOrEmi())
        {
            $card = $this->card()->firstOrFail();

            $network = $card->getNetwork();

            $data['network'] = $network;
        }

        return $data;
    }

    public function toArrayReport()
    {
        $data = parent::toArrayReport();

        unset($data[self::CUSTOMER_ID]);
        unset($data[self::TOKEN_ID]);

        $data[self::NOTES] = $this->getNotesJson();

        $data['card_type'] = null;
        $data['card_network'] = null;
        $data['invoice_id'] = null;

        if ($this->isMethodCardOrEmi())
        {
            $data['card_type'] = $this->card->getType();
            $data['card_network'] = $this->card->getNetwork();
        }

        if ($this->getInvoiceId() !== null)
        {
            $data['invoice_id'] = $this->getInvoiceId();
        }

        $merchantCore = new Merchant\Core();

        if ($merchantCore->isShowLateAuthAttributeFeatureEnabled($this->merchant))
        {
            $data[self::AUTHORIZED_AT] = $this->getAuthorizeTimestamp();
            $data[self::AUTO_CAPTURED] = $this->getAutoCaptured();
            $data[self::CAPTURED_AT] = $this->getCapturedAt();

            if(isset($data[self::CAPTURED_AT]) === false )
            {
                $data[self::AUTO_CAPTURED] = null;
            }

            $data[self::LATE_AUTHORIZED] = $this->isLateAuthorized();
        }

        if ($merchantCore->isShowReceiverTypeFeatureEnabled($this->merchant) === true)
        {
            $data[self::RECEIVER_TYPE] = $this->getReceiverType();
        }

        return $data;
    }

    public function toArrayGateway()
    {
        $data = $this->toArray();

        if ($this->isCard() === true)
        {
            $data['amount'] = $this->getGatewayAmount();
            $data['currency'] = $this->getGatewayCurrency();
            $merchant = $this->merchant;
            $app = \App::getFacadeRoot();
            $variantFlag = $app['razorx']->getTreatment($this->merchant->getId(),
                RazorxTreatment::SEND_DCC_INDICATOR,
                $app['rzp.mode']);
            if($variantFlag === "on"){
                $data['dcc'] = $this->isDCC();

                // the field 'merchant_currency' is added so that router service can select terminals
                // based on INR instead of user card currency
                // this case is valid in case of hitachi where the terminal has to be in the currency of the merchant
                // valid in case of international payments dcc payments and dcc over mcc payments.
                $data['merchant_pay_amount'] = $this->getAmount(); // amount to be settled to merchant in his home currency
            }

            // Fields to be passed cybersource gateway for DCC payment.
            if ($this->isDCC() and $merchant->isFeatureEnabled(Feature\Constants::DYNAMIC_CURRENCY_CONVERSION_CYBS))
            {
                $data['merchant_pay_amount'] = $this->getAmount();

                $data['merchant_currency'] =  $this->isDCC() ? Currency\Currency::INR : "";

                $data['forex_rate'] = $this->paymentMeta->getForexRate() ?? null;

                $data['dcc_mark_up_percent'] = floatval($this->paymentMeta->getDccMarkUpPercent() ?? null);
            }

            if (($this->merchant->isAddressRequiredEnabledInternationalMerchant() === true) and
                ($this->isInternational() === true))
            {
                $billingAddressFromDb = $this->fetchBillingAddress();

                $data['billing_address'] = ($billingAddressFromDb !== null) ?
                    $billingAddressFromDb->getBillingAddress() : $this->getBillingAddress();
            }

            if ($this->skipCvvCheck() === true)
            {
                $data['skip_cvv_check'] = true;
            }
        }

        if ($this->merchant->isLRSFlowEnabled())
        {
            $data['amount'] = $this->getGatewayAmount();
            $data['currency'] = $this->getGatewayCurrency();
        }

        if(($this->getGateway() === Gateway::WALLET_PAYPAL) or
            ($this->isWalletPaypal() === true))
        {
            $data['amount'] = $this->getGatewayAmount();
            $data['currency'] = $this->getGatewayCurrency();
        }

        if (($this->getGateway() === Gateway::EMERCHANTPAY) or
            (Gateway::isDCCRequiredApp($this->getWallet()) === true))
        {
            $data['amount'] = $this->getGatewayAmount();
            $data['currency'] = $this->getGatewayCurrency();

            $data['billing_address'] = $this->getBillingAddress();
        }

        if (($this->isCard() === true) and
            ($this->getConvertCurrency() === true))
        {
            $data['amount'] = $this->getBaseAmount();
            $data['currency'] = Currency\Currency::INR;
            $data['amount_refunded'] = $this->getBaseAmountRefunded();
        }

        if ($this->getMethod() === Method::CARDLESS_EMI and $this->merchant->IsCardlessEmiConvenienceFeeEnabled() === true) {
            $data['amount'] = $this->getGatewayAmount();
        }

        return $data;
    }

    public function toArrayHosted()
    {
        $data = parent::toArrayHosted();

        $data[self::FORMATTED_AMOUNT] = $this->getFormattedAmount();

        $createdAt = Carbon::createFromTimestamp($this->getCreatedAt(), Timezone::IST);

        $data[self::FORMATTED_CREATED_AT] = $createdAt->format(self::HOSTED_TIME_FORMAT);

        return $data;
    }

// --------------- Relation to other entities ----------------------------------

    public function card()
    {
        return $this->belongsTo('RZP\Models\Card\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }

    public function refunds()
    {
        return $this->hasMany('RZP\Models\Payment\Refund\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function settlement()
    {
        return $this->hasOneThrough(
            'RZP\Models\Settlement\Entity',
            'RZP\Models\Transaction\Entity',
            'entity_id',
            'id',
            'id',
            'settlement_id'
        );
    }

    public function hdfc()
    {
        return $this->hasOne('hdfc', 'trackid', 'id');
    }

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }

    public function subscription()
    {
        return $this->belongsTo('RZP\Models\Plan\Subscription\Entity');
    }

    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }

    public function analytics()
    {
        return $this->hasOne('RZP\Models\Payment\Analytics\Entity');
    }

    public function bankTransfer()
    {
        return $this->hasOne('RZP\Models\BankTransfer\Entity');
    }

    public function bharatQr()
    {
        return $this->hasOne('RZP\Models\BharatQr\Entity');
    }

    public function upiTransfer()
    {
        return $this->hasOne('RZP\Models\UpiTransfer\Entity');
    }

    public function qrPayment()
    {
        return $this->hasOne('RZP\Models\QrPayment\Entity');
    }

    public function paymentMeta()
    {
        return $this->hasOne('RZP\Models\Payment\PaymentMeta\Entity');
    }

    public function batch()
    {
        return $this->belongsTo('RZP\Models\Batch\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function globalCustomer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity', self::GLOBAL_CUSTOMER_ID);
    }

    public function token()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::TOKEN_ID);
    }

    public function localToken()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::TOKEN_ID)->withTrashed();
    }

    public function globalToken()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::GLOBAL_TOKEN_ID)->withTrashed();
    }

    public function app()
    {
        return $this->belongsTo('RZP\Models\Customer\AppToken\Entity', self::APP_TOKEN);
    }

    public function emi()
    {
        return $this->belongsTo('RZP\Models\Emi\Entity', self::EMI_PLAN_ID)->withTrashed();
    }

    public function senderAddress()
    {
        return $this->hasOne(Address\Entity::class, Address\Entity::ENTITY_ID)->latest()->where(Address\Entity::TYPE, Address\Type::SENDER_ADDRESS);
    }

    public function emiPlan()
    {
        return $this->belongsTo('RZP\Models\Emi\Entity')->withTrashed();
    }

    public function transfers()
    {
        return $this->morphMany('RZP\Models\Transfer\Entity', 'source');
    }

    public function paymentLink()
    {
        return $this->belongsTo(PaymentLink\Entity::class);
    }

    public function receiver()
    {
        return $this->morphTo('receiver', self::RECEIVER_TYPE, self::RECEIVER_ID)->withTrashed();
    }

    public function netbanking()
    {
        return $this->hasOne('RZP\Gateway\Netbanking\Base\Entity');
    }

    public function cardMandateNotification()
    {
        return $this->hasOne('RZP\Models\CardMandate\CardMandateNotification\Entity');
    }

    public function enach()
    {
        return $this->hasOne('RZP\Gateway\Enach\Base\Entity');
    }

    // using hasOne here as we need only the first billdesk entity, actual relation can be one-to-many
    public function billdesk()
    {
        return $this->hasOne('RZP\Gateway\Billdesk\Entity');
    }

    public function transfer()
    {
        return $this->belongsTo('RZP\Models\Transfer\Entity', self::TRANSFER_ID);
    }

    public function upiMetadata()
    {
        return $this->hasOne('RZP\Models\Payment\UpiMetadata\Entity');
    }

    public function disputes()
    {
        return $this->hasMany(\RZP\Models\Dispute\Entity::class);
    }

    public function discount()
    {
        if($this->getWallet() == Payment\Processor\CardlessEmi::LIQUILOANS)
        {
            return $this->hasOne('RZP\Models\Discount\Entity')->whereNotNull(\RZP\Models\Discount\Entity::OFFER_ID);
        }
        return $this->hasOne('RZP\Models\Discount\Entity');
    }

    public function reward()
    {
        return $this->belongsTo(RewardPoint\Entity::class);
    }

    /**
     * Points to the pivot table entity `entityOrigin` for the payment
     */
    public function entityOrigin()
    {
        return $this->morphOne(\RZP\Models\EntityOrigin\Entity::class, 'entity');
    }

    /**
     * Points to the pivot table entity `entityOffer` for the payment
     */
    private function entityOffer()
    {
        return $this->morphOne(Offer\EntityOffer\Entity::class, 'entity');
    }

    public function offers()
    {
        return $this->morphToMany(
                        Offer\Entity::class,
                        'entity',
                        Table::ENTITY_OFFER)
                    ->withTimestamps();
    }

    public function associateOffer(Offer\Entity $offer)
    {
        // Creates row in entity_offers table
        $this->offers()->attach($offer);

        $offerCollection = new Base\PublicCollection();
        $offerCollection->push($offer);
        $this->setRelation('offers', $offerCollection);
    }

    public function associateReward($rewardId)
    {
        // Creates row in entity_offers table
        $this->offers()->attach($rewardId, ['entity_offer_type' => 'reward']);
    }

    public function dissociateOffer(Offer\Entity $offer)
    {
        $this->offers()->detach($offer->getId());

        $this->unsetRelation('offers');
    }

    /**
     * Works cos we only associate one offer with payment
     * @return Offer\Entity
     */
    public function getOffer()
    {
        $offerNotFoundInOE = false;

        if (app()->isEnvironmentQA() === false)
        {
            if ($this->relationLoaded('offers') === true)
            {
                return $this->getRelation('offers')->first();
            }

            try
            {
                $offerIds = $this->entityOffer()
                                 ->pluck(Offer\EntityOffer\Entity::OFFER_ID)
                                 ->toArray();

                if (empty($offerIds) === true)
                {
                    return null;
                }

                $offersEngineRepo = new Offer\Repository();

                // fetches normal offers from OE and limited offers from API db.
                // The assumption here is that payment is always associated with one offer_id
                $offerEntity = $offersEngineRepo->findByIdAndMerchantId(
                    $offerIds[0], $this->getMerchantId(), null, true);

                $offersCollection = new Base\PublicCollection();

                $offersCollection->push($offerEntity);

                $this->setRelation('offers', $offersCollection);

                app('trace')->info(TraceCode::OFFER_FOR_PAYMENT_FOUND, [
                    'offer_id'   => optional($offerEntity)->getId(),
                    'payment_id' => $this->getId(),
                ]);

                return $offersCollection->first();
            }
            catch (\Throwable $ex)
            {
                if ($ex->getCode() === ErrorCode::BAD_REQUEST_EXTERNAL_OFFER_NOT_FOUND)
                {
                    $offerNotFoundInOE = true;
                }

                app('trace')->count(
                    Offer\Metric::OFFERS_ENGINE_FETCH_OFFERS_FAIL_FOR_PAYMENTS, [
                    'route' => app('api.route')->getCurrentRouteName(),
                ]);

                app('trace')->traceException($ex, Trace::ERROR, TraceCode::PAYMENT_OFFER_NOT_FOUND, [
                    'data'                  => $ex->getMessage(),
                    'id'                    => $this->getId(),
                    'offer_not_found_in_oe' => $offerNotFoundInOE,
                ]);
            }
        }

        // Keeping fallback on API DB for now.
        $response = $this->offers()->first();

        if (($response !== null) and
            ($offerNotFoundInOE === true))
        {
            app('trace')->count(
                Offer\Metric::OFFERS_FETCH_MISMATCH_PAYMENT_FLOW, [
                'route' => app('api.route')->getCurrentRouteName(),
            ]);

            app('trace')->info(TraceCode::OFFERS_FETCH_MISMATCH_PAYMENT_FLOW, [
                'payment_id'   => $this->getId(),
                'api_response' => $response,
            ]);
        }

        return $response;
    }

    /**
     * @return UpiMetadata\Entity
     */
    public function getUpiMetadata()
    {
        return $this->upiMetadata;
    }

    /**
     * fetches the upi metadata associated with payments
     * @return UpiMetadata\Entity
     */
    public function getUpiMetadataAttribute()
    {
        // return if metadata is already present
        if ($this->hasMetadata(UpiMetadata\Entity::UPI_METADATA) === true)
        {
            return $this->getMetadata(UpiMetadata\Entity::UPI_METADATA);
        }

        $upiMetadata = null;

        if ($this->relationLoaded('upi_metadata') === true)
        {
            $upiMetadata = $this->getRelation('upi_metadata');
        }

        if (isset($upiMetadata) === true)
        {
            return $upiMetadata;
        }

        // upi_metadata for re-arch payments will not be present in API DB
        if ($this->isExternal() === true)
        {
            return null;
        }

        $upiMetadata = (new UpiMetadata\Repository())->fetchByPaymentId($this->getId());

        if (isset($upiMetadata) === false)
        {
            return null;
        }

        $upiMetadata->payment()->associate($this);

        // set upi_metadata in metadata key
        $this->metadata[UpiMetadata\Entity::UPI_METADATA] = $upiMetadata;

        return $upiMetadata;
    }


// --------------- Relation to other entity section ends -----------------------

    public function refundAmount($amount, $baseAmount, $partialcaptureflag=null)
    {
        if ((is_int($amount) === false) or
            (is_int($baseAmount) === false))
        {
            throw new Exception\InvalidArgumentException(
                'amount should be an integer ' . $amount);
        }

        $amount = (int) $amount;

        $baseAmount = (int) $baseAmount;

        $amountUnrefunded = $this->getAmountUnrefunded();

        $amountcaptured = $this->getAmountCaptured();

        //for partial capture usecase
        $amountEligibleforRefund = $amountcaptured - $this->getAmountRefunded();

        if ($amount < $amountUnrefunded)
        {
            $this->setRefundStatus(RefundStatus::PARTIAL);
            if($partialcaptureflag && $amount == $amountEligibleforRefund){
                $this->setRefundStatus(RefundStatus::FULL);

                $this->setStatus(Payment\Status::REFUNDED);
            }
        }
        else if ($amount === $amountUnrefunded)
        {
            $this->setRefundStatus(RefundStatus::FULL);

            $this->setStatus(Payment\Status::REFUNDED);

            // Setting refund_at to null when, payment is refunded, so it wont get picked
            // up by cron.
            $this->setRefundAt(null);
        }
        else
        {
            throw new Exception\LogicException(
                'Refund amount should be less than or equal to amount not refunded yet',
                null,
                [
                    'amount'            => $amount,
                    'amount_unrefunded' => $amountUnrefunded,
                    'payment_id'        => $this->getId(),
                ]);
        }

        $amountRefunded = $this->getAmountRefunded() + $amount;

        $baseAmountRefunded = $this->getBaseAmountRefunded() + $baseAmount;

        $this->setAttribute(self::AMOUNT_REFUNDED, $amountRefunded);

        $this->setAttribute(self::BASE_AMOUNT_REFUNDED, $baseAmountRefunded);

        // Enabling this check specific to hdfc surcharge as
        // we dont want to get partial refunds created with gateway amount as zero
        if (($this->isHdfcVasDSCustomerFeeBearerSurcharge() === true) and
            ($amountRefunded >= $this->getGatewayAmount()))
        {
            $this->setRefundStatus(RefundStatus::FULL);

            $this->setStatus(Payment\Status::REFUNDED);

            $this->setRefundAt(null);
        }
    }

    public function transferAmount(int $amount)
    {
        $amountUntransferred = $this->getAmountUntransferred();

        if ($amount > $amountUntransferred)
        {
            throw new Exception\LogicException(
                'Transfer amount should be less than or equal to amount not transferred yet',
                null,
                [
                    'amount'                => $amount,
                    'amount_untransferred'  => $amountUntransferred,
                    'payment_id'            => $this->getId(),
                ]);
        }

        $amountTransferred = $this->getAmountTransferred() + $amount;

        $this->setAttribute(self::AMOUNT_TRANSFERRED, $amountTransferred);
    }

    public function transferAmountFix(int $amount)
    {
        $this->setAttribute(self::AMOUNT_TRANSFERRED, $amount);
    }

    /**
     * Updates Payment amount_paidout field
     *
     * @param  int $amount
     *
     * @throws Exception\LogicException
     */
    public function payoutAmount(int $amount)
    {
        $amountPaidout = $this->getAmountPaidout() + $amount;

        $paymentAmount = $this->getAmount();

        if ($amountPaidout > $paymentAmount)
        {
            throw new Exception\LogicException(
                'Payment payout: Payout total greater than payment amount',
                null,
                [
                    'payout'            => $amount,
                    'payment_amount'    => $paymentAmount,
                    'payment_id'        => $this->getId(),
                ]
            );
        }

        $this->setAmountPaidout($amountPaidout);
    }

    public function toArrayTraceRelevant()
    {
        $fields = [
            self::ID,
            self::MERCHANT_ID,
            self::CARD_ID,
            self::STATUS,
            self::AMOUNT,
            self::LATE_AUTHORIZED,
            self::AUTO_CAPTURED,
            self::ERROR_CODE,
            self::GATEWAY,
            self::RECEIVER_ID,
            self::RECEIVER_TYPE,
            self::SOURCE_CHANNEL,
            self::VERIFY_AT,
            self::VERIFY_BUCKET,
        ];

        $relevantData = array_intersect_key($this->attributes, array_flip($fields));

        return $relevantData;
    }

    public function shouldTimeout(int $now)
    {
        $timeoutPeriod = $this->getTimeoutWindow();

        $diff = $now - $this->getCreatedAt();

        return ($diff >= $timeoutPeriod);
    }

    public function shouldTimeoutAuthenticatedPayment(int $now)
    {
        $timeoutPeriod = $this->getTimeoutWindow();

        $diff = $now - $this->getAuthenticatedAt();

        return ($diff >= $timeoutPeriod);
    }

// --------------------- Query scopes section begin ----------------------------

    public function scopeStatus($query, $status)
    {
        return $query->where(Payment\Entity::STATUS, '=', $status);
    }

    public function scopeStatusSuccess($query)
    {
        return $query->whereNotIn(Entity::STATUS, [Status::FAILED, Status::CREATED]);
    }

// --------------------- Query scopes section ends -----------------------------

    public function resetOtpAttempts()
    {
        $this->setOtpAttempts(null);
    }

    /**
     * List of all features based on various conditions
     */
    public function getPricingFeatures()
    {
        $features = [];

        if ($this->isRecurring() === true)
        {
            $features[] = Pricing\Feature::RECURRING;
        }

        if($this->isInAppUPI() === true)
        {
            $features[] = Pricing\Feature::UPI_INAPP;
        }

        if ($this->hasReward() === true)
        {
            $features[] = Pricing\Feature::REWARD;
        }

        if (($this->isEmi() === true) and
            ($this->merchant->getEmiSubvention() === Emi\Subvention::MERCHANT))
        {
            $features[] = Pricing\Feature::EMI;
        }

        if ($this->merchant->isFeatureEnabled(Feature\Constants::ES_AUTOMATIC) === true)
        {
            $features[] = Pricing\Feature::ESAUTOMATIC;
        }

        if ($this->merchant->isFeatureEnabled(Feature\Constants::RAAS) === true)
        {
            $features[] = Pricing\Feature::OPTIMIZER;
        }

        if ($this->merchant->isAtLeastOneFeatureEnabled(Feature\Constants::OPTIMIZER_CFB_FEATURES) === true &&
            $this->isOptimizerTerminal() == true)
        {
            $features[] = Pricing\Feature::OPTIMIZER_CONVENIENCE_FEE;
        }


        $order = $this->getOrderAttribute();

        if(empty($order) === false) {
            if($order->isMagicCheckoutOrder() === true)
            {
                $features[] = Pricing\Feature::MAGIC_CHECKOUT;
            }

            if($this->isNoCodeAppFeatureRuleApplicable($order))
            {
                $features[] = Pricing\Feature::NOCODEAPPS;
            }
        }

        $orderId = null;
        if(empty($order) === false) {
            $orderId = $order->getId();
        }

        $app = \App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? Mode::LIVE;

        if ($this->merchant->isFeatureEnabled(Feature\Constants::BUYER_PROTECT_SIGNED_UP) &&
            $this->isBuyerProtectionEnabled($this->getId(), $this->getAmount(), $this->getBaseAmount(), $this->merchant->getId(), $mode, $orderId) === true)
        {
            $features[] = Pricing\Feature::BUYER_PROTECTION;
        }

        return $features;
    }

    protected function isOptimizerTerminal()
    {
        $terminal = $this->getTerminalAttribute();
        if($terminal != null)
        {
            return $terminal->isOptimizer();
        }
        return true;
    }


    protected function isNoCodeAppFeatureRuleApplicable($order) : bool
    {
        $isMonetizedOrder = (new PaymentLink\Service)->isMonetizedNoCodeAppsOrder($order);

        $isFeatureEnabled = $this->merchant->isFeatureEnabled(Feature\Constants::NOCODEAPP_FEE_APPLICABLE);

        return $isMonetizedOrder && $isFeatureEnabled;
    }

    private function isBuyerProtectionEnabled(string $paymentId, int $amount, int $baseAmount, string $merchantId, string $mode, ?string $orderId): bool
    {
        $app = \App::getFacadeRoot();

        $requestData = [
            'payment' => [
                'id' => $paymentId,
                'amount' => $amount,
                'base_amount' => $baseAmount,
            ],
            'merchant' => [
                'id' => $merchantId,
            ],
            'mode' => $mode,
        ];

        if ($orderId !== null)
        {
            $requestData['order'] = [
                'id' => $orderId,
            ];
        }

        /** @var HttpResponse */
        try
        {
            $buyerProtectionEligibilityResponse = $app['checkout_service']->getBuyerProtectionEligibilityFromCheckoutService($requestData)->getOriginalContent();

            return $buyerProtectionEligibilityResponse['eligible'];
        } catch (\Exception $e) {
            $app['trace']->traceException($e, Trace::ERROR, TraceCode::BUYER_PROTECTION_ELIGIBILITY_CHECK_FAILED, $requestData);
        }

        return false;
    }

    public function getTimeoutWindow()
    {
        $gateway = $this->getGateway();

        // default is 9 mins
        $timeWindow = (new Merchant\Core)->getPaymentTimeoutWindow($this->merchant) ?? self::PAYMENT_TIMEOUT_DEFAULT_OLD;

        if ($this->merchant->isFeatureEnabled(Feature\Constants::CREATED_FLOW) === true)
        {
            // for new flow, default is 30 mins
            $timeWindow = self::PAYMENT_TIMEOUT_DEFAULT;

            if ($gateway === Payment\Gateway::BILLDESK)
            {
                $timeWindow = self::PAYMENT_TIMEOUT_BILLDESK;
            }
            else if ($this->isNetbanking() === true)
            {
                // for direct netbanking 1 hour is good enough
                $timeWindow = self::PAYMENT_TIMEOUT_NETBANKING;
            }
            else if ($this->isWallet() === true)
            {
                // for direct netbanking 1 hour is good enough
                $timeWindow = self::PAYMENT_TIMEOUT_WALLET;
            }
        }

        if ($this->isNetbanking() === true)
        {
            if ($this->getBank() === 'HDFC_C')
            {
                $timeWindow = self::PAYMENT_TIMEOUT_NETBANKING_CORP;
            }
        }
        //
        // Irrespective of the created_flow or auto refund delay,
        // if it's emandate debit payment, the timeout window
        // defined for this must always take higher preference.
        //
        if ($this->isFileBasedEmandateDebitPayment() === true)
        {
             return self::PAYMENT_TIMEOUT_FILE_BASED_DEBIT;
        }
        else if ($this->isNach() === true)
        {
            return self::PAYMENT_TIMEOUT_NACH;
        }
        else if ($this->isUpiAutoRecurring() === true)
        {
            $notificationCore = new Notification\Core();

            $notificationCount = $notificationCore->fetchNotificationCount($this->getApiOrderId());

            if($notificationCount === 0)
            {
                return self::PAYMENT_TIMEOUT_UPI_RECURRING;
            }
        }
        else if ($this->isCardMandateRecurringAutoPayment() === true)
        {
            return self::PAYMENT_TIMEOUT_CARD_RECURRING_MANDATE_WITH_AFA;
        }
        else if ((empty($gateway) === false) and
                 ($this->isEmandateRecurring() === true) and
                 (Payment\Gateway::isApiBasedAsyncEMandateGateway($gateway) === true))
        {
            return self::PAYMENT_TIMEOUT_EMANDATE_RECURRING;
        }
        else if ($this->isCardlessEmi() === true)
        {
            // for cardless emi timeout is 30 Mins
            return self::PAYMENT_TIMEOUT_CARDLESSEMI;
        }

        /**
         * Validates if the payment is upi collect
         * and is applicable to timeout
         * the payments on input collect expiry time
         */
        $timeWindow = $this->updateTimeoutWindowForUpiCollectIfApplicable($timeWindow);

        $autoRefundDelay = $this->merchant->getAutoRefundDelay();

        return min($timeWindow, $autoRefundDelay);
    }

    public function shouldRunFraudChecks()
    {
        if (($this->isCard() === false) or ($this->isGooglePayCard() === true))
        {
            return false;
        }

        //
        // Since the first auth transaction would have already been
        // done, we don't need to do any MaxMind risk checks for this.
        //
        if ($this->isSecondRecurring() === true)
        {
            return false;
        }

        return (($this->card->isInternational() === true) and
                ($this->card->isAmex() === false));
    }

    public function shouldRunShieldChecks()
    {
        //
        // Since the first auth transaction would have already been
        // done, we don't need to do any MaxMind risk checks for this.
        //
        if ($this->isSecondRecurring() === true)
        {
            return false;
        }

        return true;
    }

    public static function getFilteredDescription(string $description = null)
    {
        $filteredDescription = preg_replace('/[^a-zA-Z0-9 ]+/', '', $description);

        return $filteredDescription;
    }

    public function getAcknowledgedAt()
    {
        return $this->getAttribute(self::ACKNOWLEDGED_AT);
    }

    /**
     * Returns true if the payment success/failure has been acknowledged by the merchant.
     *
     * @return bool
     */
    public function isAcknowledged(): bool
    {
        return $this->isAttributeNotNull(self::ACKNOWLEDGED_AT);
    }

    public function getDummyPaymentArray(
        string $method,
        Base\PublicEntity $receiver = null,
        string $network = null,
        array $metadata = [],
        Order\Entity $orderEntity = null): array
    {
        $paymentArray =  [
            self::CURRENCY    => Currency\Currency::INR,
            self::METHOD      => $method,
            self::AMOUNT      => 100,
            self::DESCRIPTION => 'Dummy Payment',
            self::CONTACT     => self::DUMMY_PHONE,
            self::EMAIL       => self::DUMMY_EMAIL,
            self::RECEIVER    => $receiver,
            '_'               => $metadata,
        ];

        switch ($method)
        {
            case Method::CARD:
                $paymentArray[self::CARD] = (new Card\Entity)->getDummyCardArray($network);
                break;

            case Method::UPI:
                if (array_get($paymentArray, '_.flow') !== 'intent')
                {
                    $paymentArray[self::VPA] = self::DUMMY_VPA;

                    if(($receiver !== null) and
                        ($receiver instanceof QrV2\Entity) and
                        ($receiver->getRequestSource() === RequestSource::EZETAP))
                        {
                            $paymentArray[Payment\Entity::SOURCE_CHANNEL] = QRConstant::PAYMENT_TYPE_IN_PERSON;

                            if($receiver->merchant->isFeatureEnabled(Feature\Constants::DD_TERMINAL_UPI_QR) === true)
                            {
                                if ($receiver->getDeviceId() !== null)
                                {
                                    $paymentArray[Payment\Entity::DeviceId] = $receiver->getDeviceId();
                                }
                                else
                                {
                                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_DEVICE_ID_REQUIRED);
                                }
                            }
                        }

                    if(($receiver !== null) and
                        ($receiver instanceof QrV2\Entity) and
                        (empty($receiver->getTaxInvoice()) === false))
                    {
                        $paymentArray[self::GST_QR] = 'true';
                    }
                }
                break;

            case Method::NACH:
                $paymentArray[self::RECURRING] = true;
                break;
        }

        if($metadata[Generator::COLLECTX_VA] === true)
        {
            $paymentArray[Payment\Entity::REFERENCE14] = "collectx";
        }

        if (is_null($orderEntity) === false)
        {
            $paymentArray[Payment\Entity::AMOUNT] = $orderEntity->getAmount();

            $paymentArray[Payment\Entity::CURRENCY] = $orderEntity->getCurrency();
        }

        return $paymentArray;
    }

    // Query scopes

    /**
     * Scopes result based on morphed entity relationship.
     *
     * @param \RZP\Base\BuilderEx $query
     * @param Base\PublicEntity   $entity
     */
    public function scopeReceiver(\RZP\Base\BuilderEx $query, Base\PublicEntity $entity)
    {
        $query->where(Entity::RECEIVER_ID, '=', $entity->getId())
              ->where(Entity::RECEIVER_TYPE, '=', $entity->getEntity());
    }

    public function isCorporateNetbanking(): bool
    {
        return (
            ($this->isNetbanking() === true) and
            (Netbanking::isCorporateBank($this->getBank()) === true)
        );
    }

    public function isCorporateMakerCheckerNetbanking(): bool
    {
        return (
            ($this->isNetbanking() === true) and
            (Netbanking::isCorporateMakerCheckerBank($this->getBank()) === true)
        );
    }

    public static function getCacheUpiStatusKey(string $id): string
    {
        parent::verifyIdAndStripSign($id);

        return 'payment:upi.polling.' . $id . '.status';
    }

    /**
     * Returns cps route key
     *
     * @param  string $id payment id
     * @return string
     */
    public static function getCacheCpsRouteKey(string $id): string
    {
        parent::verifyIdAndStripSign($id);

        return 'payment:entity.' . $id . '.cps_route';
    }

    public static function getCardlessEmiOnetimeTokenCacheKey(string $token): string
    {
        return 'payment:cardlessemi.' . $token . '.token';
    }

    public function getCacheInputKey(): string
    {
        return 'payment:fallback.' . $this->getId() . '.card_number';
    }

    public function getCacheRedirectInputKey(): string
    {
        return 'payment:redirect.' . $this->getId() . '.input';
    }

    public static function getRedirectToAuthorizeTrackIdKey(string $trackId): string
    {
        return 'payment:redirect.authorize.' . $trackId . '.encrypt';
    }

    public function getPaymentResponseCacheKey(): string
    {
        return 'payment:response' . $this->getId() . '.cache';
    }

    public static function getTrackIdRequestKey(string $trackId): string
    {
        return 'track_id:request:'. $trackId . '.cache';
    }

    public static function getTrackIdResponseKey(string $trackId): string
    {
        return 'track_id:response:'. $trackId . '.cache';
    }

    public function getTransactionType()
    {
        if ($this->isRecurring() === true)
        {
            return $this->getRecurringType();
        }

        switch ( $this->getAuthType() )
        {
            case AuthType::SKIP:
                return 'MOTO';

            default:
                return 'PG';
        }
    }

    public function isMoto()
    {
        return ($this->getAuthType() === AuthType::SKIP);
    }

    /**
     * This function determines if capture settings need to be honored for Optimizer merchants overriding
     * the existing Direct settlement capture flow
     *
     * @param Payment\Entity $payment
     * @return bool
     */
    public function isOptimizerCaptureSettingsEnabled()
    {
        if ($this->isOptimizerExternalPgPayment() === true) {
            return true;
        }
        return false;
    }

    // Returns if a payment is router via Optimizer to external PGs like Payu, Cashfree, CCAvenue, Billdesk, Paytm etc
    public function isOptimizerExternalPgPayment()
    {
        if (($this->terminal != null) and
            ($this->merchant != null) and
            ($this->terminal->isOptimizer()) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::RAAS))) {
            // TODO: confirm if type optimizer is only for external gateways
            return true;
        }
        return false;
    }

    public function isDirectSettlement()
    {
        if (($this->hasTerminal() === true) and
            ($this->terminal->isDirectSettlement() === true))
        {
            return true;
        }

        if ($this->isCoD() === true)
        {
            return true;
        }

        if (($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === true)
            and ($this->getMethod() === Method::INTL_BANK_TRANSFER))
        {
            return true;
        }

        return false;
    }

    public function isDirectSettlementRefund(): bool
    {
        // If payment has terminal and is type direct settlement with refund
        // is null check handles deleted terminal cases
        if (($this->hasTerminal() === true) and
            (is_null($this->terminal) === false) and
            ($this->terminal->isDirectSettlementWithRefund() === true) and
            ($this->getTerminalId() !== 'B2K2t8JD9z98vh'))
            // This terminal was deleted due to Yesbank moratorium
            // This particular terminal is not a direct settlement terminal
            // Will be removing this check once the terminal is fixed.
            //
            // Slack thread for reference:
            // https://razorpay.slack.com/archives/CA66F3ACS/p1584100168218900?thread_ts=1584090894.210900&cid=CA66F3ACS
            //
        {
            return true;
        }

        return false;
    }

    public function isReconciled()
    {
        if (($this->hasTransaction() === true) and
            ($this->transaction->isReconciled() === true))
        {
            return true;
        }

        return false;
    }

    public function isCardMandateCreateApplicable(): bool
    {
        $token = $this->localToken;

        if (($this->isCardRecurring() === true) and
            (($this->isRecurringTypeInitial() === true) or $this->isRecurringTypeCardChange() === true) and
            (empty($token) === false) and
            (empty($token->getCardMandateId()) === true) and
            ($this->card->iinRelation !== null) and
            ($this->card->iinRelation->isCardMandateApplicable($this->merchant) === true))
        {
            return true;
        }

        return false;
    }

    public function isCardMandateNotificationCreateApplicable(): bool
    {
        return (($this->isCardMandateRecurringAutoPayment() === true)
            and ($this->cardMandateNotification === null));
    }

    public function isCardMandateRecurringAutoPayment(): bool
    {
        $token = $this->localToken;

        if (($this->isCardAutoRecurring()) and
            (empty($token) === false) and
            ($token->hasCardMandate() === true))
        {
            return true;
        }

        return false;
    }

    public function isCardMandateRecurringInitialPayment(): bool
    {
        $token = $this->localToken;

        if (($this->isCardRecurring() === true) and
            (($this->isRecurringTypeInitial() === true) or ($this->isRecurringTypeCardChange() === true)) and
            (empty($token) === false) and
            ($token->hasCardMandate() === true))
        {
            return true;
        }

        return false;
    }

    public function isTokenisationUnhappyFlowHandlingApplicable(): bool
    {
        try
        {
            if ($this->isCardMandateRecurringInitialPayment() === true) {
                $app = \App::getFacadeRoot();

                $key = Carbon::now()->getTimestamp();

                $variant = $app['razorx']->getTreatment($key,
                    RazorxTreatment::RECURRING_TOKENISATION_UNHAPPY_FLOW_HANDLING,
                    $app['rzp.mode']);

                return (strtolower($variant) === 'on');
            }
        }
        catch (\Exception $e)
        {
            $app = \App::getFacadeRoot();

            $app['trace']->traceException(
                $e,
                null,
                TraceCode::RECURRING_PAYMENT_RAZORX_FAILURE
            );
        }

        return false;
    }

    public function hasCardMandateNotification(): bool
    {
        return $this->cardMandateNotification !== null;
    }

    public function isRequiredToCreateNewTokenAlways($token = null, $isPreferredRecurring = false): bool
    {
        // for auto payment, card mandate notification will be present
        if ($this->cardMandateNotification !== null)
        {
            return false;
        }

        $card = $this->card;

        if (($card === null) and ($token !== null)) {
            $card = $token->card;
        }

        if ((($this->isCardRecurring() === true or ($this->isCard() and $isPreferredRecurring))) and
            ($card !== null) and
            ($card->iinRelation !== null) and
            ($card->iinRelation->isCardMandateApplicable($this->merchant) === true))
        {
            return true;
        }

        // Need to create new token in case of card change as there are some cases where expiry date of card changed but
        // existing token is picked as the comparison is just based on vault token value (Non IN Merchants specifically)
        if (($this->isCardRecurring() === true) and
            ($this->getRecurringType() === RecurringType::CARD_CHANGE) and
            ($card->iinRelation !== null))
        {
            return true;
        }

        return false;
    }

    public function toArrayPublicCustomer(bool $populateMessages = false): array
    {
        $data = parent::toArrayPublicCustomer();

        if ($populateMessages === true)
        {
            $transactionTrackerMessages = new Payment\Refund\TransactionTrackerMessages();

            $autoRefundDelayDate = Carbon::createFromTimestamp($this->getAuthorizeTimestamp() + $this->merchant->getAutoRefundDelay(), Timezone::IST);

            $data[Refund\Constants::MERCHANT_ID] = $this->merchant->getPublicId();

            $data[Refund\Constants::MERCHANT_NAME] = $this->merchant->getBillingLabel();

            $data[Payment\Entity::CAPTURED_AT] = $this->getCapturedAt();

            if (isset($this->order))
            {
                $data[Payment\Entity::ORDER_ID] = $this->order->getPublicId();
            }

            $data[Refund\Constants::PRIMARY_MESSAGE] =
                $this->getMessageForTransactionTracker(
                    $transactionTrackerMessages,
                    $autoRefundDelayDate,
                    TransactionTrackerMessages::PRIMARY
                );

            $data[Refund\Constants::SECONDARY_MESSAGE] =
                $this->getMessageForTransactionTracker(
                    $transactionTrackerMessages,
                    $autoRefundDelayDate,
                    TransactionTrackerMessages::SECONDARY
                );

            $data[Refund\Constants::TERTIARY_MESSAGE] =
                $this->getMessageForTransactionTracker(
                    $transactionTrackerMessages,
                    $autoRefundDelayDate,
                    TransactionTrackerMessages::TERTIARY
                );

            $data[Refund\Constants::LATE_AUTH] = $this->isLateAuthorized();
        }

        $data[self::STATUS] = $this->getCurrentPaymentStatus();

        return $data;
    }

    /**
     * Get the collection of items as a plain array.
     * @return array
     * @throws LogicException
     * This method has been depricated, and we have moved the logic to pg-sdk.
     * If changing in toArrayPublic() for Payment, please make sure to connect with pg-router team
     */
    public function toArrayPublic()
    {
        $data = parent::toArrayPublic();

        $merchantCore = new Merchant\Core();

        if ($merchantCore->isShowLateAuthAttributeFeatureEnabled($this->merchant) === true)
        {
            $data[self::AUTHORIZED_AT] = $this->getAuthorizeTimestamp();
            $data[self::AUTO_CAPTURED] = $this->getAutoCaptured();
            $data[self::CAPTURED_AT] = $this->getCapturedAt();

            if(isset($data[self::CAPTURED_AT]) === false )
            {
                $data[self::AUTO_CAPTURED] = null;
            }

            $data[self::LATE_AUTHORIZED] = $this->isLateAuthorized();
        }

        if ($merchantCore->isShowReceiverTypeFeatureEnabled($this->merchant) === true)
        {
            $data[self::RECEIVER_TYPE] = $this->getReceiverType();
        }

        if ($this->merchant->isOmniEnabled() === true)
        {
            $data[Payment\Constant::SOURCE_CHANNEL] = $this->getSourceChannel();
            $this->setDeviceAttributesForDashboard($data);
        }

        if($this->isB2BExportCurrencyCloudPayment() === true){
            $data[self::B2BExportInvoice] = $this->getReference2();
        }

        $this->setUpiIfApplicable($data);

        // MCC CFB Payments which are in authorized state will have the fees in payment currency,
        // so we are converting it into base currency(INR) and sending it as an additional param to Merchant Dashboard.
        // If its a captured payment, then it would have already been handled in the post capture to make sure fees are stored in Base currency(INR)

        if($this->getCurrency()!== Currency\Currency::INR
            and $this->isFeeBearerCustomer()
            and $this->isCaptured() === false
            and !$this->isStatus(Status::FAILED)
            and ($this->merchant->isCustomerFeeBearerAllowedOnInternational()
            or $this->merchant->isLRSFlowEnabled()))
        {
            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($this->getId());

            if(isset($paymentMeta)) {
                if ($this->merchant->isLRSFlowEnabled() and !empty($paymentMeta->getForexRate()))
                {
                    $fee = (float)$this->getFee()*($paymentMeta->getForexRate());
                    $data[self::feeCurrencyAmount] = (int)ceil($fee);
                }
                else if (!empty($paymentMeta->getMccForexRate())) {
                    $fee = (float)$this->getFee()*($paymentMeta->getMccForexRate());
                    $data[self::feeCurrencyAmount] = (int)ceil($fee);
                }
            }
        }
        else if ($this->isCaptured()
            and $this->getCurrency()!== Currency\Currency::INR
            and $this->isFeeBearerCustomer())
        {
            if ($this->merchant->isFeatureEnabled(Features::PG_LEDGER_REVERSE_SHADOW) === true ) {
                $data[self::FEE] = $this->getFee();
            } else {
                $data[self::FEE] = $this->transaction->getFee();
            }
        }

        $this->maskSensitiveFieldsIfApplicable($data);

        return $data;
    }

    /**
     * Get the collection of items as a plain array.
     * @return array
     * @throws LogicException
     */
    public function toArrayPublicWithExpand()
    {
        $data = parent::toArrayPublicWithExpand();

        $app = \App::getFacadeRoot();

        $merchantCore = new Merchant\Core();

        if ($merchantCore->isShowLateAuthAttributeFeatureEnabled($this->merchant) === true)
        {
            $data[self::AUTHORIZED_AT] = $this->getAuthorizeTimestamp();
            $data[self::AUTO_CAPTURED] = $this->getAutoCaptured();
            $data[self::CAPTURED_AT] = $this->getCapturedAt();

            if(isset($data[self::CAPTURED_AT]) === false )
            {
                $data[self::AUTO_CAPTURED] = null;
            }

            $data[self::LATE_AUTHORIZED] = $this->isLateAuthorized();
        }

        if ($merchantCore->isShowReceiverTypeFeatureEnabled($this->merchant) === true)
        {
            $data[self::RECEIVER_TYPE] = $this->getReceiverType();
        }

        if ($this->merchant->isOmniEnabled() === true)
        {
            $data[Payment\Constant::SOURCE_CHANNEL] = $this->getSourceChannel();
            $this->setDeviceAttributesForDashboard($data);
        }

        if($this->isB2BExportCurrencyCloudPayment() === true){
            $data[self::B2BExportInvoice] = $this->getReference2();
        }

        $this->setUpiIfApplicable($data);

        // MCC CFB Payments which are in authorized state will have the fees in payment currency,
        // so we are converting it into base currency(INR) and sending it as an additional param to Merchant Dashboard.
        // If its a captured payment, then it would have already been handled in the post capture to make sure fees are stored in Base currency(INR)
        if($this->getCurrency()!== Currency\Currency::INR
            and $this->isFeeBearerCustomer()
            and $this->isCaptured() === false
            and !$this->isStatus(Status::FAILED)
            and ($this->merchant->isCustomerFeeBearerAllowedOnInternational()
            or $this->merchant->isLRSFlowEnabled()))
        {
            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($this->getId());

            if(isset($paymentMeta)) {
                if ($this->merchant->isLRSFlowEnabled() and !empty($paymentMeta->getForexRate()))
                {
                    $fee = (float)$this->getFee()*($paymentMeta->getForexRate());
                    $data[self::feeCurrencyAmount] = (int)ceil($fee);
                }
                else if (!empty($paymentMeta->getMccForexRate())) {
                    $fee = (float)$this->getFee()*$paymentMeta->getMccForexRate();
                    $data[self::feeCurrencyAmount] = (int)ceil($fee);
                }
            }
        }
        else if ($this->isCaptured()
            and $this->getCurrency()!== Currency\Currency::INR
            and $this->isFeeBearerCustomer())
        {

            if ($this->merchant->isFeatureEnabled(Features::PG_LEDGER_REVERSE_SHADOW) === true)
            {
                $txn = (new Transaction\Repository())->findByEntityIdWithoutMerchantTidb($this->getId());
                $data[self::FEE] = $txn->getFee();
            }
            else
            {
                $data[self::FEE] = $this->transaction->getFee();
            }
        }

        // This is to populate rrn from cps authorization table for hdfc gateway as we were storing incorrect rrn in api table from MIS file. This check has to be removed after the database is fixed ,otherwise increases latency
        if($this->getGateway() == self::HDFC)
        {
            $paymentId = $this->getId();

            $request = [
                'fields'        => [self::RRN],
                'payment_ids'   => [$paymentId],
            ];

            $response = $app['card.payments']->fetchAuthorizationData($request);

            $data[self::ACQUIRER_DATA][self::RRN] = $response[$paymentId][self::RRN];
        }

        $this->setConvenienceFeeAttributesForDashboard($data);

        return $data;
    }

    /**
     * Get the collection of items as a plain array.
     * @return array
     * @throws LogicException
     */
    public function toArrayWebhook()
    {
        $data = parent::toArrayWebhook();

        if ((($this->getCurrency() === Currency\Currency::INR) and
            ($this->getStatus() === Status::FAILED)) OR
            ($this->getStatus() === Status::AUTHORIZED))
        {
            unset($data[self::BASE_AMOUNT]);
        }

        if ($this->getStatus() === Status::FAILED)
        {
            unset($data[self::AMOUNT_TRANSFERRED]);
        }

        if ($this->getStatus() === Status::CAPTURED)
        {
            $data[self::BASE_AMOUNT] = $this->getBaseAmount();
        }

        $this->setUpiIfApplicable($data, Constants::WEBHOOK);

        return $data;
    }

    public function setConvenienceFeeAttributesForDashboard(array & $data)
    {
        $app = \App::getFacadeRoot();

        if( ($app['basicauth']->isAdminAuth() === true or
            $app['basicauth']->isProxyAuth() === true) and
            $this->getConvenienceFee() !== null and
            $this->getConvenienceFee() > 0)
        {
            $data['customer_fee'] = $this->getConvenienceFee();

            $data['customer_fee_gst'] = $this->getConvenienceFeeGst();
        }
    }

    public function setDeviceAttributesForDashboard(array & $data)
    {
        $app = \App::getFacadeRoot();

        if( ($app['basicauth']->isAdminAuth() === true or
                $app['basicauth']->isProxyAuth() === true))
        {
            $data[self::GatewayTerminalId] = $this->getGatewayTerminalId();
            $data[self::GatewayMerchantId] = $this->getGatewayMerchantId();
            $data[self::DeviceId] = $this->getDeviceId();
            $data[self::InternalStatus] = $this->getInternalStatus();
        }
    }

    /**
     * Set UPI block to response if applicable.
     * @param array $data
     * @param string $action
     * @return void
     */
    private function setUpiIfApplicable(array &$data, string $action = null)
    {
        if ($this->getMethod() !== Payment\Method::UPI)
        {
            return;
        }

        if ($this->getReference2() !== null)
        {
            $data[self::UPI][self::PAYER_ACCOUNT_TYPE] = $this->getReference2();
        }

        $data[self::UPI][self::VPA] = $this->getVpa();

        $this->assignUPIFlowAndErrorDetails($data, $action);
    }

    private function assignUPIFlowAndErrorDetails(array &$data, $action)
    {
        $app = App::getFacadeRoot();

        /*
         * Adding if block to return upi flow attribute for the Merchant and Admin dashboard
         * So, we are adding basic auth check on querying the upi_metadata table.
         * This helps avoiding querying to upi_metadata table in public auths on calling payment->toArrayPublic
         */
        if ($app['basicauth']->isProxyAuth() === true
            or $app['basicauth']->isAdminAuth() === true
            or $app['basicauth']->isPrivateAuth() === true
            or $action === Constants::WEBHOOK)
        {
            $upiMetadata = $this->getUpiMetadata();

            if (isset($upiMetadata) === false or $upiMetadata->getFlow() !== Flow::IN_APP)
            {
                return;
            }

            $data[self::UPI][UpiMetadata\Entity::FLOW] = $upiMetadata->getFlow();
            $reference17 = json_decode($this->getReference17(), true) ?? [];

            if ((empty($reference17['payer']) === true) or
                ($reference17['payer']['primary'] === false))
            {
                return;
            }

            $data[self::ERROR_REASON]      = $reference17['payer'][self::ERROR_REASON];
            $data[self::ERROR_CODE]        = $reference17['payer'][\RZP\Models\Upi\Turbo\Constants::PUBLIC_ERROR_CODE];
            $data[self::ERROR_DESCRIPTION] = $reference17['payer'][Error::ERROR_DESCRIPTION];
            $data[self::ERROR_SOURCE]      = $reference17['payer'][self::ERROR_SOURCE] ?? '';
            $data[self::ERROR_STEP]        = $reference17['payer'][self::ERROR_STEP] ?? '';

            if ($app['basicauth']->isAdminAuth() === true)
            {
                $data[self::INTERNAL_ERROR_CODE] = $reference17['payer'][self::INTERNAL_ERROR_CODE];
            }
        }
    }

    /**
     * @param TransactionTrackerMessages $transactionTrackerMessages
     * @param Carbon $expectedDate
     * @param $messageType
     * @return string
     */
    private function getMessageForTransactionTracker(TransactionTrackerMessages $transactionTrackerMessages, Carbon $expectedDate, $messageType): string
    {
        $messageSlaDone = null;
        $messageVoidRefund = null;
        $messageEntity = Refund\Constants::PAYMENT;
        $messageStatus = $this->getCurrentPaymentStatus();

        $messageLateAuth = ($this->isLateAuthorized() === true);

        $message = $transactionTrackerMessages->getMessage($messageEntity, $messageStatus, $messageType, $messageSlaDone, $messageLateAuth, $messageVoidRefund);

        return $this->populateTransactionTrackerMessages($message, $expectedDate);
    }

    /**
     * @param $message
     * @param Carbon $expectedDate
     * @return mixed
     */
    private function populateTransactionTrackerMessages ($message, Carbon $expectedDate)
    {
        $populatedMessage = $message;

        $messageAutoRefundDelayDays = (int) (ceil($this->merchant->getAutoRefundDelay() / 86400));

        $replacer = [
            TransactionTrackerMessages::MESSAGE_AMOUNT                 => $this->getFormattedAmount(),
            TransactionTrackerMessages::MESSAGE_MERCHANT_NAME          => $this->merchant->getBillingLabel(),
            TransactionTrackerMessages::MESSAGE_AUTO_REFUND_DELAY_DATE => $expectedDate->toFormattedDateString(),
            TransactionTrackerMessages::MESSAGE_AUTO_REFUND_DELAY_DAYS => $messageAutoRefundDelayDays,
        ];

        foreach ($replacer as $key => $value)
        {
            $populatedMessage = str_replace($key, $value, $populatedMessage);
        }

        return $populatedMessage;
    }

    public function getGatewayAmount()
    {
        $paymentMetaEntity = $this->paymentMeta;

        // If the entity has a reward we should deduct the reward amount
        // from the payment amount
        if ($this->hasReward())
        {
            return ($this->getAmount()-$this->reward->getRewardAmount());
        }

        return (($paymentMetaEntity !== null) and
                ($paymentMetaEntity->getGatewayAmount() !== null) and
                ($paymentMetaEntity->getGatewayAmount() !== 0)) ?
                $paymentMetaEntity->getGatewayAmount() : $this->getAmount();
    }

    public function getGatewayCurrency()
    {
        $paymentMetaEntity = $this->paymentMeta;

        return (($paymentMetaEntity !== null) and
                ($paymentMetaEntity->getGatewayCurrency() !== null) and
                (empty($paymentMetaEntity->getGatewayCurrency()) === false)) ?
                $paymentMetaEntity->getGatewayCurrency() : $this->getCurrency();
    }

    public function isDCC()
    {
        $paymentMetaEntity = $this->paymentMeta;

        if ($paymentMetaEntity === null)
        {
            return false;
        }

        if (($paymentMetaEntity->getGatewayCurrency() === null) or
            ($paymentMetaEntity->getGatewayAmount() === null))
        {
            return false;
        }

        return (($paymentMetaEntity->getGatewayCurrency() !== $this->getCurrency()) or
            ($paymentMetaEntity->getGatewayAmount() !== $this->getAmount()));
    }

    public function isReconAmountMismatched()
    {
        $paymentMetaEntity = $this->paymentMeta;

        if ($paymentMetaEntity === null)
        {
            return false;
        }

        if (($paymentMetaEntity->getMismatchAmount() === null) or
            ($paymentMetaEntity->getMismatchAmountReason() === null))
        {
            return false;
        }

        return true;
    }

    public function isHdfcVasDSCustomerFeeBearerSurcharge()
    {
        if ($this->isCard() === false || Gateway::isHdfcPosGateway($this->getGateway()) )
        {
            return false;
        }

        if ($this->merchant->org->isFeatureEnabled(Feature\Constants::ORG_HDFC_VAS_CARDS_SURCHARGE) === false)
        {
            return false;
        }

        $network = $this->card->getNetwork();

        $validNetworks = [
         Network::getFullName(Network::VISA),
         Network::getFullName(Network::MC),
         Network::getFullName(Network::RUPAY),
         Network::getFullName(Network::DICL),
        ];

        $app = \App::getFacadeRoot();

        $app['trace']->debug(TraceCode::HDFC_VAS_RAZORX_RESULT, [
            'merchantId' => $this->merchant->getId(),
            'paymentId' => $this->getId(),
            'razorXResult' => 'on',
        ]);

        if ((in_array($network, $validNetworks, true) === true) and
             ($this->isFeeBearerCustomer() === true) and
             ($this->isDirectSettlement() === true))
       {
          return true;
       }

        return false;
    }

    public function isHdfcNonDSSurcharge()
    {
        if ($this->isCard() === false)
        {
            return false;
        }

        if ($this->merchant->org->isFeatureEnabled(Feature\Constants::ORG_HDFC_VAS_CARDS_SURCHARGE) === false)
        {
            return false;
        }

        if($this->getGateway() !== Gateway::HDFC)
        {
            return false;
        }

        if($this->card->isInternational() === true)
        {
            return false;
        }

        if($this->getCurrency() !== Currency\Currency::INR)
        {
            return false;
        }

        $network = $this->card->getNetwork();

        $validNetworks = [
            Network::getFullName(Network::VISA),
            Network::getFullName(Network::MC),
            Network::getFullName(Network::RUPAY),
            Network::getFullName(Network::DICL),
        ];

        $app = \App::getFacadeRoot();

        $experimentResult = $app['razorx']->getTreatment($this->merchant->getId(),
            'hdfc_vas_surcharge_2', $app['rzp.mode']);

        $app['trace']->debug(TraceCode::HDFC_VAS_RAZORX_RESULT, [
            'merchantId' => $this->merchant->getId(),
            'paymentId' => $this->getId(),
            'razorXResult' => $experimentResult,
        ]);

        if (
            (in_array($network, $validNetworks, true) === true) and
            ($this->isFeeBearerCustomer() === true) and
            ($this->isDirectSettlement() === false) and
            ($experimentResult === 'on'))
        {
            return true;
        }

        return false;
    }

    public function isUpiAndAmountMismatched()
    {
        return (($this->isReconAmountMismatched() === true) and
                ($this->getMethod() === Method::UPI) and
                ($this->paymentMeta->getGatewayAmount() > 0));
    }

    public function getCurrencyConversionFee($baseAmount, $rate, $markUpPercent, $denominationFactor)
    {
        $fee = (($baseAmount * $rate * $markUpPercent * $denominationFactor) / 100);
        return (int) ceil($fee);
    }

    public function getCurrentPaymentStatus()
    {
        $currentPaymentStatus = $this->getStatus();

        if (($currentPaymentStatus === Payment\Status::FAILED) and
            ($this->getVerifyAt() !== null) and
            (($this->getVerifyBucket() !== null) and ($this->getVerifyBucket() < 9)))
        {
            $currentPaymentStatus = 'pending';
        }

        return $currentPaymentStatus;
    }

    public function fetchBillingAddress()
    {
        $billingAddress = $this->fetchBillingAddressFromPayment();

        if ($billingAddress === null)
        {
            $billingAddress = $this->fetchBillingAddressFromCustomerToken();
        }

        return $billingAddress;
    }

    /**
     * @return mixed
     */
    public function fetchBillingAddressFromCustomerToken()
    {
        $tokenEntity = $this->getGlobalOrLocalTokenEntity();

        if($tokenEntity !== null)
        {
            return $tokenEntity->getBillingAddress();
        }
        return null;
    }

    public function fetchBillingAddressFromPayment()
    {
        $app = \App::getFacadeRoot();

        return $app['repo']->address->fetchPrimaryAddressOfEntityOfType($this, Type::BILLING_ADDRESS);
    }

    public function isAVSSupportedForPayment(): bool
    {
        if(($this->isCard() === true) and ($this->hasCard() === true))
        {
            if ((empty($this->card->iinRelation) === false )
                && (empty($this->card->iinRelation->getCountry()) === false)
                && ($this->card->iinRelation->getCountry() !== null))
            {
                return ( ($this->merchant->isAVSEnabledInternationalMerchant())
                    && ($this->card->iinRelation->isAVSSupportedIIN()) );
            }
        }
        return false;
    }

    /**
     * Checks for currency and amount authorized at gateway and if amount is different it will
     * check difference is allowed for this given payment.
     * @param string $currency
     * @param int $amountAuthorized
     * @return bool
     */
    public function shouldAllowGatewayAmountMismatch(string $currency, int $amountAuthorized): bool
    {
        // Currency check is mandatory as it could lead to mismatch for international payments
        if  ($this->getCurrency() !== $currency)
        {
            return false;
        }

        $diff = ($amountAuthorized - $this->getAmount());

        //It will not allow amount difference, if there is no difference at all
        $allowSurplus = (($diff > 0) and ($this->shouldAllowGatewayAmountSurplus($amountAuthorized)));
        $allowDeficit = (($diff < 0) and ($this->shouldAllowGatewayAmountDeficit($amountAuthorized)));

        return ($allowSurplus or $allowDeficit);
    }

    public function fetchInternationalFromInput(array $input): bool
    {
        $method = $input['method'];

        if ($method === Payment\Method::BANK_TRANSFER) {
            $isPacbPartnerFlow = (new \RZP\Models\Partner\Service())->isPaCbFeatureEnabledForPartner();
            if ($isPacbPartnerFlow === true) {
                return true;
            }
            return false;
        }

        if ($method === Payment\Method::CARD and
            isset($input[Payment\Entity::CARD]) and
            isset($input[Payment\Entity::CARD][Card\Entity::NUMBER]))
        {
            $app = \App::getFacadeRoot();
            $iinId = substr($input[Payment\Entity::CARD][Card\Entity::NUMBER], 0, 6);

            $iin = $app['repo']->iin->find($iinId);

            // IIN not available
            if (empty($iin) === true)
            {
                return false;
            }

            if (IIN\IIN::isInternational($iin->getCountry(), $this->merchant->getCountry()) === true)
            {
                return true;
            }
        }
        else if(($method === Payment\Method::WALLET) and
            ($input['wallet'] === Wallet::PAYPAL))
        {
            return true;
        }
        else if(($method === Payment\Method::APP) and
            (isset($input['provider']) === true)  and
            (in_array($input['provider'], Payment\Gateway::INTERNATIONAL_ENABLED_APPS) === true))
        {
            return true;
        }

        return false;
    }

    public function shouldAllowGatewayAmountSurplus(int $amountAuthorized): bool
    {
        $allowedMerchantsForSurplus = config()->get('app.amount_difference_allowed_authorized');

        return in_array($this->getMerchantId(), $allowedMerchantsForSurplus, true);
    }

    public function shouldAllowGatewayAmountDeficit(int $amountAuthorized): bool
    {
        $allowedMerchantsForDeficit = config()->get('app.amount_difference_allowed_authorized');

        return in_array($this->getMerchantId(), $allowedMerchantsForDeficit, true);
    }
    /*
   * Fetch methods supported for a payment
   * For Gpay, we fetch all Gpay supported methods
   * For non Gpay payments, simply push the method field
   * to an array and return

    * @return array of methods
   *
   */
    public function fetchPaymentMethods()
    {
        $paymentMethods = [];

        if ($this->isGooglePay())
        {
            $paymentMethods = $this->getGooglePayMethods();
        }

        else
        {
            array_push($paymentMethods, $this->getMethod());
        }

        return $paymentMethods;
    }

    public function getDiscountRatioIfApplicable()
    {
        $discountRatio = 0.0;

        $discount = $this->getDiscountIfApplicable();

        if ($discount !== null) {
            $discountRatio = $discount / $this->getBaseAmount();
        }

        return $discountRatio;
    }

    public function getDiscountedAmountIfApplicable()
    {
        $amount = $this->getBaseAmount();

        $discountRatio = $this->getDiscountRatioIfApplicable();

        return ($amount - (int)(round($amount * $discountRatio)));
    }

    public function isMethodlessGooglePay()
    {
        return ($this->getMethod() === Payment\Method::UNSELECTED) and
            ($this->isAuthenticationGatewayGooglePay());
    }

    public function isPostMethodGooglePay()
    {
        return $this->isAuthenticationGatewayGooglePay() and
            in_array($this->getMethod(), Payment\Method::getPostAuthorizeGooglePayMethods(), true);
    }

    public function updateGooglePayPaymentMethodIfApplicable($methodToUpdate)
    {
        if ($this->getIsGooglePayMethodChangeApplicable() !== true)
        {
            return;
        }

        $oldPaymentMethod = $this->getMethod();

        $this->setMethod($methodToUpdate);

        $this->setApplication(self::GOOGLE_PAY);

        $this->saveOrFail();

        $app = \App::getFacadeRoot();
        $app['trace']->info(TraceCode::GOOGLEPAY_PAYMENT_METHOD_UPDATE,[
            'payment_id'                => $this->getPublicId(),
            'old_payment_method'        => $oldPaymentMethod,
            'updated_payment_method'    => $this->getMethod(),
        ]);
    }

    public function setConvenienceFee(int $fee)
    {
        $this->setAttribute(self::CONVENIENCE_FEE, $fee);
    }

    public function setConvenienceFeeGst(int $gst)
    {
        $this->setAttribute(self::CONVENIENCE_FEE_GST, $gst);
    }

    public function getConvenienceFee()
    {
        return $this->getAttribute(self::CONVENIENCE_FEE);
    }

    public function getConvenienceFeeGst()
    {
        return $this->getAttribute(self::CONVENIENCE_FEE_GST);
    }

    // For payment in case of and dynamic fee bearer, where customer fee is not settled
    // We have to make sure that customer fee and customer fee gst is removed from merchant balance and
    // merchant receivable amount, hence this function.
    public function getCustomerFeeAndCustomerFeeGst()
    {
        if( $this->hasOrder() === true and
            $this->order->getFeeConfigId() !== null )
        {

            $customerFee = $this->getConvenienceFee();

            $customerTax = $this->getConvenienceFeeGst();

            return [$customerFee, $customerTax];
        }

        return [0,0];
    }

    public function getAmountWithoutConvenienceFeeIfApplicable(int $amount, Order\Entity $order)
    {
        if($order->getFeeConfigId() !== null and
            $this->getConvenienceFee() !== null)
        {
            return $amount - $this->getConvenienceFee() - $this->getConvenienceFeeGst();
        }
        return $amount;
    }

    public function getBaseAmountForFeeCalculation($amount)
    {
        if($this->getConvenienceFee() !== null)
        {
            return $amount - $this->getConvenienceFee() - $this->getConvenienceFeeGst();
        }
        return $amount;
    }

    public function isInternationalGateway($gateway)
    {
        return (in_array($gateway, Payment\Gateway::$internationalGateways, true) === true);
    }

    public function isMethodInternationalApp()
    {
        return ($this->isMethod(Payment\Method::APP)) and
            ($this->isInternationalGateway($this->getWallet()));
    }

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

    public function isUnintendedPayment()
    {
        $reason = $this->getCancellationReason();

        return in_array($reason, $this->unintendedPaymentCancellationReasons);
    }

    public function getCancellationReason()
    {
        return $this->getAttribute(self::CANCELLATION_REASON);
    }

    public function isPaymentCompletedOrCOD(): bool
    {
        $method = $this->getMethod();

        $status = $this->getStatus();

        return ((($method === Payment\Method::COD) and
            ($status === Payment\Status::PENDING)) or
            (($method !== Payment\Method::COD) and
            (in_array($status, [Payment\Status::CAPTURED, Payment\Status::AUTHORIZED]) === true)));
    }

    public function isB2BExportCurrencyCloudPayment(){

        $method = $this->getMethod();
        $gateway = $this->getGateway();

        if($method == Payment\Method::INTL_BANK_TRANSFER && $gateway == Gateway::CURRENCY_CLOUD){
            return true;
        }

        return false;
    }

    public function getFormattedCreatedAtWithTimeZone(){
        $timeZone = $this->merchant->getTimeZone();
        return Carbon::createFromTimestamp($this->getCreatedAt(), $timeZone)->format('dS M, Y H:i:s A ')  . Timezone::getTimeZoneAbbrevation($timeZone);
    }

    public function shouldCreateDCCEInvoice()
    {
        if ($this->getGateway() != Gateway::HITACHI)
        {
            return false;
        }
        return true;
    }

    // Return fee in payment currency
    // Slack: https://razorpay.slack.com/archives/C7WEGELHJ/p1677061101772369?thread_ts=1675832734.858449&cid=C7WEGELHJ
    public function getFeeInMcc()
    {
        $fee = 0;

        if ($this->getCurrency() === Currency\Currency::INR)
        {
            return $fee;
        }
        // return the fees as it is if the payment is yet in authorized state because
        // in this case the fees are already in payment currency
        if ($this->isAuthorized()) {
            return $this->getFee();
        }

        $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($this->getId());

        if ((isset($paymentMeta) === true) and
            (empty($paymentMeta->getMccForexRate()) === false))
        {
            $fee = (float)$this->getFee() / $paymentMeta->getMccForexRate();
        }

        return (int)ceil($fee);
    }

    public function getMccMarkDownCommisionAmount()
    {
        $commissionAmount = 0;
        $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($this->getId());
        if ($paymentMeta->getMccApplied())
        {
            $denominationFactorToCurrency = Currency\Currency::DENOMINATION_FACTOR[Currency\Currency::INR];

            $denominationFactorFromCurrency = Currency\Currency::DENOMINATION_FACTOR[$this->getCurrency()];

            $denominationFactor = $denominationFactorToCurrency / $denominationFactorFromCurrency;

            $commissionAmount = ($this->getAmount() * $paymentMeta->getMccForexRate() * $paymentMeta->getMccMarkDownPercent() * $denominationFactor)/100;
        }
        return $commissionAmount;
    }

    public function modifyInput(& $input)
    {
        foreach ($this->public as $key)
        {
            if (isset($input[$key]) === false)
            {
                $input[$key] = null;
            }
        }
    }

    public function isEligibleForFeeModelOverride(): bool {
        return
            (
                ($this->merchant !== null) and
                (in_array($this->merchant->getId(),self::FEE_MODEL_OVERRIDE_MERCHANT_IDS) === true) and
                (empty($this->transaction) === false)
            );
    }

    public function getProvider()
    {
        return $this->getAttribute(self::PROVIDER);
    }

    /**
     * @return bool
     */
    public function isSodexoPayment(): bool
    {
        return $this->isCard() && $this->getProvider() === self::SODEXO;
    }

    public function isOptimizerWalletLinkAndPaySupported($gatewayInput=null): bool
    {
        if($gatewayInput!=null)
        {
            if((isset($gatewayInput['gateway'][PaymentConstants::OPTIMIZER_AUTO_DEBIT_WALLET]) === true and $gatewayInput['gateway'][PaymentConstants::OPTIMIZER_AUTO_DEBIT_WALLET] === true))
            {
                return true;
            }

            return false;
        }

        if($this->hasTerminal() === true && $this->isWallet() && in_array($this->getGateway(),Wallet::$optimizerWalletAutoDebitGateways) && $this->merchant->isFeatureEnabled(Features::RAAS) === true && $this->merchant->isFeatureEnabled(Features::WALLET_PAYTM_AUTO_DEBIT) === true)
        {
            $terminalTypeArray = $this->terminal->getType();

            if(($terminalTypeArray !== null) && (in_array(\RZP\Models\Terminal\Type::ENABLE_AUTO_DEBIT, $terminalTypeArray) === true) && (in_array(\RZP\Models\Terminal\Type::OPTIMIZER, $terminalTypeArray) === true))
            {
                return true;
            }
        }

        return false;
    }

    /** This checks if the flow is collect and updates the
     *  timeout on collect expiry time sent in the request
     * @param $timeWindow
     * @return int|mixed
     */
    public function updateTimeoutWindowForUpiCollectIfApplicable($timeWindow)
    {
        if (($this->isUpi() === false) or
            ($this->isUpiQr() === true) or
            ($this->isUpiTransfer() === true) or
            ($this->isBharatQr() === true) or
            ($this->isUpiRecurring() === true) or
            ($this->isUpiAutoRecurring() === true))
        {
            return $timeWindow;
        }

        $app = \App::getFacadeRoot();

        $upiMetadata = $this->fetchUpiMetadata();

        if ((empty($upiMetadata) === true) or
            ($upiMetadata instanceof UpiMetadata\Entity === false))
        {
            $app['trace']->info(TraceCode::PAYMENT_UPI_METADATA_NOT_FOUND,
                [
                    'payment_id'  => $this->getId(),
                    'merchant_id' => $this->getMerchantId(),
                ]);

            return $timeWindow;
        }

        if ($this->isUpiOtmPayment($upiMetadata) === true)
        {
            return $timeWindow;
        }

        $isUpiCollect = false;

        if (empty($upiMetadata->getFlow()) === false)
        {
            $isUpiCollect = ($upiMetadata->getFlow() === Flow::COLLECT);
        }

        if ($isUpiCollect === false)
        {
            return $timeWindow;
        }

        if (($this->isUpiCollectExpiryDisabled() === true) or
            ($this->isMerchantDisabledOnCollectExpiry($this->getMerchantId()) === true))
        {
            return $timeWindow;
        }

        $expiryWindow = $timeWindow;

        if (empty($upiMetadata->getExpiryTime()) === false)
        {
            $expiryWindow = $upiMetadata->getExpiryTime() * 60;
        }

        $app['trace']->info(TraceCode::PAYMENT_UPI_COLLECT_EXPIRY, [
            'payment_id'  => $this->getId(),
            'expiry_time' => $expiryWindow,
        ]);

        return min($expiryWindow, self::PAYMENT_UPI_COLLECT_MAX_EXPIRY_WINDOW);
    }

    public function isNewSplitPaymentFlow()
    {
        $app = \App::getFacadeRoot();
        try
        {
            $merchantId = $this->getMerchantId();
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get('app.split_payment_flow_new'),
            ];
            $response   = $app['splitzService']->evaluateRequest($properties);

            $app['trace']->info(TraceCode::SPLITZ_RESPONSE, [
                'properties'    => $properties,
                'merchant_id'   => $merchantId,
                'response'      => $response
            ]);

            if ($response['response']['variant']['name'] === 'enabled')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }

        return false;
    }
    public function isSplitPayment()
    {
        if ($this->order === null)
        {
            return false;
        }

        $amount = $this->getAdjustedAmountWrtCustFeeBearer();
        $amount = $this->getAmountWithoutConvenienceFeeIfApplicable($amount, $this->order);
        if ($amount === $this->order->getAmount())
        {
            return false;
        }

        if ($this->order->hasSplitPaymentMeta() === false)
        {
            return false;
        }

        return true;
    }

    /** isCollectExpiryDisabled() verifies if the
     * experiment is disabled for all payments timeout
     * on input collect expiry time
     * @param $merchantId
     * @return bool
     */
    public function isUpiCollectExpiryDisabled()
    {
        $app = \App::getFacadeRoot();

        $variant = $app['razorx']->getTreatment(
            $app['request']->getTaskId(),
            Merchant\RazorxTreatment::DISABLE_TIMEOUT_ON_UPI_COLLECT_EXPIRY,
            $app['rzp.mode'] ?? Mode::LIVE
        );

        $app['trace']->info(TraceCode::PAYMENT_UPI_COLLECT_EXPIRY_DISABLE_RAZORX_EXPERIMENT,
            [
                'variant'       => $variant,
                'merchant_id'   => $this->getMerchantId(),
            ]);

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }

    /** isMerchantDisabledOnCollectExpiry() verifies if the
     * experiment is disabled for MID timeout
     * on input collect expiry time
     * @param $merchantId
     * @return bool
     */
    public function isMerchantDisabledOnCollectExpiry($merchantId)
    {
        $app = \App::getFacadeRoot();

        $variant = $app['razorx']->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::BLOCK_MERCHANT_TIMEOUT_ON_UPI_COLLECT_EXPIRY,
            $app['rzp.mode'] ?? Mode::LIVE
        );

        $app['trace']->info(TraceCode::PAYMENT_UPI_COLLECT_EXPIRY_RAZORX_EXPERIMENT, [
            'payment_id'  => $this->getId(),
            'variant'     => $variant,
            'merchant_id' => $this->getMerchantId(),
            ]);

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }

    /**
     * isEmailOptionalForPartnerPayment checks if payment is made by partner & if the partner
     * is whitelisted to allowed to create payments without email
     */
    public function isEmailOptionalForPartnerPayment() : bool
    {
        $app = \App::getFacadeRoot();

        $partnerId = $app['basicauth']->getPartnerMerchantId();

        if (empty($partnerId) === true)
        {
            return false;
        }

        return ($this->isEmailOptionalForPartner($partnerId) === true);
    }

    private function isEmailOptionalForPartner($partnerId)
    {
        $app = \App::getFacadeRoot();

        try
        {
            $whitelistedPartnerIds = explode(',', $app['config']->get('app.email_optional_partner_MIDs'));

            return (in_array($partnerId, $whitelistedPartnerIds) === true);


        }
        catch (\Exception $e)
        {
            $app['trace']->info(TraceCode::EMAIL_OPTIONAL_CHECK_FOR_PARNTER_FAILED, [
                "error" => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function maskField($key, $value)
    {
        // mask only username for VPA, the PSP will remain intact
        if ($key === self::VPA)
        {
            $exploded = explode('@', $value);

            if (empty($exploded[0]) || empty($exploded[1])) // to handle incorrect VPA format value
            {
                return str_repeat('x', strlen($value));
            }
            else
            {
                return str_repeat('x', strlen($exploded[0])) . '@' . $exploded[1];
            }
        }

        return parent::maskField($key, $value);
    }

    public function hasReward()
    {
        if (isset($this->reward) === true)
        {
            return true;
        }

        return false;
    }

    public function isWalletRecurring(): bool
    {
        return (($this->getAttribute(self::METHOD) === Payment\Method::WALLET) and
            ($this->getAttribute(self::RECURRING) === true));
    }

    public function isWalletAutoRecurring(): bool
    {
        return (($this->isWalletRecurring() === true) and
            ($this->isSecondRecurring() === true));
    }

    public function fetchExternalTransferTypePaymentById(string $id): Payment\Entity
    {
        $class = EntityConstants::getExternalRepoSingleton('transfer');

        try
        {
            $entity = $class->fetchPaymentById($id);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

                return $entity;
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);
        }

        $data = [
            'model' => $this->entityName,
            'attributes' => [
                'id'       => $id,
            ],
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function getTransferAttribute()
    {
        $transfer = null;

        if ($this->relationLoaded('transfer') === true)
        {
            $transfer = $this->getRelation('transfer');
        }

        if ($transfer !== null)
        {
            return $transfer;
        }

        try {

            $transfer = (new Transfer\Repository)->findOrFail($this->getTransferId());

            $this->transfer()->associate($transfer);
        }
        catch ( \Throwable $e){}

        return $transfer;
    }
}
