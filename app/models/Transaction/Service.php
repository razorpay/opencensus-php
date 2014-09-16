<?php

namespace Models\Transaction;

use EE\Exception\BadRequestException;

use Models\Base;
use Models\Transaction;

use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Transaction\Core();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    /**
     * Processes a transaction.
     */
    public function process(array $input)
    {
        $data = $this->getActionInstance(Transaction\Action::AUTHORIZE)
                     ->process($input);

        //
        // The returned value could be either Transaction
        // model or an array containing callback data.
        // We convert txn model to array
        // if it's a txn model
        //
        if ($data instanceof Transaction\Entity)
            $data = $data->toArrayPublic();

        return $data;
    }

    /**
     * Refunds a transaction
     *
     * @param  string   $id
     *
     * @return Transaction\Entity
     */
    public function refund($id, $input)
    {
        $refund = $this->getActionInstance(Transaction\Action::REFUND)
                       ->process($id, $input);

        return $refund->toArrayPublic();
    }

    public function retrieveRefund($id)
    {
        $refund = $this->core->retrieveRefund($id, $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function retrieveMultipleRefunds($input)
    {
        $refunds = (new Refund\Repository)->fetch($input, $this->merchant->getKey());

        return $refunds->toArrayPublic();
    }

    public function retrieveRefundByIdAndTransactionId($txnId, $rfndId)
    {
        $refund = $this->core->retrieveByIdAndMerchantId(
                                    $rfndId,
                                    $this->merchant->getKey(),
                                    $txnId);

        return $refund->toArrayPublic();
    }

    public function retrieveRefundsForTransaction($txnId)
    {
        Transaction\Entity::verifyIdAndStripSign($txnId);

        $refunds = (new Refund\Repository)->findForTransaction($txnId);

        return $refunds->toArrayPublic();
    }

    /**
     * Captures a transaction
     *
     * @param  string   $id
     *
     * @return Transaction\Entity
     */
    public function capture($id, $input)
    {
        $txn = $this->getActionInstance(Transaction\Action::CAPTURE)
                    ->process($id, $input);

        return $txn->toArrayPublic();
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return Transaciton\Entity
     */
    public function bankAcsCallback($id, array $input)
    {
        $txn = $this->getActionInstance(Transaction\Action::AUTHORIZE)
                    ->callback($id, $input);

        return $txn->toArrayPublic();
    }

    public function retrieveMultiple(array $input)
    {
        $txns = (new Transaction\Repository)->fetch($input, $this->merchant->getKey());

        return $txns->toArrayPublic();
    }

    public function retrieveTransaction($id)
    {
        $txn = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getKey());

        return $txn->toArrayPublic();
    }

    protected function getActionInstance($action)
    {
        $bindings = array(
            'merchant'  => $this->merchant,
            'core'      => $this->core,
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return Transaction\Action::create($action, $bindings);
    }
}
