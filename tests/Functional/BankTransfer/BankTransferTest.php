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

        $this->ba->privateAuth();

        $this->bankAccount = $this->createVirtualAccount();

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');

        $gavaskarSecret = \Config::get('applications.gavaskar.secret');

        $this->ba->appAuth('rzp_test', $gavaskarSecret);
    }

    public function testBankTransferValidate()
    {
        $accountNumber = $this->bankAccount['account_number'];

        $ifsc = $this->bankAccount['ifsc'];

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

        $ifsc = $this->bankAccount['ifsc'];

        $response = $this->validateBankAccount($accountNumber, $ifsc);

        $utr = $response['transaction_id'];

        $response = $this->validateBankAccount($accountNumber, $ifsc, $utr);
        $this->assertEquals(false, $response['valid']);
        $this->assertEquals('Duplicate UTR received', $response['message']);
    }

    public function testBankTransferValidateFalse()
    {
        $accountNumber = 'RAZORPINVALIDACCOUNT';

        $ifsc = $this->bankAccount['ifsc'];

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

        $ifsc = $this->bankAccount['ifsc'];

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

        $ifsc = $this->bankAccount['ifsc'];

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

    protected function createVirtualAccount()
    {
        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        return $response['bank_account'];
    }

    protected function createVirtualAccountForCustomer($customer)
    {
        $request = $this->testData['createVirtualAccount'];

        $request['content']['customer_id'] = $customer['id'];

        $response = $this->makeRequestAndGetContent($request);

        return $response['bank_account'];
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

        $request['content']['transaction_id'] = $utr;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        return $response;
    }
}
