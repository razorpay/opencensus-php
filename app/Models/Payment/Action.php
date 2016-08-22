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
    const AUTHORIZE         = 'authorize';
    const CALLBACK          = 'callback';
    const CAPTURE           = 'capture';
    const REFUND            = 'refund';
    const TOPUP             = 'topup';
    const DEBIT             = 'debit';
    const VERIFY            = 'verify';
    const VERIFY_REFUND     = 'verify_refund';
    const VERIFY_CAPTURE    = 'verify_capture';

    protected $merchant;

    protected $core;

    protected $trace;

    protected $payment;

    protected $mode;

    protected $repo;

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

        $this->repo = new Payment\Repository;

        $this->terminal = $this->getTerminal();
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
