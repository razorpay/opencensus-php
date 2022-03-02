<?php


namespace Functional\Merchant;

use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\MerchantApplications;

use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

class MerchantCoreTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use SettlementTrait;

    const RZP_ORG  = '100000razorpay';

    protected $core;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->core = new Core();
    }

    public function testAggregatorToResellerPartnerTypeUpdate()
    {
        list($merchantId, $submerchantId, $newAppId, $client) = $this->createAggregatorPartnerAndSubmerchantAndFetchMocks();

        $this->fixtures->create('referrals');

        $this->createCommissionForPartner($merchantId, $submerchantId, $newAppId);

        $this->core->updateAggregatorToReseller($merchantId);

        // partner type should be updated as reseller
        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals('reseller', $merchant->getPartnerType());

        // new merchant application with referred type should be created
        $updatedApplications = $this->getDbEntities('merchant_application',
            ['application_id' => '8ckeirnw84ifke'])->toArray();
        $this->assertCount(1, $updatedApplications);
        $this->assertEquals('referred', $updatedApplications[0]['type']);

        // all existing applications for partner should be deleted
        $oldApplications = $this->getDbEntities('merchant_application',
            ['application_id' => $client->getApplicationId()])->toArray();
        $this->assertCount(0, $oldApplications);

        // referral link data should not be deleted
        $referrals = $this->getDbEntity('referrals',
            ['merchant_id' => $merchantId, 'product' => 'primary']);
        $this->assertNotEmpty($referrals->getReferralLink());

        // merchant access map should be updated with new application id
        $accessMaps = $this->getDbEntities('merchant_access_map',
            ['entity_type' => 'application', 'entity_owner_id' => $merchantId])->toArray();
        $this->assertCount(2, $accessMaps);
        $this->assertEquals($newAppId, $accessMaps[0]['entity_id']);

        // commissions data should not be deleted for partner
        $commission = $this->getDbEntities("commission", ["partner_id" => $merchantId]);
        $this->assertCount(1, $commission);
    }

    protected function createAggregatorPartnerAndSubmerchantAndFetchMocks(string $merchantId = '10000000000000', string $submerchantId = '100submerchant', string $newAppId = '8ckeirnw84ifke')
    {
        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($merchantId, $submerchantId);

        $this->fixtures->merchant->edit($merchantId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);

        $app = $this->createSubmerchantMappingForReferredApp($merchantId, '101submerchant');

        $this->ba->adminAuth();

        $this->setUpAuthServiceMocks($merchantId, $client->getApplicationId(), $app->getId(), $newAppId);

        return [$merchantId, $submerchantId, $newAppId, $client];
    }

    private function createSubmerchantMappingForReferredApp(string $merchantId, string $submerchantId)
    {
        $app = $this->createOAuthApplication(['merchant_id' => $merchantId, 'partner_type' => 'reseller']);

        $this->fixtures->merchant->createAccount('101submerchant');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'              => 'J00dqRlTehtNbv',
                'merchant_id'     => '101submerchant',
                'entity_id'       => $app->getId(),
                'entity_type'     => 'application',
                'entity_owner_id' => $merchantId,
            ]
        );

        return $app;
    }

    protected function createCommissionForPartner(string $partnerId, string $submerchantId, string $appId)
    {
        $config = $this->createConfigForPartnerApp($appId);

        $payment = $this->createPaymentEntities(1, $submerchantId);

        $commissionAttributes = [
            'source_id'         => $payment->getId(),
            'partner_id'        => $partnerId,
            'partner_config_id' => $config->getId(),
        ];

        $commission = $this->fixtures->create('commission:commission_and_sync_es', $commissionAttributes);

        return $commission;
    }

    protected function setUpAuthServiceMocks(string $merchantId, string $managedApp, string $referredApp, string $newAppId)
    {
        $this->authServiceMock
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->withConsecutive(
                ['applications', 'POST',
                    [
                        'name' => 'et',
                        'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod',
                        'merchant_id' => $merchantId,
                        'type' => 'partner'
                    ]
                ],
                ['applications/'.$managedApp, 'PUT', ['merchant_id' => $merchantId]
                ],
                ['applications/'.$referredApp, 'PUT', ['merchant_id' => $merchantId]
                ])
            ->willReturnOnConsecutiveCalls($app = ['id'=> $newAppId], [], []);
    }
}
