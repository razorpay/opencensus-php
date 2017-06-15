<?php

namespace RZP\Tests\Functional\BankTransfer;

use Redis;
use Mockery;
use Closure;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

class BankTransferTest extends TestCase
{
    use EntityActionTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->ba->privateAuth();

        $this->bankAccount = $this->createVirtualAccount();

        $vvsSecret = \Config::get('applications.vvs.secret');

        $this->ba->appAuth('rzp_test', $vvsSecret);
    }

    public function testBankTransferProcess()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('pay_'.$bankTransfer['payment_id'], $payment['id']);
    }

    public function testBankTransferProcessDuplicateUtr()
    {
        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is an expected one
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Payment is automatically captured
        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('pay_'.$bankTransfer['payment_id'], $payment['id']);

        $utr = $response['transaction_id'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        // Created bank transfer is not expected, not linked to a payment
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(false, $bankTransfer['expected']);
        $this->assertNull($bankTransfer['payment_id']);
    }

    public function testBankTransferProcessInvalidAccount()
    {
        $accountNumber = 'RAZORPINVALIDACCOUNT';
        $ifsc = $this->bankAccount['ifsc'];

        // Process API always returns true
        $response = $this->processBankTransfer($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);
    }

    public function testBankTransferProcessFailure()
    {
        $this->startTest();
    }

    public function testBankTransferNotify()
    {
        $this->testBankTransferProcess();

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Created bank transfer is an expected one, but initially not marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(false, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Notify API always returns true
        $response = $this->notifyBankTransfer($accountNumber, $ifsc, $bankTransfer[E::UTR]);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        // Bank transfer is now marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(true, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);
    }

    public function testBankTransferNotifyAgain()
    {
        $this->testBankTransferProcess();

        $accountNumber = $this->bankAccount['account_number'];
        $ifsc = $this->bankAccount['ifsc'];

        // Created bank transfer is an expected one, but initially not marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(false, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);

        $utr = $bankTransfer[E::UTR];

        // Notify API always returns true
        $response = $this->notifyBankTransfer($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        // Bank transfer is now marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(true, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);

        // Notify API still returns true
        $response = $this->notifyBankTransfer($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        // Bank transfer is still marked as notified
        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);
        $this->assertEquals(true, $bankTransfer['expected']);
        $this->assertEquals(true, $bankTransfer['notified']);
        $this->assertNotNull($bankTransfer['payment_id']);
    }

    public function testBankTransferNotifyFailure()
    {
        $this->startTest();
    }

    protected function createVirtualAccount()
    {
        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        return $response['bank_account'];
    }

    protected function processBankTransfer($accountNumber, $ifsc, $utr = null)
    {
        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);
    }

    protected function notifyBankTransfer($accountNumber, $ifsc, $utr = null)
    {
        return $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);
    }

    protected function processOrNotifyBankTransfer($accountNumber, $ifsc, $utr)
    {
        $request = $this->testData[__FUNCTION__];

        $name = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        $request['url'] = $this->testData[$name]['url'];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        if (isset($utr) === false)
        {
            $utr = 'utr_'.rand(10000000,99999999);
        }

        $request['content']['transaction_id'] = $utr;

        $vvsSecret = \Config::get('applications.vvs.secret');

        $this->ba->appAuth('rzp_test', $vvsSecret);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        return $response;
    }
}
