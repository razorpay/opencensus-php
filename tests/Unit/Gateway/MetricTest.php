<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Gateway\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment\Entity;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class MetricTest extends TestCase
{
    use DbEntityFetchTrait;

    public function upiPsp()
    {
        $cases = [];

        $case = 'collectWithVpaOkAxis';
        $expected = [
            'action'    => 'authorize',
            'upi_psp'   => 'google_pay'
        ];
        $payment = [
            'vpa'       => 'sample@okaxis',
        ];
        $cases[$case] = ['authorize', $payment, 'upi_mindgate', null, $expected];

        $case = 'collectWithVpaYbl';
        $expected = [
            'action'    => 'authorize',
            'upi_psp'   => 'phonepe'
        ];
        $payment = [
            'vpa'       => 'sample@ybl',
        ];
        $cases[$case] = ['authorize', $payment, 'upi_mindgate', null, $expected];

        $case = 'collectWithVpaPaytm';
        $expected = [
            'action'    => 'authorize',
            'upi_psp'   => 'paytm'
        ];
        $payment = [
            'vpa'       => 'sample@paytm',
        ];
        $cases[$case] = ['authorize', $payment, 'upi_mindgate', null, $expected];

        $case = 'collectWithVpaUpi';
        $expected = [
            'action'    => 'authorize',
            'upi_psp'   => 'bhim'
        ];
        $payment = [
            'vpa'       => 'sample@upi',
        ];
        $cases[$case] = ['authorize', $payment, 'upi_mindgate', null, $expected];

        $case = 'validateVpaWithVpaUpi';
        $expected = [
            'action'    => 'validate_vpa',
            'upi_psp'   => 'bhim'
        ];
        $input = [
            'vpa'       => 'sample@upi',
        ];
        $cases[$case] = ['validate_vpa', $input, 'upi_mindgate', null, $expected];

        return $cases;
    }

    /**
     * @dataProvider upiPsp
     */
    public function testUpiPsp($action, $input, $gateway, $excData, $expected)
    {
        $metric = new Base\Metric();

        if ($action !== 'validate_vpa')
        {
            $payment = $this->fixtures->create('payment:upi_authorized', $input);

            $input = [
                'payment'   => $payment,
            ];
        }
        $input['merchant'] = $this->getDbEntityById('merchant', Merchant\Account::TEST_ACCOUNT);
        $input['terminal'] = $this->getDbEntityById('terminal', '1n25f6uN5S1Z5a');

        $dimensions = $metric->getV2Dimensions($action, $input, $gateway, $excData);

        $this->assertArraySubset($expected, $dimensions);
    }

    public function optimiserDowntime()
    {
        $cases = [];

        $case = 'collectWithUpi';
        $expected = [
            'action'    => 'authorize',
            'procurer'   => 'razorpay'
        ];
        $payment = [
            'method' => 'upi',
        ];
        $cases[$case] = ['authorize', $payment, 'payu', null, $expected,'upi'];

        $case = 'collectWithNetbanking';
        $expected = [
            'action'    => 'authorize',
            'procurer'   => 'razorpay'
        ];
        $payment = [
            'method' => 'netbanking'
        ];
        $cases[$case] = ['authorize', $payment, 'payu', null, $expected,'netbanking'];

        return $cases;
    }

    /**
     * @dataProvider optimiserDowntime
     */
    public function testOptimiserDowntime($action, $input, $gateway, $excData, $expected,$method)
    {
        $metric = new Base\Metric();

             $payment=[];

            switch($method)
            {
                case 'upi':
                     $payment = $this->fixtures->create('payment:upi_authorized', $input);
                     break;
                case 'card':
                    $payment = $this->fixtures->create('payment:card_authorized', $input);
                    break;
                case 'netbanking':
                    $payment = $this->fixtures->create('payment:netbanking_authorized', $input);
                    break;
            }
            $input = [
                'payment'   => $payment,
            ];

        $input['merchant'] = $this->getDbEntityById('merchant', Merchant\Account::TEST_ACCOUNT);

        $input['terminal'] = $this->getDbEntityById('terminal', '1n25f6uN5S1Z5a');

        $dimensions = $metric->getOptimiserDimensions($action, $input, $gateway, $excData);

        $this->assertArraySubset($expected, $dimensions);
    }
}
