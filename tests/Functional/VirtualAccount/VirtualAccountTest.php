<?php

namespace RZP\Tests\Functional\VirtualAccount;

use Closure;
use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class VirtualAccountTest extends TestCase
{
    use EntityActionTrait;
    use VirtualAccountTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/VirtualAccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->ba->privateAuth();

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');
    }

    public function testCreateVirtualAccount()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $response = $this->createVirtualAccount($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualAccountWithBharatQr()
    {
        $input = [
            'receiver_types'  => 'bharat_qr',
        ];

        $response = $this->createVirtualAccount($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrString = $response['receivers'][0]['qr_string'];

        $tlvArray = $this->getTagMappedValues($qrString);

        $masterCardValue = $tlvArray['04'];

        $visaValue       = $tlvArray['02'];

        assert(16, strlen($masterCardValue));

        assert(16, strlen($visaValue));
    }

    public function testCreateVirtualAccountWithBharatQrWithAmount()
    {
        $input = [
            'receiver_types' => 'bharat_qr',
            'amount_expected' => 10000,
        ];

        $response = $this->createVirtualAccount($input);

        $expectedResponse = $this->testData['testCreateVirtualAccountWithBharatQr'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrString = $response['receivers'][0]['qr_string'];

        $tlvArray = $this->getTagMappedValues($qrString);

        $this->assertEquals($tlvArray['54'], '100.00');
    }

    public function testCreateVirtualAccountWithDescriptor()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $this->createVirtualAccount($input);

        $vba = $this->getLastEntity('bank_account', true);
        // Handle is unsetso default root is used with default handle
        $this->assertRegexp("/RAZORPAY[A-Z0-9]{9}$/", $vba['account_number']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount(['descriptor' => 'desc1234']);
        });

        $this->fixtures->merchant->setHandle('hand');

        $this->createVirtualAccount(['descriptor' => 'desc1234']);

        $vba = $this->getLastEntity('bank_account', true);
        // Handle is set so standard root is used with given handle
        $this->assertEquals("RZRPHANDDESC1234", $vba['account_number']);
    }

    public function testCreateVirtualAccountDescriptorLengths()
    {
        $input = [
            'amount_expected' => 10000,
            'descriptor' => '9chardesc',
        ];

        $this->fixtures->merchant->setHandle('hand');

        $this->createVirtualAccount($input);

        $vba = $this->getLastEntity('bank_account', true);
        $this->assertEquals("RZRPHAND9CHARDESC", $vba['account_number']);

        // Only upto nine chars allows in descriptor
        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount(['descriptor' => '10chardesc']);
        });

        // Shortening handle to 3 characters
        $this->fixtures->merchant->setHandle('han');

        // Now 10 characters are allows
        $this->createVirtualAccount(['descriptor' => '10chardesc']);

        $vba = $this->getLastEntity('bank_account', true);
        // Handle is set so standard root is used with given handle
        $this->assertEquals("RAZRHAN10CHARDESC", $vba['account_number']);
    }

    public function testCreateVirtualAccountWithIdenticalDescriptor()
    {
        $this->fixtures->merchant->setHandle('hand');

        $input = [
            'amount_expected' => 10000,
            'descriptor' => 'samedesc',
        ];

        $this->createVirtualAccount($input);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount(['descriptor' => 'samedesc']);
        });
    }

    public function testCreateVirtualAccountWithIdenticalDescriptorAfterClosing()
    {
        $this->fixtures->merchant->setHandle('hand');

        $input = [
            'amount_expected' => 10000,
            'descriptor' => 'samedesc',
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $this->closeVirtualAccount($virtualAccount['id']);

        $this->createVirtualAccount(['descriptor' => 'samedesc']);
    }

    public function testFetchVirtualAccount()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $response = $this->createVirtualAccount($input);

        $response = $this->fetchVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccounts()
    {
        $input = [
            'amount_expected' => 10000,
            'name'            => 'First VA'
        ];

        $this->createVirtualAccount($input);

        $input = [
            'amount_expected' => 10000,
            'name'            => 'Second VA'
        ];
        $this->createVirtualAccount($input);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testEditVirtualAccount()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $response = $this->closeVirtualAccount($virtualAccount['id']);

        $this->assertEquals('closed', $response['status']);
    }

    public function testVirtualAccountPay()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $response = $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(5000, $virtualAccount['amount_paid']);
        $this->assertEquals('active', $virtualAccount['status']);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(10000, $virtualAccount['amount_paid']);
        $this->assertEquals('paid', $virtualAccount['status']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);
    }

    public function testVirtualAccountExcess()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $response = $this->payVirtualAccount($virtualAccount['id'], ['amount' => 110]);

        // Account is paid in excess
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(11000, $virtualAccount['amount_paid']);
        $this->assertEquals('paid', $virtualAccount['status']);

        $response = $this->refundVirtualAccountExcessPayments();

        // Payment is partially refunded
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(11000, $payment['amount']);
        $this->assertEquals(1000, $payment['amount_refunded']);

        // Refund is created
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1000, $refund['amount']);
    }

    public function testFetchPaymentsForVirtualAccount()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testVirtualAccountForCustomer()
    {
        $input = [
            'amount_expected' => 10000,
            'customer_id' => 'cust_100000customer',
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $this->assertEquals('cust_100000customer', $virtualAccount['customer_id']);

        $this->payVirtualAccount($virtualAccount['id']);

        $payment = $this->getLastEntity('payment', true);

        $customer = $this->getEntityById('customer', 'cust_100000customer', true);

        $this->assertEquals($customer['id'], $payment['customer_id']);
        $this->assertEquals($customer['email'], $payment['email']);
        $this->assertStringEndsWith($customer['contact'], $payment['contact']);
    }

    public function testWebhookOnVirtualAccountPay()
    {
        $input = [
            'amount_expected' => 10000,
        ];

        $virtualAccount = $this->createVirtualAccount($input);

        $this->createWebhook(
            [
                'events' => [
                    'payment.captured' => '1',
                ]
            ]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertEquals('payment.captured', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->payVirtualAccount($virtualAccount['id']);
    }

    protected function mockInfernoFire(Closure $closure)
    {
        $class = \RZP\Models\Merchant\Webhook\Inferno::class;

        $inferno = Mockery::mock($class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }

    protected function getTagMappedValues(string $qrString)
    {
        $tlvArray = [];

        $length = strlen($qrString);

        $index = 0;

        while ($index < $length)
        {
            $tlvTag = substr($qrString, $index, 2);

            $index += 2;

            $tlvLength = (int) substr($qrString, $index, 2);

            $index +=2;

            $phpArray[$tlvTag] = substr($qrString, $index, $tlvLength);

            $index += $tlvLength;
        }

        return $phpArray;
    }
}
