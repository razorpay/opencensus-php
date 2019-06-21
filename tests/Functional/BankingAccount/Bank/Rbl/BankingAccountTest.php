<?php

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BankingAccountTest extends TestCase
{
    use PaymentTrait;
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

    public function testStoreMerchantCredentials()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' .  $merchantDetail->merchant['id']);

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace = [
          'request' => [
              'url' => '/banking_accounts/' . $bankingAccount->getId() . '/credentials'
          ]
        ];

        $this->mockCardVault(function ()
        {
            return [
                    'success' => false,
                    'token'   => 'random'
            ];
        });

        $this->startTest($dataToReplace);
    }

    public function testStoreMerchantCredentialsFailed()
    {
        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getId() . '/credentials'
            ]
        ];

        $this->mockCardVault(function ()
        {
            return ['success' => false];
        });

        $this->startTest($dataToReplace);
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
