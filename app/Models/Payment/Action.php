<?php

namespace RZP\Models\Payment;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Action
{
    const ENROLL                        = 'enroll';
    const AUTHORIZE                     = 'authorize';
    const CALLBACK                      = 'callback';
    const PAY                           = 'pay';
    const CAPTURE                       = 'capture';
    const OTP_GENERATE                  = 'otp_generate';
    const REFUND                        = 'refund';
    const VOID                          = 'void';
    const REVERSE                       = 'reverse';
    const TOPUP                         = 'topup';
    const PRE_DEBIT                     = 'pre_debit';
    const DEBIT                         = 'debit';
    const VERIFY                        = 'verify';
    const VERIFY_GATEWAY                = 'verify_gateway';
    const VERIFY_INTERNAL_REFUND        = 'verify_internal_refund';
    const VERIFY_REFUND                 = 'verify_refund';
    const VERIFY_CAPTURE                = 'verify_capture';
    const MANUAL_GATEWAY_REFUND         = 'manual_gateway_refund';
    const MANUAL_GATEWAY_CAPTURE        = 'manual_gateway_capture';
    const CREATE_REFUND_RECORD          = 'create_refund_record';
    const ALREADY_REFUNDED              = 'already_refunded';
    const VALIDATE_UNKNOWN_REFUND       = 'validate_unknown_refund';
    const AUTHORIZE_FAILED              = 'authorize_failed';
    const FORCE_AUTHORIZE_FAILED        = 'force_authorize_failed';
    const CALLBACK_OTP_SUBMIT           = 'callback_otp_submit';
    const CHECK_BALANCE                 = 'check_balance';
    const GENERATE_REFUNDS              = 'generate_refunds';
    const GENERATE_CLAIMS               = 'generate_claims';
    const RECONCILE_DEBIT_EMANDATE      = 'reconcile_debit_emandate';
    const VALIDATE_VPA                  = 'validate_vpa';
    const VALIDATE_PUSH                 = 'validate_push';
    const AUTHORIZE_PUSH                = 'authorize_push';
    const OTP_RESEND                    = 'otp_resend';
    const PAYOUT                        = 'payout';
    const PAYOUT_VERIFY                 = 'payout_verify';
    const CHECK_ACCOUNT                 = 'check_account';
    const FETCH_TOKEN                   = 'fetch_token';
    const OMNI_PAY                      = 'omni_pay';
    const MANDATE_UPDATE                = 'mandate_update';
    const MANDATE_CANCEL                = 'mandate_cancel';
    const VALIDATE_APP                  = 'validate_app';
    const VALIDATE_CRED                 = 'validate_cred';
    const CARD_MANDATE_CREATE           = 'card_mandate_create';
    const CARD_MANDATE_PRE_DEBIT_NOTIFY = 'card_mandate_pre_debit_notify';
    const CARD_MANDATE_VERIFY           = 'card_mandate_verify';
    const REPORT_PAYMENT                = 'report_payment';
    const CARD_MANDATE_UPDATE           = 'card_mandate_update';
    const CARD_MANDATE_CANCEL           = 'card_mandate_cancel';
    const CARD_MANDATE_UPDATE_TOKEN     = 'card_mandate_update_token';
    const CHECK_BIN                     = 'check_bin';


    protected $merchant;

    protected $core;

    protected $trace;

    protected $payment;

    protected $mode;

    public function __construct(
        Merchant\Entity $merchant,
        Payment\Core $core,
        Trace $trace,
        $mode)
    {
        $this->merchant = $merchant;
        $this->core = $core;
        $this->trace = $trace;
        $this->mode = $mode;

        $this->checkMerchantPermissions();
    }

    public static function validateAction(string $action)
    {
        if (defined(__CLASS__ . '::' . strtoupper($action)) === false)
        {
            throw new Exception\RuntimeException('Action provided is invalid: ' . $action);
        }
    }

    public static function create($action, $bindings)
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($action);

        if ($action === self::REFUND)
        {
            $class .= '\Process';
        }

        return new $class(
            $bindings['merchant'],
            $bindings['core'],
            $bindings['trace'],
            $bindings['mode']);
    }

    protected function tracePaymentNewRequest($input)
    {
        $inputTrace = $input;

        unset($inputTrace['notes'], $inputTrace['contact'], $inputTrace['email']);

        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $inputTrace);
    }
}
