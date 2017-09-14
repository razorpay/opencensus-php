<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Transaction;

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
        $time = time();

        $createdAt = $time - 10;
        $updatedAt = $time + 10;

        $defaultValues = [
            'authorized_at' => $createdAt + 1,
            'captured_at'   => $updatedAt,
            'created_at'    => $createdAt,
            'updated_at'    => $updatedAt
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
        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'authorized',
            'gateway'        => 'sharp',
            'method'         => 'netbanking',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'created_at'     => time() - 10,
            'updated_at'     => time() - 5
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
        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'created',
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

    public function createNetbankingFailed(array $attributes = array())
    {
        $defaultValues = [
            'bank'           => 'HDFC',
            'status'         => 'failed',
            'gateway'        => 'billdesk',
            'method'         => 'netbanking',
            'terminal_id'    => '1n25f6uN5S1Z5a',
            'transaction_id' => null,
            'verify_bucket'  => 0,
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

        $defaultValues = [
            'merchant_id'   => '10000000000000',
            'authorized_at' => time(),
            'status'        => 'authorized',
            'terminal_id'   => '1n25f6uN5S1Z5a',
            'card_id'       => $card['id'],
            'international' => false,
            'approval_code' => rand(111111, 999999)
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

    public function createPurchased(array $attributes = array())
    {
        $cardAttributes = [
            'iin'       => '502165',
            'last4'     => '1111',
            'network'   => 'Maestro'
        ];

        $card = $this->fixtures->create('card', $cardAttributes);

        $defaultValues = [
            'authorized_at' => time(),
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
            'card_id'     => '12345678901234',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);

        return $payment;
    }

    public function createMethodTransfer(array $attributes = [])
    {
        $defaultValues = [
            'status'        => 'captured',
            'method'        => 'transfer',
            'captured_at'   => time(),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payment = $this->create($attributes);

        list($txn, $feesSplit) = $this->createTransactionOnPaymentMethodTransfer($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $payment->getCreatedAt());

        $txn->saveOrFail();

        $payment->saveOrFail();

        return $payment;
    }

    public function failPayment($id)
    {
        $this->edit(
            $id, ['status' => 'failed', 'error_code' => 'BAD_REQUEST_PAYMENT_FAILED']);
    }
}
