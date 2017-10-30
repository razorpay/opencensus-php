<?php

namespace RZP\Models\Payment;

use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Trace\TraceCode;

use Razorpay\Trace\Logger as Trace;

class Action
{
    const AUTHORIZE                     = 'authorize';
    const CALLBACK                      = 'callback';
    const CAPTURE                       = 'capture';
    const OTP_GENERATE                  = 'otp_generate';
    const REFUND                        = 'refund';
    const VOID                          = 'void';
    const REVERSE                       = 'reverse';
    const TOPUP                         = 'topup';
    const DEBIT                         = 'debit';
    const VERIFY                        = 'verify';
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
    const INITIATE_REGISTER_EMANDATE    = 'initiate_register_emandate';
    const RECONCILE_REGISTER_EMANDATE   = 'reconcile_register_emandate';
    const INITIATE_DEBIT_EMANDATE       = 'initiate_debit_emandate';
    const RECONCILE_DEBIT_EMANDATE      = 'reconcile_debit_emandate';

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
        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $input);
    }
}
