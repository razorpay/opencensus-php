<?php

namespace RZP\Tests\Functional\BankingAccountStatement;

use Mail;
use Mockery;
use RZP\Services\Mozart;
use RZP\Tests\Functional\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\External\Entity as ExternalEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\BankingAccountStatement\Entity as BasEntity;

class RblBankingAccountStatementTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    const UFH_FILE_PATH_REGEX    = '/.*\/ufh\/file\/(.*)/';

    const MOCK_UFH_BASE_LOCATION = 'files/filestore';

    const FILE_ID                = 'file_id';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RblBankingAccountStatementTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $this->fixtures->balance->edit($this->balance->getId(),
            ['balance' => 10000, 'account_type' => 'direct', 'channel' => 'rbl']);
    }

    public function testRblXlsxStatementGeneration()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);

        $this->assertArrayHasKey(self::FILE_ID, $response);

        $this->verifyGeneratedXlsxFile($currentTime);

    }

    public function testRblXlsxStatementEmailSent()
    {
        Mail::fake();

        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);

        Mail::assertQueued(StatementMail::class);
    }

    /**
     * Case where the response from RBL is success
     */
    public function testRblAccountStatementCase1()
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $this->startTest();

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $txnEntity->toArray();

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 11355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL               => 'rbl',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($basExpected, $basActual, true);

        $externalExpected = [
            BasEntity::MERCHANT_ID                => $basActual[BasEntity::MERCHANT_ID],
            ExternalEntity::BALANCE_ID            => $this->balance->getId(),
            ExternalEntity::BANK_REFERENCE_NUMBER => $basActual[BasEntity::BANK_TRANSACTION_ID],
            ExternalEntity::TYPE                  => $basActual[BasEntity::TYPE],
            ExternalEntity::AMOUNT                => $basActual[BasEntity::AMOUNT],
            ExternalEntity::CHANNEL               => $basActual[BasEntity::CHANNEL],
            ExternalEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($externalExpected, $externalActual, true);

        $txnExpected = [
            TransactionEntity::ID               => $externalTxnId,
            TransactionEntity::ENTITY_ID        => $externalId,
            TransactionEntity::TYPE             => 'external',
            TransactionEntity::DEBIT            => $externalActual[ExternalEntity::AMOUNT],
            TransactionEntity::CREDIT           => 0,
            TransactionEntity::AMOUNT           => $externalActual[ExternalEntity::AMOUNT],
            TransactionEntity::FEE              => 0,
            TransactionEntity::TAX              => 0,
            TransactionEntity::PRICING_RULE_ID  => null,
            TransactionEntity::ON_HOLD          => false,
            TransactionEntity::SETTLED          => false,
            TransactionEntity::SETTLED_AT       => null,
            TransactionEntity::SETTLEMENT_ID    => null,
        ];

        $this->assertArraySubset($txnExpected, $txnActual, true);
    }

    /**
     * Case where the no more data is received from RBL
     **/
    public function testRblAccountStatementCase2()
    {
        $mockedResponse = $this->getRblNoDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $basBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $this->ba->appAuth();

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $this->assertEquals($basBeforeTest[BasEntity::ID], $basAfterTest[BasEntity::ID]);
    }

    /**
     * Case where request details are incorrect to RBL
     */
    public function testRblAccountStatementCase3()
    {
        $mockedResponse = $this->getRblInvalidDetailsResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $this->startTest();
    }

    /**
     * Case where request fails at mozart
     */
    public function testRblAccountStatementCase4()
    {
        $mockedResponse = $this->getMozartServiceFailureResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $this->startTest();
    }

    /**
     * Case where response is malformed
     */
    public function testRblAccountStatementCase5()
    {
        $mockedResponse = $this->getRblMalformedResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $this->startTest();
    }

    /**
     * Case where the balances don't match
     */
    public function testRblAccountStatementCase6()
    {
        $mockedResponse = $this->getRblIncorrectBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $this->startTest();
    }

    /**
     * Case where the balances is negative
     */
    public function testRblAccountStatementCase7()
    {
        $mockedResponse = $this->getRblNegativeBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $this->startTest();
    }

    protected function verifyGeneratedXlsxFile($currentTime)
    {
        $openingBalanceCell = 'B34';

        $closingBalanceCell = 'B35';

        $effectiveBalanceCell = 'B36';

        $expectedOpeningBalance = 113.55;

        $expectedClosingBalance = 214.5;

        $expectedEffectiveBalance = 214.5;

        $fileName = storage_path(self::MOCK_UFH_BASE_LOCATION) .
                    '/2224440041626905_946684800_' .
                    $currentTime .
                    '.xlsx';

        $spreadsheet = IOFactory::load($fileName);

        $activeSheet = $spreadsheet->getActiveSheet();

        $openingBalance = $activeSheet->getCell($openingBalanceCell)->getValue();

        $closingBalance = $activeSheet->getCell($closingBalanceCell)->getValue();

        $effectiveBalance = $activeSheet->getCell($effectiveBalanceCell)->getValue();

        $this->assertEquals($expectedOpeningBalance , $openingBalance);

        $this->assertEquals($expectedClosingBalance , $closingBalance);

        $this->assertEquals($expectedEffectiveBalance , $effectiveBalance);
    }

    protected function getRblDataResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '114.50',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'DEBIT CARD ANNUAL FEE 2635',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '214.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.95',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => 'Loan Recovery For 809000520619',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '113.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807068',
                                'txnSrlNo' => '  49',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                        ]
                    ],
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '',
                        'Error_Desc' => '',
                        'Status' => 'SUCCESS',
                        'TranID' => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => 'bjt1l8jc1osqk0jtadrg',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    protected function getRblNoDataResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N'
                    ],
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '8504',
                        'Error_Desc' => 'No record could be retrieved',
                        'Status' => 'FAILED',
                        'TranID' => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ]
            ],
            'error' => [
                'description' => 'No record could be retrieved',
                'gateway_error_code' => '8504',
                'gateway_error_description' => 'No record could be retrieved',
                'gateway_status_code' => 200,
                'internal_error_code' => 'NO_DATA_FOUND'
            ],
            'external_trace_id' => '',
            'mozart_id' => 'bk0u6h3c1osgdo154fh0',
            'next' => [],
            'success' => false
        ];

        return $response;
    }

    protected function getRblGatewayErrorResponse()
    {
        $response = [
            'httpCode' => '401',
            'httpMessage' => 'Unauthorized',
            'moreInformation' => 'Client id in wrong location.'
        ];

        return $response;
    }

    protected function getRblInvalidDetailsResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => 'ER034',
                        'Error_Desc' => 'Request not valid for the given Account Number',
                        'Status' => 'FAILED',
                        'TranID' => 'A1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => [
                'description' => 'Request not valid for the given Account Number',
                'gateway_error_code' => 'ER034',
                'gateway_error_description' => 'Request not valid for the given Account Number',
                'gateway_status_code' => 200,
                'internal_error_code' => 'VALIDATION_ERROR'
            ],
            'external_trace_id' => '',
            'mozart_id' => 'bk1mej3c1osssas3oghg',
            'next' => [],
            'success' => false
        ];

        return $response;
    }

    protected function getMozartServiceFailureResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => 'ER034',
                        'Error_Desc' => 'Request not valid for the given Account Number',
                        'Status' => 'FAILED',
                        'TranID' => 'A1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => [
                'description' => 'Request not valid for the given Account Number',
                'gateway_error_code' => 'ER034',
                'gateway_error_description' => 'Request not valid for the given Account Number',
                'gateway_status_code' => 200,
                'internal_error_code' => 'VALIDATION_ERROR'
            ],
            'external_trace_id' => '',
            'mozart_id' => 'bk1mej3c1osssas3oghg',
            'next' => [],
            'success' => false
        ];

        return $response;
    }

    protected function getRblMalformedResponse()
    {
        $response = $this->getRblDataResponse();

        unset($response['data']['PayGenRes']['Body']['transactionDetails'][0]['pstdDate']);

        return $response;
    }

    protected function getRblIncorrectBalanceResponse()
    {
        $response = $this->getRblDataResponse();

        $response['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '20.00';

        return $response;
    }

    protected function getRblNegativeBalanceResponse()
    {
        $response = $this->getRblDataResponse();

        $txn = $response['data']['PayGenRes']['Body']['transactionDetails'][1];

        $txn['transactionSummary']['txnAmt']['amountValue'] = '221.00';
        $txn['txnBalance']['amountValue'] = '-3.50';

        $response['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn;

        return $response;
    }

    protected function setMozartMockResponse($mockedResponse)
    {
        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive([
            'sendRawRequest' => json_encode($mockedResponse)
        ]);

        $this->app->instance('mozart', $mock);
    }

    protected function addTestTransactions(): void
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $request = $this->testData['testRblAccountStatementCase1']['request'];

        $this->sendRequest($request);

        $this->ba->proxyAuth();
    }
}
