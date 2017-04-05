<?php

namespace RZP\Tests\Functional\BankTransfer;

use Redis;
use RZP\Models\BankTransfer\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BankTransferTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->ba->proxyAuth();

        $this->bankAccount = $this->createVirtualBankAccount();

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

        $this->ba->appAuth();
    }

    public function testBankTransferValidate()
    {
        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc_code'];

        $response = $this->validateBankAccount($accountNumber, $ifsc);
        $this->assertEquals(true, $response['valid']);
        $this->assertNull($response['message']);

        $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($accountNumber, $bankTransfer['payee_account']);
        $this->assertEquals($ifsc, $bankTransfer['payee_ifsc']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('created', $payment['status']);
    }

    public function testBankTransferValidateDuplicateUtr()
    {
        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc_code'];

        $response = $this->validateBankAccount($accountNumber, $ifsc);

        $utr = $response[E::UTR];

        $response = $this->validateBankAccount($accountNumber, $ifsc, $utr);
        $this->assertEquals(false, $response['valid']);
        $this->assertEquals('Duplicate UTR received', $response['message']);
    }

    public function testBankTransferValidateFalse()
    {
        $accountNumber = 'RAZORPINVALIDACCOUNT';

        $ifsc = $this->bankAccount['ifsc_code'];

        $response = $this->validateBankAccount($accountNumber, $ifsc);
        $this->assertEquals(false, $response['valid']);
        $this->assertEquals('Invalid account number', $response['message']);
    }

    public function testBankTransferValidateFailure()
    {
        $this->startTest();
    }

    public function testBankTransferPay()
    {
        $this->testBankTransferValidate();

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc_code'];

        $transfer =  $this->getLastEntity('bank_transfer', true);

        $response = $this->payBankAccount($accountNumber, $ifsc, $transfer[E::UTR]);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
    }

    public function testBankTransferPayAgain()
    {
        $this->testBankTransferValidate();

        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc_code'];

        $transfer =  $this->getLastEntity('bank_transfer', true);

        $utr = $transfer[E::UTR];

        $response = $this->payBankAccount($accountNumber, $ifsc, $utr);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);

        $response = $this->payBankAccount($accountNumber, $ifsc, $utr);
        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['message']);

        $payment =  $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
    }

    public function testBankTransferPayFailure()
    {
        $this->startTest();
    }

    protected function createVirtualBankAccount()
    {
        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createCustomerVirtualBankAccount($customer)
    {
        $request = $this->testData[__FUNCTION__];

        $request['url'] .= $customer['id'] . '/bank_account';

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function validateBankAccount($accountNumber, $ifsc, $utr = null)
    {
        return $this->validateOrPayBankAccount($accountNumber, $ifsc, $utr);
    }

    protected function payBankAccount($accountNumber, $ifsc, $utr = null)
    {
        return $this->validateOrPayBankAccount($accountNumber, $ifsc, $utr);
    }

    protected function validateOrPayBankAccount($accountNumber, $ifsc, $utr)
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

        $request['content'][E::UTR] = $utr;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response[E::UTR]);

        return $response;
    }
}
