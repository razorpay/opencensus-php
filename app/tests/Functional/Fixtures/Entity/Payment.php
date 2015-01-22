<?php

namespace Tests\Functional\Fixtures\Entity;

class Payment extends Base
{
    public function createCardCaptured(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'captured_at' => time(),
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $hdfcAttrArray = array(
            'trackid' => $payment->getKey(),
            'amount' => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $card = $this->create('card');

        $payment->card()->associate($card);

        $payment->save();

        $txn = (new Models\Transaction\Core)->createFromPayment($payment);
        $txn->save();

        $payment->setStatus('captured');
        $payment->save();

        $hdfcPaymentAuthorized = $this->createHdfcPaymentAuthorizedEntity(
            $hdfcAttrArray);

        $hdfcPaymentCaptured = $this->createHdfcPaymentCapturedEntity(
            $hdfcAttrArray);

        return $payment;
    }
}