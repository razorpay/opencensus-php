<?php

namespace Trace;

use Trace\Trace;

class TransactionTrace extends \Singleton
{
    const COMPONENT = 'transaction';

    // status codes for operations
    const GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_HDFC_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const CARD_NOT_PROVIDED = 'CARD_NOT_PROVIDED';
    const MERCHANT_ID_MISMATCH = 'MERCHANT_ID_MISMATCH';
    const REQUEST_FOR_REFUND = 'REQUEST_FOR_REFUND';

    /**
     * Transaction id of the current process
     *
     * @var int $transaction_id Transaction id
     */
    protected static $transaction_id;

    /**
     * Request => Operation mapping
     *
     * @var array $operations Operation mapping
     */
    protected static $operations = array(
        'POST' => array(
            'transactions' => 'CREATE_TRANSACTION',
            'transactions/*/refund' => 'REFUND_TRANSACTION',
            'transactions/*/process' => 'PROCESS_TRANSACTION'),
        'GET' => array(
            'transactions' => 'RETRIEVE_TRANSACTIONS',
            'transactions/*' => 'RETRIEVE_TRANSACTION'));

    protected $trace;

    protected function __construct()
    {
        $this->trace = new Trace(static::COMPONENT, static::getOperation());
    }

    private static function getOperation()
    {
        $method = \Request::method();
        foreach (static::$operations[$method] as $pattern => $operation) {
            if(\Request::is($pattern))
            {
                return $operation;
            }
        }
        return false;
    }

    public function info($message, $status_code)
    {
        $context = array(
            'transaction' => array(
                'id' => static::$transaction_id,
                'operation' => static::getOperation(),
                'status_code' => $status_code));

        $this->trace->info($message, $context);
    }

    
}