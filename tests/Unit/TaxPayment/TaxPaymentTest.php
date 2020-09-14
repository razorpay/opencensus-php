<?php

namespace RZP\Tests\Unit\TaxPayment;

use App;
use RZP\Tests\Functional\TestCase;
use RZP\Services\TaxPayments\Service;

class TaxPaymentTest extends TestCase
{
    public function testGetBooleanValueUtility()
    {
        $testCases = [
            'false' => false,
            'true'  => true,
            'False' => false,
            'True'  => true,
            '0'     => false,
            '1'     => true,
        ];

        $taxPaymentService = (new Service(App::getFacadeRoot()));

        foreach ($testCases as $input => $expectedOutput)
        {
            self::assertEquals($expectedOutput, $taxPaymentService->getBooleanValue($input));
        }
    }
}
