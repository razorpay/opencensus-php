<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class Payment extends Base
{
    use TransactionTrait;

    protected $emandateRegistrationInitialPaymentDefaultAttributes = [
        'amount'         => 0,
        'method'         => 'emandate',
        'customer_id'    => '100000customer',
        'email'          => 'a@b.com',
        'contact'        => '+919918899029',
        'recurring_type' => 'initial',
        'auth_type'      => 'netbanking',
    ];

    public function createCaptured(array $attributes = array())
    {
        if (isset($attributes['method']))
        {
            switch ($attributes['method'])
            {
                case 'netbanking':
                    return $this->fixtures->create('payment:netbanking_captured');

                case 'upi':
                    return $this->fixtures->create('payment:netbanking_captured');
            }
        }

        return $this->fixtures->create('payment:card_captured', $attributes);
    }

    public function createSettled(array $attributes = [])
    {
        $payment = $this->createCaptured($attributes);

        $this->fixtures->edit('transaction', $payment->getTransactionId(), ['settled' => 1]);

        return $payment;
    }

    public function createStatusCreated(array $attributes = array())
    {
        $attributes['status'] = 'created';

        return $this->fixtures->create('payment', $attributes);
    }

    public function createStatusAuthenticated(array $attributes = array())
    {
        $attributes['status'] = 'authenticated';

        return $this->fixtures->create('payment', $attributes);
    }

    public function createEmandateRegistrationCaptured(array $attributes = array())
    {
        $defaultValues = [
            'amount'           => 0,
            'method'           => 'emandate',
            'status'           => 'captured',
            'customer_id'      => '100000customer',
            'email'            => 'a@b.com',
            'contact'          => '+919918899029',
            'auto_captured'    => true,
            'reference1'       => '9999999999',
            'gateway_captured' => true,
            'recurring'        => true,
            'recurring_type'   => 'initial',
            'auth_type'        => 'netbanking'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->create('payment', $attributes);
    }

    public function create(array $attributes = array())
    {
        $defaultValues = array(
            'terminal_id' => '1n25f6uN5S1Z5a'
        );

        $attributes = array_merge($defaultValues, $attributes);

        if (isset($attributes['amount']))
        {
            $attributes['base_amount'] = $attributes['amount'];
        }

        return parent::create($attributes);
    }

    public function createCardCaptured(array $attributes = array())
    {
        $time = Carbon::now()->getTimestamp();

        $createdAt = $time - 10;
        $updatedAt = $time + 10;

        $defaultValues = [
            'authorized_at' => $createdAt + 1,
            'captured_at'   => $updatedAt,
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->createCardAuthorized($attributes);

        $payment['status'] = 'captured';
        $payment['authorized_at'] = $attributes['authorized_at'];
        $payment['captured_at'] = $attributes['captured_at'];

        $hdfcAttrArray = [
            'payment_id' => $payment->getKey(),
            'amount'     => $payment->getAmount(),
            'created_at' => $payment->created_at,
            'updated_at' => $payment->created_at
        ];

        $payment->saveOrFail();

        list($txn, $feesSplit) = $this->updateTransactionOnCapture($payment);

        $txn->saveOrFail();

        $payment->setStatus('captured');
        $payment->saveOrFail();

        $hdfcPaymentAuthorized = $this->fixtures->create('hdfc:authorized', $hdfcAttrArray);
        $hdfcPaymentCaptured = $this->fixtures->create('hdfc:captured', $hdfcAttrArray);

        return $payment;
    }

    public function createCardAuthenticated(array $attributes = array())
    {
        $time = Carbon::now()->getTimestamp();

        $createdAt = $time - 10;
        $updatedAt = $time + 10;
        $authenticatedAt = $time + 10;

        $defaultValues = [
            'created_at'       => $createdAt,
            'updated_at'       => $updatedAt,
            'authenticated_at' => $authenticatedAt,
            'authorized_at'    => null
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->createCardAuthorized($attributes);

        $payment['status'] = 'authenticated';
        $payment['cps_route'] = 2;

        $payment->saveOrFail();
    }

    public function createNetbankingCaptured(array $attributes = array())
    {
        $payment = $this->createNetbankingAuthorized($attributes);
        $payment['authorized_at'] = $payment['created_at'];
        $payment['captured_at'] = $payment['created_at'] + 10;

        list($txn, $feesSplit) = $this->updateTransactionOnCapture($payment);

        $txn->saveOrFail();

        $payment->setStatus('captured');
        $payment->saveOrFail();

        return $payment;
    }

    public function createNetbankingAuthorized(array $attributes = array())
    {
        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'authorized',
            'gateway'        => 'sharp',
            'method'         => 'netbanking',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at'     => $now - 10,
            'updated_at'     => $now - 5
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->saveOrFail();

        list($txn, $feesSplit) = $this->createTransactionForPaymentAuthorized($payment);

        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }


    public function createUpiCaptured(array $attributes = array())
    {
        $payment = $this->createUpiAuthorized($attributes);
        $payment['authorized_at'] = $payment['created_at'];
        $payment['captured_at'] = $payment['created_at'] + 10;

        list($txn, $feesSplit) = $this->updateTransactionOnCapture($payment);

        $txn->saveOrFail();

        $payment->setStatus('captured');
        $payment->saveOrFail();

        return $payment;
    }

    public function createUpiAuthorized(array $attributes = array())
    {
        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'status'         => 'authorized',
            'gateway'        => 'sharp',
            'method'         => 'upi',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at'     => $now - 10,
            'updated_at'     => $now - 5
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->saveOrFail();

        list($txn, $feesSplit) = $this->createTransactionForPaymentAuthorized($payment);

        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function createNetbankingCreated(array $attributes = array())
    {
        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'created',
            'gateway'        => 'billdesk',
            'method'         => 'netbanking',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at'     => $now - 10,
            'updated_at'     => $now - 5
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->build('payment', $attributes);

        $payment->saveOrFail();

        return $payment;
    }

    public function createNetbankingFailed(array $attributes = array())
    {
        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'failed',
            'gateway'        => 'billdesk',
            'method'         => 'netbanking',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'verify_bucket'  => 0,
            'created_at'     => $now - 10,
            'updated_at'     => $now - 5
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

        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'merchant_id'   => '10000000000000',
            'authorized_at' => $now,
            'status'        => 'authorized',
            'terminal_id'   => '1n25f6uN5S1Z5a',
            'card_id'       => $card['id'],
            'international' => false,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);
        $merchant = (new \RZP\Models\Merchant\Repository)->find($attributes['merchant_id']);

        $payment->merchant()->associate($merchant);
        $payment->setRelation('card', $card);

        $hdfcPayment = $this->fixtures->create('hdfc:authorized',
            [
                'payment_id' => $payment->getKey(),
                'amount'     => $payment->getAmount(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->created_at,
            ]);

        list($txn, $feesSplit) = $this->createTransactionForPaymentAuthorized($payment);

        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function createEmandateAuthorized(array $attributes = array())
    {
        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'merchant_id'   => '10000000000000',
            'authorized_at' => $now,
            'status'        => 'authorized',
            'terminal_id'   => '1n25f6uN5S1Z5a',
            'method'        => 'emandate',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);
        $merchant = (new \RZP\Models\Merchant\Repository)->find($attributes['merchant_id']);

        $payment->merchant()->associate($merchant);

        list($txn, $feesSplit) = $this->createTransactionForPaymentAuthorized($payment);

        $txn->saveOrFail();

        $payment->saveOrFail();

        $enachAttributes = [
            'payment_id' => $payment->getId(),
            'acquirer'   => 'ratn',
            'action'     => 'authorize',
            'bank'       => $payment->getBank(),
            'amount'     => $payment->getAmount(),
            'signed_xml' => '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.009.001.04"></Document>'
        ];

        $this->fixtures->create('enach', $enachAttributes);

        return $payment;
    }

    public function createPurchased(array $attributes = array())
    {
        $cardAttributes = [
            'iin'       => '502165',
            'last4'     => '1111',
            'network'   => 'Maestro'
        ];

        $card = $this->fixtures->create('card', $cardAttributes);

        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'authorized_at' => $now,
            'status'        => 'authorized',
            'terminal_id'   => '1n25f6uN5S1Z5a',
            'card_id'       => $card['id'],
            'international' => false,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);

        $hdfcPayment = $this->fixtures->create('hdfc:purchased',
            [
                'payment_id' => $payment->getKey(),
                'amount'     => $payment->getAmount(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->created_at,
            ]);

        list($txn, $feesSplit) = $this->createTransactionForPaymentAuthorized($payment);

        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function createCreated(array $attributes = array())
    {
        $defaultValues = [
            'status'      => 'created',
            'terminal_id' => '1n25f6uN5S1Z5a',
            'card_id'     => '12345678901234',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);

        return $payment;
    }

    public function createFailed(array $attributes = array())
    {
        $defaultValues = [
            'status'      => 'failed',
            'terminal_id' => '1n25f6uN5S1Z5a',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);

        return $payment;
    }

    public function createMethodTransfer(array $attributes = [])
    {
        $now = Carbon::now()->getTimestamp();

        $defaultValues = [
            'status'        => 'captured',
            'method'        => 'transfer',
            'captured_at'   => $now,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);

        list($txn, $feesSplit) = $this->createTransactionOnPaymentMethodTransfer($payment);

        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function failPayment($id)
    {
        $this->edit(
            $id, ['status' => 'failed', 'error_code' => 'BAD_REQUEST_PAYMENT_FAILED']);
    }

    public function createEmandateRegistrationInitial(array $attributes = [])
    {
        $defaults = array_merge(
            $this->emandateRegistrationInitialPaymentDefaultAttributes,
            ['status'         => 'authorized']
        );

        $attributes = array_merge($defaults, $attributes);

        return $this->create($attributes);
    }

    public function createEmandateRegistrationConfirmed(array $attributes = [])
    {
        $defaults = array_merge(
            $this->emandateRegistrationInitialPaymentDefaultAttributes,
            ['status'         => 'captured']
        );

        $attributes = array_merge($defaults, $attributes);

        return $this->create($attributes);
    }

    public function createEmandateDebit(array $attributes = [])
    {
        $defaults = [
            'method'         => 'emandate',
            'customer_id'    => '100000customer',
            'auto_captured'  => false,
            'email'          => 'a@b.com',
            'contact'        => '+919918899029',
            'recurring_type' => 'auto',
            'auth_type'      => 'netbanking',
            'recurring'      => 1,
        ];

        $attributes = array_merge($defaults, $attributes);

        return $this->create($attributes);
    }

    public function createTxnForAuthPayment($payment)
    {
        return $this->createTransactionForPaymentAuthorized($payment);
    }
}
