<?php

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\BankingAccount\AccountType;
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

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());
    }

    public function testCreateBankingAccountTwiceForSameMerchant()
    {
        $testData = $this->testData['testCreateBankingAccount'];

        $bankingAccount = $this->startTest($testData);

        $bankingAccountTwo = $this->startTest($testData);

        $this->assertEquals($bankingAccount['id'], $bankingAccountTwo['id']);
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

    public function testSuccessBankAccountInfoNotification(string $id = null)
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '1cXSLlUU8V9sXl',
            ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = '1cXSLlUU8V9sXl';

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->testCreateBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status' => 'initiated',
            ]);

        $this->assertEquals('created', $bankingAccount->getStatus());

        $this->ba->privateAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber()
                        ]
                    ]
                ]
            ]
        ];

        return $this->startTest($dataToReplace);
    }

    public function testAccountInfoWebhookWithIncorrectAndThenCorrectDetails()
    {
        $response = $this->testFailedBankAccountInfoNotification();

        $this->assertEquals('Failure', $response['RZPAlertNotiRes']['Body']['Status']);

        $response = $this->testSuccessBankAccountInfoNotification();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('processed', $bankingAccount->getStatus());

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);
    }

    public function testFailedBankAccountInfoNotification()
    {
        $this->ba->privateAuth('rzp_test', 'RANDOM_RBL_SECRET');

        return $this->startTest();
    }

    public function testUpdateAccountInfoWebhookInternally()
    {
        $this->ba->proxyAuth();

        $this->testCreateBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status' => 'initiated',
            ]);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber()
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testDoubleAccountOpeningWebhooks()
    {
        $this->testSuccessBankAccountInfoNotification();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->privateAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);

        // we are asserting that the values passed in second webhook will not be updated
        // as the first webhook is processed.
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEquals($bankingAccount['account_number'], 31900299180853);
    }

    public function testActivate()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' .  $merchantDetail->merchant['id']);

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->setupDataForActivation($bankingAccount);

        $dataToReplace = [
          'request' => [
              'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
          ]
        ];

        $this->mockFundAccountService();

        $this->mockCardVault(function ()
        {
            return [
                    'success' => true,
                    'token'   => 'random'
            ];
        });

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
                                                                    Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::ACTIVATED, $bankingAccount['status']);

        $balance = $this->getDbLastEntity('balance');

        $this->assertEquals('rbl', $balance[RZP\Models\Merchant\Balance\Entity::CHANNEL]);

        $this->assertEquals('direct', $balance[RZP\Models\Merchant\Balance\Entity::ACCOUNT_TYPE]);

        $this->assertEquals($balance[RZP\Models\Merchant\Balance\Entity::ID],
                            $bankingAccount[RZP\Models\BankingAccount\Entity::BALANCE_ID]);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);
    }

    public function testActivateFailedDueToFtsFailure()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' .  $merchantDetail->merchant['id']);

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->setupDataForActivation($bankingAccount);

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
            Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->mockFundAccountService(function ()
        {
            throw new \Exception();
        });

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testActivateFailedDueToMissingData()
    {
        $this->ba->proxyAuth();

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $this->mockCardVault(function ()
        {
            return [];
        });

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccount()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchantDetail->merchant['id'],
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountStatusAsProcessed()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchantDetail->merchant['id'],
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'],
            [
                'status' => 'initiated',
            ]);

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountStatusAsProcessedFailed()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'initiated',
            ]);

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountIncorrectCurrentToPreviousStatus()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountToUnserviceable()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'processing',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::UNSERVICEABLE, $bankingAccount->getStatus());
    }

    public function testUpdateBankingAccountToInitiated()
    {
        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::INITIATED, $bankingAccount->getStatus());
    }

    public function testUpdateBankingAccountToInitiatedWithInternalComments()
    {
        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::INITIATED, $bankingAccount->getStatus());
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

    public function testBankingAccountFetch()
    {
        $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForAccountNumber()
    {
        $response = $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account', $response['id'], [
            'account_number' => '1234567808',
        ]);

        $this->startTest();
    }

    public function testBankingAccountFetchForCurrentAccount()
    {
        $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchBankingAccountRequests()
    {
        $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForCurrentAccountFailure()
    {
        $response = $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $response['id'],
            [
                'account_type' => 'virtual',
            ]);
    }

    public function testFetchBankingAccountsOfCreatedStatus()
    {
        $response = $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $response['id'],
            [
                'status' => 'created',
            ]);

        $this->startTest();
    }

    public function testUpdatedStatusFromCreatedToCancelled()
    {
        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::CANCELLED, $bankingAccount->getStatus());
    }

    public function testUpdatedStatusFromProcessingToRejected()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'processing',
            ]);


        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::REJECTED, $bankingAccount->getStatus());
    }

    public function testUpdateBankingAccountDetails()
    {
        $this->testUpdateBankingAccountStatusAsProcessed();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->adminAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount->getId(),
                'method'  => 'PATCH',

            ],
        ];

        $this->startTest($dataToReplace);
/*
        $request = [
            'url'       => '/banking_accounts/' . 'bacc_' . $bankingAccount->getId(),
            'method'    => 'GET',
            'content'   => [
                'expand' => ['merchant','merchant.merchantDetail', 'banking_account_details'],
            ]
        ];

        $this->ba->proxyAuth();

        sd($this->sendRequest($request));*/
    }

    public function testUpdateBankingAccountDetailsWithOverride()
    {
        $this->testUpdateBankingAccountDetails();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->adminAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount->getId(),
                'method'  => 'PATCH',
            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccountDetails = $this->getDbLastEntity('banking_account_detail');

        $this->assertEquals(base64_encode('api_key_two'), $bankingAccountDetails['gqateway_value']);
    }

    protected function setMozartMockResponse($mockedResponse)
    {
        $mock = Mockery::mock(Mozart::class)->makePartial();

        $mock->shouldReceive([
            'sendMozartRequest' => $mockedResponse
        ]);

        $this->app->instance('mozart', $mock);
    }

    protected function getMozartMockedResponse(string $key)
    {
        return $this->testData[$key];
    }

    protected function setupDataForActivation($bankingAccount)
    {
        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'account_type'          => 'current',
            'account_ifsc'          => 'YESB000198',
            'beneficiary_name'      => 'abc',
            'beneficiary_mobile'    => '9999999999',
            'beneficiary_email'     => 'aa@abc.com',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
            'reference2'            => 'RANDOM_STRING',
            'reference3'            => 'RANDOM_STRING',
        ]);

        $attributes = [
            [
                'id'                 => 'badetail000000',
                'banking_account_id' => $bankingAccount->getId(),
                'gateway_key'        => 'client_id',
                'gateway_value'      => '123zz',
            ],
            [
                'id'                 => 'badetail000001',
                'banking_account_id' => $bankingAccount->getId(),
                'gateway_key'        => 'client_secret',
                'gateway_value'      => '123zz',
            ]
        ];

        $this->fixtures->create('banking_account_detail', $attributes[0]);
        $this->fixtures->create('banking_account_detail', $attributes[1]);

    }
}
