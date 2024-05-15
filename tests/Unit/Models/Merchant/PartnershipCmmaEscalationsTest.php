<?php

namespace Unit\Models\Merchant;

use DB;
use RZP\Constants\Mode;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Escalations\Constants;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\AutoKyc\Escalations\Core as EscalationCore;

class PartnershipCmmaEscalationsTest extends TestCase
{
    use DbEntityFetchTrait;

    protected $splitzMock;

    private function createTransaction(string $merchantId, string $type, int $amount)
    {
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => $type,
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId
        ]);
    }

    private function createAndFetchFixtures()
    {
        $permission = $this->fixtures->connection('live')->create('permission', [
            'name' => Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED
        ]);

        // Creating workflow
        $workflow = $this->fixtures->connection('live')->create('workflow', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
            'name'   => "NC Workflow"
        ]);

        // Attaching create_payout permission to the workflow
        DB::connection('live')->table('workflow_permissions')->insert([
                                                                          'workflow_id'   => $workflow->getId(),
                                                                          'permission_id' => $permission->getId()
                                                                      ]);
        DB::connection('live')->table('permission_map')->insert([
                                                                    'entity_id'     => OrgEntity::RAZORPAY_ORG_ID,
                                                                    'entity_type'   => 'org',
                                                                    'permission_id' => $permission->getId(),
                                                                ]);

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_type'     => 11,
            'business_website'  => "http://hello.com"
        ]);

        return [$merchant];
    }

    private function createPartnerSubMerchantMocks(string $subMerchantId)
    {
        $partner = $this->fixtures->create('merchant', ['partner_type' => 'reseller']);

        $appAttributes = [
            'merchant_id' => $partner->getId(),
            'partner_type'=> 'reseller',
        ];

        $app = $this->fixtures->merchant->createDummyPartnerApp($appAttributes);

        $accessMapData = [
            'entity_type'     => 'application',
            'entity_id'       => $app->getId(),
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => $partner->getId(),
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        return [$partner];
    }

    protected function getSplitzMock()
    {
        if ($this->splitzMock === null)
        {
            $this->splitzMock = \Mockery::mock(SplitzService::class, [$this->app])->makePartial();

            $this->app->instance('splitzService', $this->splitzMock);
        }

        return $this->splitzMock;
    }

    protected function mockAllSplitzTreatment($output = [
        "response" => [
            "variant" => [
                "name" => 'enable',
            ]
        ]
    ])
    {
        return $this->getSplitzMock()
                    ->shouldReceive('evaluateRequest')
                    ->andReturn($output);
    }

    public function testSoftLimitCMMAEscalationForSubMerchant()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createAndFetchFixtures();

        [$partner] = $this->createPartnerSubMerchantMocks($merchant->getId());

        $this->createTransaction($merchant->getId(), 'payment', 1000);

        $escalationPayload = [
            'variables' => [
                'caseType' => Constants::ACTIVATION,
                "merchantId" => $merchant->getId(),
                "triggeredOn" => Constants::CMMA_SOFT_LIMIT_BREACH,
                "merchantName" => $merchant->getName(),
                "hasMerchantTransacted" => "false",
            ]
        ];

        $cmmaProxyControllerMock = \Mockery::mock('overload:RZP\Http\Controllers\CmmaProxyController');

        $cmmaProxyControllerMock
            ->shouldReceive('handleInternalCronProxyRequests')
            ->once()
            ->with(Constants::CMMA_ROUTE, \Mockery::on(
                function(array $arg) use ($escalationPayload)
                {
                    $this->assertArraySelectiveEquals($escalationPayload, $arg);
                    return true;
                }
            ));

        $this->mockAllSplitzTreatment();

        (new EscalationCore())->handleSoftLimitBreach();
    }

    public function testHardLimitCMMAEscalationForSubMerchant()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createAndFetchFixtures();

        [$partner] = $this->createPartnerSubMerchantMocks($merchant->getId());

        $this->createTransaction($merchant->getId(), 'payment', 1000);

        $escalationPayload = [
            'variables' => [
                'caseType' => Constants::ACTIVATION,
                "merchantId" => $merchant->getId(),
                "triggeredOn" => Constants::CMMA_HARD_LIMIT_BREACH,
                "merchantName" => $merchant->getName(),
                "hasMerchantTransacted" => "false",
            ]
        ];

        $cmmaProxyControllerMock = \Mockery::mock('overload:RZP\Http\Controllers\CmmaProxyController');

        $cmmaProxyControllerMock
            ->shouldReceive('handleInternalCronProxyRequests')
            ->once()
            ->with(Constants::CMMA_ROUTE, \Mockery::on(
                function(array $arg) use ($escalationPayload) {
                    $this->assertArraySelectiveEquals($escalationPayload, $arg);
                    return true;
                }
            ));

        $this->mockAllSplitzTreatment();

        (new EscalationCore())->handleHardLimitBreach();
    }
}
