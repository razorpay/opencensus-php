<?php

namespace Tests\Functional\Fixtures\Entity;

class Payment extends Base
{
    public function createCaptured(array $attributes = array())
    {
        if ((isset($attributes['method'])) and
            ($attributes['method'] === 'netbanking'))
        {
            return $this->fixtures->create('payment:netbanking_captured');
        }

        return $this->fixtures->create('payment:card_captured', $attributes);
    }

    public function createStatusCreated(array $attributes = array())
    {
        $attributes['status'] = 'created';

        return $this->create('payment', $attributes);
    }

    public function create($entity, array $attributes = array())
    {
        $defaultValues = array(
            'terminal_id' => '1n25f6uN5S1Z5a'
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($entity, $attributes);
    }

    public function createCardCaptured(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'method' => 'card',
            'transaction_id' => null,
            'captured_at' => time(),
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $hdfcAttrArray = array(
            'payment_id'    => $payment->getKey(),
            'amount'     => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $card = $this->fixtures->create('card');

        $payment->card()->associate($card);

        $payment->save();

        $txn = (new \Models\Transaction\Core)->createFromPayment($payment);
        $txn->save();

        $payment->setStatus('captured');
        $payment->save();

        $hdfcPaymentAuthorized = $this->fixtures->create('hdfc:authorized', $hdfcAttrArray);

        $hdfcPaymentCaptured = $this->fixtures->create('hdfc:captured', $hdfcAttrArray);

        return $payment;
    }

    public function createNetbankingCaptured(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'authorized',
            'gateway' => 'atom',
            'method' => 'netbanking',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'captured_at' => time(),
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->save();

        $txn = (new \Models\Transaction\Core)->createFromPayment($payment);
        $txn->save();

        $payment->setStatus('captured');
        $payment->save();

        return $payment;
    }

    public function createAuthorized(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
        );

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create('payment', $attributes);

        $hdfcPayment = $this->fixtures->create('hdfc:authorized',
            array(
                'payment_id' => $payment->getKey(),
                'amount' => $payment->getAmount(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->created_at,
            ));

        return $payment;
    }
}