<?php

namespace RZP\Models\Payment;

use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Action
{
    const AUTHORIZE               = 'authorize';
    const CALLBACK                = 'callback';
    const CAPTURE                 = 'capture';
    const OTP_GENERATE            = 'otp_generate';
    const REFUND                  = 'refund';
    const VOID                    = 'void';
    const REVERSE                 = 'reverse';
    const TOPUP                   = 'topup';
    const DEBIT                   = 'debit';
    const VERIFY                  = 'verify';
    const VERIFY_INTERNAL_REFUND  = 'verify_internal_refund';
    const VERIFY_REFUND           = 'verify_refund';
    const VERIFY_CAPTURE          = 'verify_capture';
    const MANUAL_GATEWAY_REFUND   = 'manual_gateway_refund';
    const MANUAL_GATEWAY_CAPTURE  = 'manual_gateway_capture';
    const CREATE_REFUND_RECORD    = 'create_refund_record';
    const ALREADY_REFUNDED        = 'already_refunded';
    const VALIDATE_UNKNOWN_REFUND = 'validate_unknown_refund';

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
