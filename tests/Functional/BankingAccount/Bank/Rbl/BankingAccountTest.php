<?php

use RZP\Models\Contact;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Mail;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\BankingAccount\AccountType;
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\BankingAccount\StatusNotifications\Created;
use RZP\Mail\BankingAccount\StatusNotifications\Processed;
use RZP\Mail\BankingAccount\StatusNotifications\Cancelled;
use RZP\Mail\BankingAccount\StatusNotifications\Processing;
use RZP\Mail\BankingAccount\StatusNotifications\Unserviceable;

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
        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        Mail::assertQueued(XProActivation::class);
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

        $response = $this->startTest($dataToReplace);

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('processed', $logs['items'][1]['status']);
        $this->assertEquals('closed', $logs['items'][1]['bank_status']);

        return $response;
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

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
        ]);

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

        $request  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('activated', $logs['items'][1]['status']);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);
        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $contact = $this->getDbLastEntity('contact')->toArray();

        $this->assertEquals($contact['type'], Contact\Type::RZP_FEES);
        $this->assertEquals($contact['active'], true);
        $this->assertEquals($contact['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($contact['name'],  config('banking_account.razorpayx_fee_details.name'));

        $fundAccount = $this->getDbLastEntity('fund_account')->toArray();

        $this->assertEquals($fundAccount['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($fundAccount['source_type'], 'contact');
        $this->assertEquals($fundAccount['source_id'], $contact['id']);
        $this->assertEquals($fundAccount['active'], true);

        $account = $this->getDbLastEntity('bank_account')->toArray();

        $this->assertEquals($account['account_number'], config('banking_account.razorpayx_fee_details.account_number'));
        $this->assertEquals($account['name'], config('banking_account.razorpayx_fee_details.name'));
        $this->assertEquals($account['ifsc'], config('banking_account.razorpayx_fee_details.ifsc'));
        $this->assertEquals($account['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($account['entity_id'], $contact['id']);
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
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

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
        $attribute = ['activation_status' => 'processed'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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

        $request  = [
            'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('initiated', $logs['items'][1]['status']);
    }

    public function testUpdateBankingAccountStatusAsProcessed()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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
                'status' => 'picked',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'picked',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::INITIATED, $bankingAccount->getStatus());
    }

    public function testUpdateBankingAccountToPicked()
    {
        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PICKED, $bankingAccount->getStatus());
    }

    public function testUpdateBankingAccountToInitiatedWithInternalComments()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'picked',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
        ];
        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchBankingAccountRequests()
    {
        $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
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
        Mail::fake();

        $response = $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
        ];
        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $response['id'],
            [
                'status' => 'created',
            ]);

        $this->startTest();

        Mail::assertQueued(Created::class);
    }

    public function testUpdatedStatusFromCreatedToPicked()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PICKED, $bankingAccount->getStatus());
    }

    public function testUpdatedStatusFromCreatedToCancelled()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::CANCELLED, $bankingAccount->getStatus());

        Mail::assertQueued(Cancelled::class);
    }

    public function testUpdatedStatusFromInitiatedToProcessing()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => 'initiated',
                              ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PROCESSING, $bankingAccount->getStatus());

        Mail::assertQueued(Processing::class);
    }

    public function testUpdatedStatusFromProcessingToProcessed()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => 'processing',
                              ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PROCESSED, $bankingAccount->getStatus());

        Mail::assertQueued(Processed::class);
    }

    public function testUpdateOnDiffBankInternalStatus()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'] ,
            [
                'status' => 'picked',
            ]);

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'status' => 'initiated',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'status' => 'processing',
                ]
        ];

        $this->makeRequestAndGetContent($request);

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'status'               => 'processing',
                'bank_internal_status' => 'open'
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getLastEntity('banking_account', true);

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('initiated', $logs['items'][1]['status']);
        $this->assertEquals('processing', $logs['items'][2]['status']);
        $this->assertNull($logs['items'][2]['bank_status']);
        $this->assertEquals('processing', $logs['items'][3]['status']);
        $this->assertEquals('open', $logs['items'][3]['bank_status']);
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
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
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

        $request = [
            'url'       => '/admin/banking_account/' . 'bacc_' . $bankingAccount->getId(),
            'method'    => 'GET',
            'content'   => [
                'expand' => ['banking_account_details'],
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->sendRequest($request);

        $response = json_decode($response->getContent(), true);

        $actualDetails = $response['banking_account_details']['items'];

        $expectedDetails = [
            [
                'gateway_key'   => 'client_secret',
                'gateway_value' => 'YXBpX3NlY3JldA==',
            ],
            [
                'gateway_key'   => 'client_id',
                'gateway_value' => 'api_key',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedDetails, $actualDetails);
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

        $this->assertEquals('api_key_two', $bankingAccountDetails['gateway_value']);
    }

    public function testBankingAccountFetchOnProxyAuth()
    {
        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $xBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '1234567808',
                'balance'           => 100000,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
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
            'beneficiary_address1'  => 'blr1',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
        ]);

        $attributes = [
            [
                'id'                 => 'badetail000000',
                'banking_account_id' => $bankingAccount->getId(),
                'gateway_key'        => 'client_id',
                'gateway_value'      => '123zz',
                'merchant_id'        => '10000000000000',
            ],
            [
                'id'                 => 'badetail000001',
                'banking_account_id' => $bankingAccount->getId(),
                'gateway_key'        => 'client_secret',
                'gateway_value'      => '123zz',
                'merchant_id'        => '10000000000000',
            ]
        ];

        $this->fixtures->create('banking_account_detail', $attributes[0]);
        $this->fixtures->create('banking_account_detail', $attributes[1]);

    }
}
