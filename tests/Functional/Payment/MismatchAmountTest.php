<?php

namespace Functional\Payment;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\TestCase;
use RZP\Models\Payment\PaymentMeta;

class MismatchAmountTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function testMismatchAmountWithoutReason()
    {
        $input = [
            'payment_id'        => 'pay_GON008vWuvOlwq',
            'mismatch_amount'   => 1,
        ];

        $this->makeRequestAndCatchException(
            function() use ($input)
            {
                $this->createPaymentMetaEntity($input);
            },
            BadRequestValidationFailureException::class,
            'The mismatch amount reason field is required when mismatch amount is present.');

    }

    public function testMismatchAmountWithInvalidReason()
    {
        $input = [
            'payment_id'                => 'GON008vWuvOlwq',
            'mismatch_amount'           => 1,
            'mismatch_amount_reason'    => 'invalid_reason',
        ];

        $this->makeRequestAndCatchException(
            function() use ($input)
            {
                $this->createPaymentMetaEntity($input);
            },
            BadRequestValidationFailureException::class,
            'The selected mismatch amount reason is invalid.');

    }


    public function testMismatchAmountWithValidReason()
    {
        $input = [
            'payment_id'                    => 'GON008vWuvOlwq',
            'mismatch_amount'               => 1,
            'mismatch_amount_reason'        => 'credit_deficit',
        ];

        $this->createPaymentMetaEntity($input);
    }

    private function createPaymentMetaEntity($input)
    {
        (new PaymentMeta\Core)->create($input);
    }

}
