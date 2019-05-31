<?php

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BankingAccountTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BankingAccountTestData.php';

        parent::setUp();

        // storing the below in redis for purpose of test cases.
        $pincodeList = ['560030', '560034'];

        $this->app['redis']->sadd('rbl_pincode_set', $pincodeList);
    }

    public function testCreateBankingAccount()
    {
         $this->ba->proxyAuth();

         sd($this->startTest());
    }

    public function testCreateBankingAccountWithUnserviceablePincode()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateBankingAccountWithInvalidBank()
    {
        $this->ba->proxyAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function(){
            $this->createBankingAccount([
                Entity::BANK => 'TEST'
            ]);
        });
    }

    public function testCreateBankingAccountWithEmptyPincode()
    {
        $this->ba->proxyAuth();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function(){
            $this->createBankingAccount([
                Entity::PINCODE => ''
            ]);
        });
    }
    protected function createBankingAccount(array $attributes = [])
    {
        $data = [
            Entity::PINCODE => '560030',
            Entity::BANK    => 'rbl'
        ];

        $data = array_merge($data, $attributes);

        $request = [
            'method'    => 'post',
            'url'       => '/banking_account',
            'content'   => $data
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}
