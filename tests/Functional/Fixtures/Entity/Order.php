<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Order extends Base
{
    public function createTpvOrder(Array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'               => '10000000000000',
            'account_number'            => '0001231321321',
            'bank'                      => 'ICIC',
            'method'                    => 'netbanking',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createPaymentCaptureOrder(Array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'               => '10000000000000',
            'receipt'                   => 'test_auto_capture_receipt',
            'currency'                  => 'INR',
            'amount'                    => 1000,
            'payment_capture'           => true,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createOrderWithOfferApplied(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'receipt'                   => 'test_tpv_receipt',
            'currency'                  => 'INR',
            'amount'                    => 100000,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createPaidOrder(array $attributes = [])
    {
        $amountPaid = $attributes['amount_paid'] ?? 1000000;
        $status     = $attributes['status'] ?? 'paid';

        $attributes = array_merge(
                        $attributes,
                        [
                            'amount_paid' => $amountPaid,
                            'status'      => $status,
                        ]);

        return parent::create($attributes);
    }
}
