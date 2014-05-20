<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class TransactionTrace extends Trace
{
    // string code for events
    const NEW_TRANSACTION = 'NEW_TRANSACTION';
    const GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_HDFC_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const CARD_NOT_PROVIDED = 'CARD_NOT_PROVIDED';
    const MERCHANT_ID_MISMATCH = 'MERCHANT_ID_MISMATCH';
    const REFUND_SUCCESSFUL = 'REFUND_SUCCESSFUL';
    const INVALID_TRANSACTION_ID = 'INVALID_TRANSACTION_ID';

    protected $component = 'transaction';

    protected static $compulsoryFields = array(
        'message',
        'transaction_id');

    protected static $fields = array(
        self::NEW_TRANSACTION => array(
            'status'),
        self::REFUND_SUCCESSFUL => array(
            'status',
            'previous_status'),
        );

    public function debug($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::DEBUG, $message);
    }

    public function info($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::INFO, $message);
    }

    public function notice($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::NOTICE, $message);
    }

    public function warning($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::WARNING, $message);
    }

    public function error($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::ERROR, $message);
    }

    public function critical($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::CRITICAL, $message);
    }

    public function alert($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::ALERT, $message);
    }

    public function emergency($code, array $traceMessage = array())
    {
        $message = $traceMessage['message'];

        unset($traceMessage['message']);

        $this->updateAllValues($code, $traceMessage);

        $this->addRecord(Logger::EMERGENCY, $message);
    }
}