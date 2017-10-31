<?php

namespace RZP\Tests\Functional\VirtualAccount;

use Closure;
use Mockery;
use RZP\Models\Merchant\Webhook;
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

        $this->fixtures->merchant->addFeatures('bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->ba->privateAuth();

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');
    }

    public function testCreateVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualAccountWithBharatQr()
    {
        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
        ]);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrString = $response['receivers'][0]['qr_string'];

        $tlvArray = $this->getTagMappedValues($qrString);

        $masterCardValue = $tlvArray['04'];

        $visaValue = $tlvArray['02'];

        $this->assertEquals(16, strlen($masterCardValue));

        $this->assertEquals(16, strlen($visaValue));

        $masterCardAcquirerCode = substr($masterCardValue, 0, 6);

        $visaAcquirerCode = substr($visaValue, 0, 6);

        $this->assertEquals('470100', $visaAcquirerCode);

        $this->assertEquals('513344', $masterCardAcquirerCode);
    }

    public function testCreateVirtualAccountWithBharatQrWithAmount()
    {
        $response = $this->createVirtualAccount([
            'receiver_types'  => 'qr_code',
            'amount_expected' => 10000,
        ]);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrString = $response['receivers'][0]['qr_string'];

        $tlvArray = $this->getTagMappedValues($qrString);

        $this->assertEquals($tlvArray['54'], '100.00');
    }

    public function testCreateVirtualAccountWithDescriptor()
    {
        $this->createVirtualAccount();

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
        $this->fixtures->merchant->setHandle('hand');

        $this->createVirtualAccount(['descriptor' => '9chardesc']);

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

        $this->createVirtualAccount(['descriptor' => 'samedesc']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount(['descriptor' => 'samedesc']);
        });
    }

    public function testCreateVirtualAccountWithIdenticalDescriptorAfterClosing()
    {
        $this->fixtures->merchant->setHandle('hand');

        $virtualAccount = $this->createVirtualAccount(['descriptor' => 'samedesc']);

        $this->closeVirtualAccount($virtualAccount['id']);

        $this->createVirtualAccount(['descriptor' => 'samedesc']);
    }

    public function testFetchVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $response = $this->fetchVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccounts()
    {
        $this->createVirtualAccount(['name' => 'First VA']);
        $this->createVirtualAccount(['name' => 'Second VA']);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testEditVirtualAccount()
    {
        $virtualAccount = $this->createVirtualAccount();

        $response = $this->closeVirtualAccount($virtualAccount['id']);

        $this->assertEquals('closed', $response['status']);
    }

    public function testVirtualAccountPay()
    {
        $virtualAccount = $this->createVirtualAccount([
            'amount_expected' => 10000,
        ]);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

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
        $virtualAccount = $this->createVirtualAccount([
            'amount_expected' => 10000,
        ]);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 110]);

        // Account is paid in excess
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(11000, $virtualAccount['amount_paid']);
        $this->assertEquals('paid', $virtualAccount['status']);

        $this->refundVirtualAccountExcessPayments();

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
        $virtualAccount = $this->createVirtualAccount();

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 50]);

        $response = $this->fetchVirtualAccountPayments($virtualAccount['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testVirtualAccountForCustomer()
    {
        $virtualAccount = $this->createVirtualAccount([
            'customer_id' => 'cust_100000customer',
        ]);

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
        $virtualAccount = $this->createVirtualAccount();

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
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

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

            $tlvArray[$tlvTag] = substr($qrString, $index, $tlvLength);

            $index += $tlvLength;
        }

        return $tlvArray;
    }
}
