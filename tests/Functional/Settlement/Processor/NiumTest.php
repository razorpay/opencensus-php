<?php

namespace RZP\Tests\Functional\Settlement\Processor;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;


class NiumTest extends OAuthTestCase
{
    use AttemptTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';
    const DEFAULT_MERCHANT_ID       = 'DefaultPartner';
    const DEFAULT_SUBMERCHANT_ID    = '10000000000009';


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

}
