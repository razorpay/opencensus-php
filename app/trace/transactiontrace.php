<?php

namespace Trace;

use Trace\Trace;
use Monolog\Logger;

class TransactionTrace extends \Singleton
{
    const COMPONENT = 'transaction';

    // status codes for operations
    const GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_HDFC_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const CARD_NOT_PROVIDED = 'CARD_NOT_PROVIDED';
    const MERCHANT_ID_MISMATCH = 'MERCHANT_ID_MISMATCH';
    const REQUEST_FOR_REFUND = 'REQUEST_FOR_REFUND';
    const INVALID_TRANSACTION_ID = 'INVALID_TRANSACTION_ID';

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

    /**
     * Operations for which the transaction id is to be updated
     */
    protected static $updateTransactionOperations = array(
        'REFUND_TRANSACTION',
        'PROCESS_TRANSACTION',
        'RETRIEVE_TRANSACTION');

    protected $trace;

    public function initialize()
    {
        $operation = static::getOperation();
        $this->trace = new Trace(static::COMPONENT, $operation);

        if(in_array($operation, static::$updateTransactionOperations)) {
            $transaction_id = \Request::segment(2);
            static::setTransactionId($transaction_id);
        }
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

    public static function setTransactionId($id)
    {
        static::$transaction_id = $id;
    }

    public static function getTransactionId($id)
    {
        return static::$transaction_id;
    }

    public function addRecord($level, $message, $status_code)
    {
        $context = array(
            'transaction' => array(
                'id' => static::$transaction_id,
                'status_code' => $status_code));

        $this->trace->addRecord($level, $message, $context);
    }

    public function info($message, $status_code)
    {
        $this->addRecord(Logger::INFO, $message, $status_code);
    }
    
}