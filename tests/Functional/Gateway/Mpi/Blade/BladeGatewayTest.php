<?php

namespace RZP\Tests\Functional\Gateway\Mpi\Blade;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class BladeGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/BladeGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_blade_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mpi_blade';
    }

    public function testSuccessful13DigitPanForEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(1, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals('1000BladeTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);

        $this->assertArraySelectiveEquals(
            $this->testData['testSuccessful13DigitPanTxn'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $mpi['gateway']);
    }

    public function testSuccessful13DigitPanForNonEnrolledCard()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_NOT_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(1, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals('1000BladeTrmnl', $payment['terminal_id']);

        $payment = $this->capturePayment($payment['public_id'], $payment['amount']);

        $txn = $this->getLastTransaction(true);

        $this->assertArraySelectiveEquals(
            $this->testData['testSuccessful13DigitPanTxn'], $txn);

        $payment = $this->getLastEntity('payment', true);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $mpi['gateway']);
    }

    public function testEnrolledUSCardWithSkipInternationalAuth()
    {
        $this->fixtures->create('iin', [
            'iin' => 556763,
            'network' => 'MasterCard',
            'type' => 'credit',
            'country' => 'US'
        ]);

        $this->fixtures->merchant->addFeatures('skip_international_auth');
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(1, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals('1000BladeTrmnl', $payment['terminal_id']);
        $this->assertEquals('not_applicable', $payment['two_factor_auth']);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNull($mpi);
    }

    public function testEnrolledINCardWithAuth()
    {
        $this->fixtures->create('iin', [
            'iin' => 556763,
            'network' => 'MasterCard',
            'type' => 'credit',
            'country' => 'US'
        ]);

        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $txn = $this->getEntities('transaction', [], true);
        $this->assertEquals(1, $txn['count']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertNotNull($payment['transaction_id']);
        $this->assertEquals('1000BladeTrmnl', $payment['terminal_id']);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertNotNull($mpi);
        $this->assertEquals('Y', $mpi['enrolled']);
    }

    public function testInvalidMessage()
    {
        $this->runRequestResponseFlow(
            $data = $this->testData['testInvalidMessage'],
            function()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::INVALID_MEESAGE,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['verify_at']);

        $this->assertNull($payment['verify_bucket']);
    }

    public function testInvalidPares()
    {
        $this->mockSignatureNotFound();

        $this->runRequestResponseFlow(
            $data = $this->testData['testInvalidMessage'],
            function()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::INVALID_PARES,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });
    }

    public function testSignatureMissingFromValidPARes()
    {
        $this->markTestSkipped(
            'This is old flow . IT is migrated to card payment service'
        );

        $this->mockSignatureNotFound();

        $this->runRequestResponseFlow(
            $data = $this->testData['testSignatureMissing'],
            function()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::VALID_ENROLL_NUMBER,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });
    }

    public function testBlankMessage()
    {
        $this->markTestSkipped(
            'This is old flow . IT is migrated to card payment service'
        );

        $this->runRequestResponseFlow(
            $data = $this->testData['testBlankMessage'],
            function()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::BLANK_MEESAGE,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });
    }

    public function testInvalidVersion()
    {
        $this->runRequestResponseFlow(
            $data = $this->testData['testInvalidVersion'],
            function()
            {
                $payment = $this->defaultAuthPayment([
                    'card' => [
                        'number'       => CardNumber::INVALID_VERSION,
                        'expiry_month' => '02',
                        'expiry_year'  => '35',
                        'cvv'          => 123,
                        'name'         => 'Test Card'
                    ]
                ]);
            });
    }

    public function testElementIreqCodeFollowsVendorCode()
    {
        $response = $this->getIreqCodeFollowsVendorCode();

        $this->mockIReqCode($response);

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_NOT_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $mpi['gateway']);
    }

    public function testBlankIreq()
    {
        $response = $this->getBlankIReq();

        $this->mockIReqCode($response);

        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'       => CardNumber::VALID_NOT_ENROLL_NUMBER,
                'expiry_month' => '02',
                'expiry_year'  => '35',
                'cvv'          => 123,
                'name'         => 'Test Card'
            ]
        ]);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $mpi['gateway']);
    }

    public function testInvalidIReqCode()
    {
        $this->markTestSkipped("not being used");
        $response = $this->getInvalidIreqCode();

        $this->mockIReqCode($response);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = CardNumber::VALID_NOT_ENROLL_NUMBER;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });
    }

    protected function mockIReqCode($response)
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($response)
            {
                $content['Message']['VERes']['IReq'] = $response;
            }
        );
    }

    public function mockSignatureNotFound()
    {
        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'acs')
                {
                    unset($content['Message']['Signature']);
                }
            }
        );
    }

    protected function getBlankIReq()
    {
        return [];
    }

    protected function getInvalidIreqCode()
    {
        return [
            'iReqcode'   => 56,
        ];
    }

    protected function getIreqCodeFollowsVendorCode()
    {
        return [
            'vendorCode' => 1000,
            'iReqCode'   => 56,
            'iReqDetail' => 'VEReq.pan',
        ];
    }

    public function testUnknownEnrolledResponse()
    {
        $payment = $this->defaultAuthPayment([
            'card' => [
                'number'        => CardNumber::UNKNOWN_ENROLLED,
                'expiry_month'  => '02',
                'expiry_year'   => '35',
                'cvv'           => 123,
                'name'          => 'Test Card',
                'international' => true
            ]
        ]);

        $mpi = $this->getLastEntity('mpi', true);

        $this->assertEquals('mpi_blade', $mpi['gateway']);
    }
}
