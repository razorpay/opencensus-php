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
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->createCardAuthorized($attributes);

        $payment['status'] = 'captured';
        $payment['captured_at'] = $attributes['created_at'] + 10;

        $hdfcAttrArray = array(
            'payment_id' => $payment->getKey(),
            'amount'     => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $payment->save();

        $txn = (new \Models\Transaction\Core)->updateOnCapture($payment);
        $txn->save();

        $payment->setStatus('captured');
        $payment->save();

        $hdfcPaymentAuthorized = $this->fixtures->create('hdfc:authorized', $hdfcAttrArray);
        $hdfcPaymentCaptured = $this->fixtures->create('hdfc:captured', $hdfcAttrArray);

        return $payment;
    }

    public function createNetbankingCaptured(array $attributes = array())
    {
        $payment = $this->createNetbankingAuthorized($attributes);
        $payment['captured_at'] = $payment['created_at'] + 10;

        $txn = (new \Models\Transaction\Core)->updateOnCapture($payment);
        $txn->save();

        $payment->setStatus('captured');
        $payment->save();

        return $payment;
    }

    public function createNetbankingAuthorized(array $attributes = array())
    {
        $defaultValues = array(
            'bank'  => 'HDFC',
            'status' => 'authorized',
            'gateway' => 'atom',
            'method' => 'netbanking',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->save();

        $txn = (new \Models\Transaction\Core)->createFromPaymentAuthorized($payment);
        $txn->save();

        $payment->save();

        return $payment;
    }

    public function createNetbankingFailed(array $attributes = array())
    {
        $defaultValues = array(
            'bank'  => 'HDFC',
            'status' => 'failed',
            'gateway' => 'atom',
            'method' => 'netbanking',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->save();

        return $payment;
    }

    public function createCardAuthorized(array $attributes = array())
    {
        $attributes['method'] = 'card';

        $payment = $this->createAuthorized($attributes);

        return $payment;
    }

    public function createAuthorized(array $attributes = array())
    {
        $card = $this->fixtures->create('card');

        $defaultValues = array(
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'card_id' => $card['id'],
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

        $txn = (new \Models\Transaction\Core)->createFromPaymentAuthorized($payment);
        $txn->save();

        $payment->save();

        return $payment;
    }
}
