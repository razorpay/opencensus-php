<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Payment extends Base
{
    use TransactionTrait;

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

        return $this->fixtures->create('payment', $attributes);
    }

    public function create(array $attributes = array())
    {
        $defaultValues = array(
            'terminal_id' => '1n25f6uN5S1Z5a'
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createCardCaptured(array $attributes = array())
    {
        $defaultValues = array(
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->createCardAuthorized($attributes);

        $payment['status'] = 'captured';
        $payment['authorized_at'] = $attributes['created_at'];
        $payment['captured_at'] = $attributes['created_at'] + 10;

        $hdfcAttrArray = array(
            'payment_id' => $payment->getKey(),
            'amount'     => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at);

        $payment->saveOrFail();

        $txn = $this->updateTransactionOnCapture($payment);
        $txn->saveOrFail();

        $payment->setStatus('captured');
        $payment->saveOrFail();

        $hdfcPaymentAuthorized = $this->fixtures->create('hdfc:authorized', $hdfcAttrArray);
        $hdfcPaymentCaptured = $this->fixtures->create('hdfc:captured', $hdfcAttrArray);

        return $payment;
    }

    public function createNetbankingCaptured(array $attributes = array())
    {
        $payment = $this->createNetbankingAuthorized($attributes);
        $payment['authorized_at'] = $payment['created_at'];
        $payment['captured_at'] = $payment['created_at'] + 10;

        $txn = $this->updateTransactionOnCapture($payment);
        $txn->saveOrFail();

        $payment->setStatus('captured');
        $payment->saveOrFail();

        return $payment;
    }

    public function createNetbankingAuthorized(array $attributes = array())
    {
        $defaultValues = array(
            'bank'  => 'HDFC',
            'status' => 'authorized',
            'gateway' => 'sharp',
            'method' => 'netbanking',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at' => time() - 10,
            'updated_at' => time() - 5);

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->saveOrFail();

        $txn = $this->createTransactionForPaymentAuthorized($payment);
        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function createNetbankingFailed(array $attributes = array())
    {
        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'failed',
            'gateway'        => 'billdesk',
            'method'         => 'netbanking',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at'     => time() - 10,
            'updated_at'     => time() - 5
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->saveOrFail();

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
            'authorized_at' => time(),
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'card_id' => $card['id'],
            'international' => false,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $payment = parent::create($attributes);

        $hdfcPayment = $this->fixtures->create('hdfc:authorized',
            array(
                'payment_id' => $payment->getKey(),
                'amount' => $payment->getAmount(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->created_at,
            ));

        $txn = $this->createTransactionForPaymentAuthorized($payment);
        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function createPurchased(array $attributes = array())
    {
        $cardAttributes = [
            'iin'               =>  '502165',
            'last4'             =>  '1111',
            'network'           =>  'Maestro'
        ];

        $card = $this->fixtures->create('card', $cardAttributes);

        $defaultValues = array(
            'authorized_at' => time(),
            'status' => 'authorized',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'card_id' => $card['id'],
            'international' => false,
        );

        $attributes = array_merge($defaultValues, $attributes);

        $payment = parent::create($attributes);

        $hdfcPayment = $this->fixtures->create('hdfc:purchased',
            array(
                'payment_id' => $payment->getKey(),
                'amount' => $payment->getAmount(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->created_at,
            ));

        $txn = $this->createTransactionForPaymentAuthorized($payment);
        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function createFailed(array $attributes = array())
    {
        $defaultValues = array(
            'status' => 'failed',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'card_id' => '12345678901234',
        );

        $attributes = array_merge($defaultValues, $attributes);

        $payment = parent::create($attributes);

        return $payment;
    }

    public function failPayment($id)
    {
        $this->edit(
            $id, ['status' => 'failed', 'error_code' => 'BAD_REQUEST_PAYMENT_FAILED']);
    }
}
