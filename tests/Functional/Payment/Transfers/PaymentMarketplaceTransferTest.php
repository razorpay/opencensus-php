<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;

class PaymentMarketplaceTransferTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentMarketplaceTransferTestData.php';

        parent::setUp();

        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->create('merchant:marketplace_account');

        $this->ba->privateAuth();
    }

    public function testTransferToInvalidOrUnlinkedId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->startTest();
    }

    public function testTransferWithFeatureNotEnabled()
    {
        $this->startTest();
    }

    public function testMultipleTransfersOnSameAccountId()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $testData = $this->testData[__FUNCTION__];

        $this->ba->privateAuth();

        $this->setRequestData($testData['request']);

        $this->sendRequest($testData['request']);

        $this->startTest();

        $transferEntities = $this->getEntities('transfer', [],true);

        $this->assertEquals($this->payment['id'], $transferEntities['items'][0]['source']);
        $this->assertEquals($this->payment['id'], $transferEntities['items'][1]['source']);
    }

    public function testTransferPaymentAmountGreaterThanCaptured()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => $this->payment['amount'] + 1000,
            'currency'=> 'INR',
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->transferPayment($this->payment['id'], $transfers);
        });
    }

    public function testTransferToCustomerAndAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers[1] = [
            'customer'=> 'cust_100000customer',
            'amount'  => 400,
            'currency'=> 'INR',
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->transferPayment($this->payment['id'], $transfers);
        });
    }

    public function testPartialAmountTransfer()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 4000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 4000,
                    'amount_reversed' => 0
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $transferFee = $this->getLastTransactionFee('10000000000001');

        $this->assertEquals(4000 - $transferFee, $this->getAccountBalance('10000000000001'));

        $this->assertArraySelectiveEquals($expected, $content);
    }

    public function testFullTransfer()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);
        $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));

        $oldMarketBalance = $this->getAccountBalance('10000000000000');

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 43000,
            'currency'=> 'INR',
        ];

        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 7000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 2,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 43000,
                    'amount_reversed' => 0
                ],
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000002',
                    'amount'          => 7000,
                    'amount_reversed' => 0
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $this->assertArraySelectiveEquals($expected, $content);

        $transferFee = $this->getLastTransactionFee('10000000000001');
        $this->assertEquals($transfers[0]['amount'] - $transferFee, $this->getAccountBalance('10000000000001'));

        $transferFee = $this->getLastTransactionFee('10000000000002');
        $this->assertEquals($transfers[1]['amount'] - $transferFee, $this->getAccountBalance('10000000000002'));

        $newMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertEquals(50000, $oldMarketBalance - $newMarketBalance);
    }
}
