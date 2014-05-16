<?php

use Monolog\Logger;

class TransactionTrace extends \Singleton
{
    const COMPONENT = 'transaction';

    // string code for events
    const REQUEST_FOR_NEW_TRANSACTION = 'REQUEST_FOR_NEW_TRANSACTION';
    const GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL = 'GATEWAY_HDFC_ACS_CALLBACK_SUCCESSFUL';
    const GATEWAY_HDFC_ACS_REQUEST_TIMEOUT = 'GATEWAY_HDFC_ACS_REQUEST_TIMEOUT';
    const CARD_NOT_PROVIDED = 'CARD_NOT_PROVIDED';
    const MERCHANT_ID_MISMATCH = 'MERCHANT_ID_MISMATCH';
    const REQUEST_FOR_REFUND = 'REQUEST_FOR_REFUND';
    const INVALID_TRANSACTION_ID = 'INVALID_TRANSACTION_ID';

    /**
     * Transaction id of the current process
     *
     * @var int $transactionId Transaction id
     */
    protected static $transactionId;

    /**
     * Status of the transaction
     *
     * @var string $transactionStatus Transaction status
     */
    protected static $transactionStatus;

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
     *
     * @var array $updateTransactionOperations Transaction id update operations
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
            // grabs and uses transaction id from request url
            $transactionId = \Request::segment(2);
            static::setTransactionId($transactionId);
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
        static::$transactionId = $id;
    }

    public static function getTransactionId($id)
    {
        return static::$transactionId;
    }

    public static function setTransactionStatus($status)
    {
        static::$transactionStatus = $status;
    }

    public static function getTransactionStatus($status)
    {
        return static::$transactionStatus;
    }

    public function addRecord($level, $message, $event)
    {
        $context = array(
            'object' => 'transaction',
            'id' => static::$transactionId,
            'status' => static::$transactionStatus,
            'event' => $event);

        $this->trace->addRecord($level, $message, $context);
    }

    public function info($message, $event)
    {
        $this->addRecord(Logger::INFO, $message, $event);
    }
}