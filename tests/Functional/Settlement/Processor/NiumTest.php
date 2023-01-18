<?php

namespace RZP\Tests\Functional\Settlement\Processor;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;



class NiumTest extends OAuthTestCase
{
    use FileHandlerTrait;
    use AttemptTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;
    use WorkflowTrait;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';
    const DEFAULT_MERCHANT_ID       = 'DefaultPartner';
    const DEFAULT_SUBMERCHANT_ID    = '10000000000009';

    const DATE_TIME                 = 'Date Time';
    const CLIENT                    = 'Client';
    const ACCOUNT_NUMBER            = 'Account Number';
    const TRANSACTION               = 'Transaction #';
    const LT                        = 'Ledger Type';
    const CURRENCY                  = 'Currency';
    const DR_AMOUNT                 = 'Dr.Amount';
    const CR_AMOUNT                 = 'Cr.Amount';
    const REMARK                    = 'Remark';
    const TXN_ID                    = 'Transaction ID';
    const USD_DR_AMT                = 'USD Dr.Amount';
    const USD_CR_AMT                = 'USD Cr.Amount';
    const STATUS                    = 'status';


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NiumTestData.php';

        parent::setUp();
        $connector = $this->mockSqlConnectorWithReplicaLag(0);
        $this->app->instance('db.connector.mysql', $connector);
        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
        $this->app['config']->set('applications.ufh.mock', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function testNiumFileGeneration()
    {

        // Create Partner Merchant, Partner Config, Sub Merchant and Access Map

        list($partner, $app) = $this->createPartnerAndApplication([
            'partner_type' => 'reseller'
        ]);
        $this->createConfigForPartnerApp($app->getId());
        [$subMerchant, $accessMap] = $this->createSubMerchant($partner, $app,
            ['id'=>self::DEFAULT_SUBMERCHANT_ID]);
        $this->fixtures->edit('merchant', $subMerchant->getId(), [
            'channel' => Channel::AXIS,
            'activated' => true ,
            'suspended_at' => null
        ]);

        $this->fixtures->user->createUserForMerchant($partner->getId());

        // Create Payment, Refund, Transaction, Settlement and InternationalIntegration

        $payments = $this->createPaymentEntities(2, $subMerchant->getId());
        $refund = $this->fixtures->create('refund:from_payment', ['payment' => $payments[0]]);
        $dispute = $this->fixtures->create('dispute', [
            'merchant_id' => self::DEFAULT_SUBMERCHANT_ID, 'deduct_at_onset' => true,
            'amount' => 100, 'test' => 'nium']);

        $dispute2 = $this->fixtures->create('dispute', [
            'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            'deduct_at_onset'       => 1,
            'amount'                => 100, 
            'test'                  => 'nium',
            'amount_deducted'       => 100,
            'deduction_source_type' => 'adjustment',
            'deduction_source_id'   => 'randomAdjId123',
            'status'                => 'under_review',
            'internal_status'       => 'represented',
            'amount_reversed'       => 0,
            'deduction_reversal_at' => time() + 60 * 86400,
        ]);

        $this->performAdminActionOnDispute(['status' => 'won'], $dispute2->getId());

        $this->initiateSettlements(Channel::AXIS);
        $settlement = $this->getLastEntity('settlement', true);



        $this->fixtures->stripSign($settlement['id']);

        $request = [
            'url' => '/settlements/status/update',
            'method' => 'POST',
            'content' => [
                'id'                            => $settlement['id'],
                'utr'                           => '12312312311',
                'status'                        => 'processed',
                'redacted_ba'                   => 'sample',
                'remarks'                       => 'xyz',
                'failure_reason'                => 'na',
                'trigger_failed_notification'   => false
            ],
        ];

        $this->ba->settlementsAuth();
        $this->makeRequestAndGetContent($request);

        $this->fixtures->create('merchant_international_integrations:nium_integration', [
            'merchant_id'    => self::DEFAULT_SUBMERCHANT_ID,
            'integration_key' => 'NIUM0000063',
            'notes'          => [
                'country'       => 'SGP',
                'payForText'    => 'Develop Ecom Application'
            ]
        ]);

        // Send Request and Test

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));
        $this->ba->cronAuth();
        $this->startTest();
    }

    public function testNiumFileGenerationNoSettlements()
    {
        // Create Partner Merchant, Partner Config, Sub Merchant and Access Map

        list($partner, $app) = $this->createPartnerAndApplication([
            'partner_type' => 'reseller'
        ]);
        $this->createConfigForPartnerApp($app->getId());
        [$subMerchant, $accessMap] = $this->createSubMerchant($partner, $app,
            ['id'=>self::DEFAULT_SUBMERCHANT_ID]);
        $this->fixtures->edit('merchant', $subMerchant->getId(), [
            'channel' => Channel::AXIS,
            'activated' => true ,
            'suspended_at' => null
        ]);

        $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->fixtures->create('merchant_international_integrations:nium_integration', [
            'merchant_id'    => self::DEFAULT_SUBMERCHANT_ID,
            'integration_key' => 'NIUM0000063',
            'notes'          => [
                'country'       => 'SGP',
                'payForText'    => 'Develop Ecom Application'
            ]
        ]);

        // Send Request and Test

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));
        $this->ba->cronAuth();
        $this->startTest();
    }

    public function testNiumRepatriation()
    {

        list($partner, $app) = $this->createPartnerAndApplication([
            'partner_type' => 'reseller'
        ]);
        $this->createConfigForPartnerApp($app->getId());
        [$subMerchant, $accessMap] = $this->createSubMerchant($partner, $app,
            ['id'=>self::DEFAULT_SUBMERCHANT_ID]);
        $this->fixtures->edit('merchant', $subMerchant->getId(), [
            'channel' => Channel::AXIS,
            'activated' => true ,
            'suspended_at' => null
        ]);

        $this->fixtures->user->createUserForMerchant($partner->getId());

        $payments = $this->createPaymentEntities(2, $subMerchant->getId());
        $refund = $this->fixtures->create('refund:from_payment', ['payment' => $payments[0]]);

        $this->initiateSettlements(Channel::AXIS);
        $settlement = $this->getLastEntity('settlement', true);

        $paymentTransaction1 = $this->getEntities('transaction', ['entity_id' => $payments[0]['id']], true);
        $paymentTransaction2 = $this->getEntities('transaction', ['entity_id' => $payments[1]['id']], true);
        $refundTransaction = $this->getEntities('transaction', ['entity_id' => $refund['id']], true);


        $this->fixtures->stripSign($settlement['id']);

        $request = [
            'url' => '/settlements/status/update',
            'method' => 'POST',
            'content' => [
                'id'                            => $settlement['id'],
                'utr'                           => '12312312311',
                'status'                        => 'processed',
                'redacted_ba'                   => 'sample',
                'remarks'                       => 'xyz',
                'failure_reason'                => 'na',
                'trigger_failed_notification'   => false
            ],
        ];

        $this->ba->settlementsAuth();
        $this->makeRequestAndGetContent($request);

        $settlementAmount = $settlement['amount']/100;
        $entries = [
            [
                self::DATE_TIME                 => '17/05/2022 15:30:03',
                self::CLIENT                    => 'Churn',
                self::ACCOUNT_NUMBER            => '1234512345',
                self::TRANSACTION               => 'TR12345',
                self::LT                        => 'Payouts',
                self::CURRENCY                  => 'USD',
                self::DR_AMOUNT                 => '100',
                self::CR_AMOUNT                 => '',
                self::REMARK                    => 'remark',
                self::TXN_ID                    => 'TRIS1234',
                self::USD_DR_AMT                => '10',
                self::USD_CR_AMT                => '',
                self::STATUS                    => '',
            ],
            [
                self::DATE_TIME                 => '17/05/2022 15:30:03',
                self::CLIENT                    => 'Churn',
                self::ACCOUNT_NUMBER            => '1234512345',
                self::TRANSACTION               => 'TR123456',
                self::LT                        => 'BOOK_FX',
                self::CURRENCY                  => 'USD',
                self::DR_AMOUNT                 => '',
                self::CR_AMOUNT                 => '100',
                self::REMARK                    => 'remark',
                self::TXN_ID                    => 'TRIS1234',
                self::USD_DR_AMT                => '',
                self::USD_CR_AMT                => '10',
                self::STATUS                    => '',
            ],
            [
                self::DATE_TIME                 => '17/05/2022 15:30:03',
                self::CLIENT                    => 'Churn',
                self::ACCOUNT_NUMBER            => '1234512345',
                self::TRANSACTION               => '',
                self::LT                        => 'BOOK_FX',
                self::CURRENCY                  => 'INR',
                self::DR_AMOUNT                 => $settlementAmount,
                self::CR_AMOUNT                 => '',
                self::REMARK                    => 'remark',
                self::TXN_ID                    => 'TRIS1234',
                self::USD_DR_AMT                => '',
                self::USD_CR_AMT                => '10',
                self::STATUS                    => '',
            ],
            [
                self::DATE_TIME                 => '17/05/2022 15:30:03',
                self::CLIENT                    => 'Churn',
                self::ACCOUNT_NUMBER            => '1234512345',
                self::TRANSACTION               => substr($paymentTransaction1['items'][0]['id'], 4),
                self::LT                        => 'Receive',
                self::CURRENCY                  => 'INR',
                self::DR_AMOUNT                 => '',
                self::CR_AMOUNT                 => '100000',
                self::REMARK                    => 'remark',
                self::TXN_ID                    => 'TRIS1234',
                self::USD_DR_AMT                => '',
                self::USD_CR_AMT                => '10',
                self::STATUS                    => '',
            ],
            [
                self::DATE_TIME                 => '17/05/2022 15:30:03',
                self::CLIENT                    => 'Churn',
                self::ACCOUNT_NUMBER            => '1234512345',
                self::TRANSACTION               => substr($paymentTransaction2['items'][0]['id'], 4),
                self::LT                        => 'Receive',
                self::CURRENCY                  => 'INR',
                self::DR_AMOUNT                 => '',
                self::CR_AMOUNT                 => '100000',
                self::REMARK                    => 'remark',
                self::TXN_ID                    => 'TRIS1234',
                self::USD_DR_AMT                => '',
                self::USD_CR_AMT                => '10',
                self::STATUS                    => '',
            ],
            [
                self::DATE_TIME                 => '17/05/2022 15:30:03',
                self::CLIENT                    => 'Churn',
                self::ACCOUNT_NUMBER            => '1234512345',
                self::TRANSACTION               => substr($refundTransaction['items'][0]['id'], 4),
                self::LT                        => 'Receive',
                self::CURRENCY                  => 'INR',
                self::DR_AMOUNT                 => '100',
                self::CR_AMOUNT                 => '',
                self::REMARK                    => 'remark',
                self::TXN_ID                    => 'TRIS1234',
                self::USD_DR_AMT                => '',
                self::USD_CR_AMT                => '10',
                self::STATUS                    => '',
            ],
        ];

        $fileName = 'file_acct_'.$settlement['id'];
        $url = $this->writeToCsvFile($entries, $fileName, null, 'files/settlement');

        $uploadedFile = $this->createUploadedFileCsv($url);

        $input = [
            'manual'           => true,
            'partner'          => 'NIUM',
            'attachment-count' => 1,
        ];

        $lambdaRequest = [
            'url'     => '/settlements/nium/repat',
            'content' => $input,
            'method'  => 'POST',
            'files' => [
                'file' => $uploadedFile,
            ],
        ];

        $this->ba->h2hAuth();
        $content = $this->makeRequestAndGetContent($lambdaRequest);
        $this->assertTrue($content['success']);

        $repatriationEntity = $this->getLastEntity('settlement_international_repatriation', true);
        $this->assertEquals($settlement['amount'], $repatriationEntity['amount']);
        $this->assertEquals('INR', $repatriationEntity['currency']);
        $this->assertEquals('TR12345', $repatriationEntity['partner_settlement_id']);
        $this->assertEquals('1234512345', $repatriationEntity['partner_merchant_id']);
        $this->assertEquals($settlement['id'], $repatriationEntity['settlement_ids'][0]);
        $this->assertEquals('TRIS1234', $repatriationEntity['partner_transaction_id']);
        $this->assertEquals(10000, $repatriationEntity['credit_amount']);
        $this->assertEquals('USD', $repatriationEntity['credit_currency']);

    }

    public function createUploadedFileCsv(string $url, $fileName = 'file_acct_0123.csv'): UploadedFile
    {
        $mime = 'text/csv';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }

    protected function performAdminActionOnDispute(array $input, $disputeId)
    {
        $this->addPermissionToBaAdmin('edit_dispute');
        $admin = $this->ba->getAdmin();
        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);
        $this->ba->adminProxyAuth(self::DEFAULT_SUBMERCHANT_ID, 'rzp_test_' . self::DEFAULT_SUBMERCHANT_ID);

        return $this->makeRequestAndGetContent([
            'url'     => '/disputes/disp_' . $disputeId,
            'method'  => 'POST',
            'content' => $input,
        ]);
    }
}
