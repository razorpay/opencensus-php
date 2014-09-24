<?php

namespace Models\Payment;

use EE\Exception\BadRequestException;

use Models\Base;
use Models\Payment;

use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payment\Core();
    }

    /**
     * Processes a payment.
     */
    public function process(array $input)
    {
        $data = $this->getActionInstance(Payment\Action::AUTHORIZE)
                     ->process($input);

        //
        // The returned value could be either Payment
        // model or an array containing callback data.
        // We convert txn model to array
        // if it's a txn model
        //
        if ($data instanceof Payment\Entity)
            $data = $data->toArrayPublic();

        return $data;
    }

    /**
     * Refunds a payment
     *
     * @param  string   $id
     *
     * @return Payment\Entity
     */
    public function refund($id, $input)
    {
        $refund = $this->getActionInstance(Payment\Action::REFUND)
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

    public function retrieveRefundByIdAndPaymentId($txnId, $rfndId)
    {
        Payment\Entity::verifyIdAndStripSign($txnId);
        Refund\Entity::verifyIdAndStripSign($rfndId);

        $refund = (new Refund\Repository)->fetchByIdTxnIdMerchantId(
                                    $rfndId,
                                    $txnId,
                                    $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function retrieveRefundsForPayment($txnId)
    {
        Payment\Entity::verifyIdAndStripSign($txnId);

        $refunds = (new Refund\Repository)->findForPayment($txnId);

        return $refunds->toArrayPublic();
    }

    /**
     * Captures a payment
     *
     * @param  string   $id
     *
     * @return Payment\Entity
     */
    public function capture($id, $input)
    {
        $txn = $this->getActionInstance(Payment\Action::CAPTURE)
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
        $txn = $this->getActionInstance(Payment\Action::AUTHORIZE)
                    ->callback($id, $input);

        return $txn->toArrayPublic();
    }

    public function retrieveMultiple(array $input)
    {
        $txns = (new Payment\Repository)->fetch($input, $this->merchant->getKey());

        return $txns->toArrayPublic();
    }

    public function retrievePayment($id)
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

        return Payment\Action::create($action, $bindings);
    }
}
