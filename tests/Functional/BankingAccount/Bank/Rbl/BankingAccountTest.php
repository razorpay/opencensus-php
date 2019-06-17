<?php

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BankingAccountTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BankingAccountTestData.php';

        parent::setUp();

        // storing the below in redis for purpose of test cases.
        $pincodeList = ['560030', '560034'];

        $this->app['redis']->sadd('rbl_pincode_set', $pincodeList);

        $this->ba->proxyAuth();
    }

    public function testCreateBankingAccount()
    {
        $this->startTest();
    }

    public function testCreateBankingAccountWithUnserviceablePincode()
    {
        $this->startTest();
    }

    public function testCreateBankingAccountWithInvalidBank()
    {
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->createBankingAccount([Entity::CHANNEL => 'TEST']);
        });
    }

    public function testCreateBankingAccountWithEmptyPincode()
    {
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->createBankingAccount([Entity::PINCODE => '']);
        });
    }

    public function testSuccessBankAccountInfoNotification()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->fixtures->merchant = $merchantDetail->merchant;

        $this->ba->proxyAuth('rzp_test_' . $this->fixtures->merchant['id']);

        $this->testCreateBankingAccount();

        $this->ba->privateAuth('rzp_test', 'rbl_secret');

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'REF_NUM_1' => $bankingAccount->getBankReferenceNumber()
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testFailedBankAccountInfoNotification()
    {
        $this->ba->proxyAuth();

        $this->ba->privateAuth('rzp_test', 'rbl_secret');

        $this->startTest();
    }

    protected function createBankingAccount(array $attributes = [])
    {
        $data = [
            Entity::PINCODE => '560030',
            Entity::CHANNEL => 'rbl'
        ];

        $data = array_merge($data, $attributes);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts',
            'content' => $data
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}
