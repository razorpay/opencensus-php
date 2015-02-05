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
        return $this->processor()->process($input);
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
        $refund = $this->processor()->refund($id, $input);

        return $refund->toArrayPublic();
    }

    public function verify($id)
    {
        $payment = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getKey());

        $this->processor()->verify($id);
    }

    public function retrieveRefund($id)
    {
        $refund = $this->core->retrieveRefundById($id, $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function retrieveMultipleRefunds($input)
    {
        $refunds = (new Refund\Repository)->fetch($input, $this->merchant->getKey());

        return $refunds->toArrayPublic();
    }

    public function retrieveRefundByIdAndPaymentId($paymentId, $rfndId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);
        Refund\Entity::verifyIdAndStripSign($rfndId);

        $refund = (new Refund\Repository)->fetchByIdPaymentIdMerchantId(
                                    $rfndId,
                                    $paymentId,
                                    $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function retrieveRefundsForPayment($paymentId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);

        $refunds = (new Refund\Repository)->findForPayment($paymentId);

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
        $payment = $this->processor()->capture($id, $input);

        return $payment->toArrayPublic();
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
        $payment = $this->processor()->callback($id, $input);

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }

    public function retrieveMultiple(array $input)
    {
        $payments = (new Payment\Repository)->fetch($input, $this->merchant->getKey());

        return $payments->toArrayPublic();
    }

    public function retrievePayment($id)
    {
        $payment = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getKey());

        return $payment->toArrayPublic();
    }

    public function expireAuthorizations()
    {
        $timestamp = time() - 24 * 60 * 60;

        $count = (new Payment\Repository)->expireAuthorizedPayments($timestamp);

        return array('count' => $count);
    }

    protected function processor()
    {
        return Payment\Processor\Processor::create($this->getBindings());
    }

    protected function getBindings()
    {
        $bindings = array(
            'merchant'  => $this->merchant,
            'core'      => $this->core,
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return $bindings;
    }
}
