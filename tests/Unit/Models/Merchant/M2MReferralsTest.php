<?php


namespace Unit\Models\Merchant;

use DB;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Services\Mock\HarvesterClient;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use RZP\Models\Feature\Constants;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Models\Merchant\AutoKyc\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as EscalationConstant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Cron\Core as CronJobHandler;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
class M2MReferralsTest extends TestCase
{
    use DbEntityFetchTrait;

    protected function createAndFetchMocks()
    {
        Mail::fake();
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY  or
                                      $feature === RazorxTreatment::DRUID_MIGRATION )
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    private function createTransaction(string $merchantId, string $type, int $amount)
    {
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => $type,
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId
        ]);
    }


    private function createMerchantFixtures(int $activated_at)
    {

        $merchant = $this->fixtures->on('live')->create('merchant', [
            'live'         => true,
            'activated'    => 1,
            'activated_at' => $activated_at
        ]);

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated'
        ]);

        return [$merchant];
    }

    public function mockDruid($merchantId,$amount)
    {

        config(['services.druid.mock' => true]);

        $druidService = $this->getMockBuilder(MockDruidService::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['getDataFromDruid'])
                             ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = [
            'user_days_till_last_transaction' => 30,
            'merchant_lifetime_gmv'           => $amount,
            'average_monthly_gmv'             => 10,
            'primary_product_used'            => 'payment_links',
            'ppc'                             => 1,
            'mtu'                             => true,
            'average_monthly_transactions'    => 3,
            'pg_only'                         => false,
            'pl_only'                         => true,
            'pp_only'                         => false,
            'merchant_details_merchant_id'    => $merchantId
        ];

        $druidService->method('getDataFromDruid')
                     ->willReturn([null, [$dataFromDruid]]);
    }

    public function mockPinot($merchantId,$amount)
    {
        $harvesterService = $this->getMockBuilder(HarvesterClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getDataFromPinot'])
            ->getMock();

        $this->app->instance('eventManager', $harvesterService);

        $dataFromHarvester = [
            'user_days_till_last_transaction' => 30,
            'merchant_lifetime_gmv'           => $amount,
            'average_monthly_gmv'             => 10,
            'primary_product_used'            => 'payment_links',
            'ppc'                             => 1,
            'mtu'                             => true,
            'average_monthly_transactions'    => 3,
            'pg_only'                         => false,
            'pl_only'                         => true,
            'pp_only'                         => false,
            'merchant_details_merchant_id'    => $merchantId
        ];

        $harvesterService->method('getDataFromPinot')
            ->willReturn([$dataFromHarvester]);
    }

    /**
     * Scenario: merchant have transacted in the past day,
     * merchant is in activated state,
     * merchant has payment above a threshold,
     * merchant is activated for min time,
     * m2m referral feature already not created
     * Expectation: M2Mreferral feature is created in features table
     */
    public function testCreatingM2MFeatureWhenAlreadyNotExists()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createMerchantFixtures(1631258738);

        $this->mockPinot($merchant->getId(),6000);

        $this->mockDruid($merchant->getId(),6000);

        $this->createTransaction($merchant->getId(), 'payment', 300000);
        $this->createTransaction($merchant->getId(), 'payment', 200000);
        $this->createTransaction($merchant->getId(), 'payment', 100000);

        (new CronJobHandler())->handleCron(CronConstants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME, []);
        $features = $this->getDbLastEntity('feature', 'live');
        self::assertNotEmpty($features);
        self::assertEquals(Constants::M2M_REFERRAL, $features->getAttribute('name'));

    }

    /**
     * Scenario: merchant have transacted in the past day,
     * merchant is in activated state,
     * merchant has payment above a threshold,
     * merchant is activated for min time,
     * m2m referral feature already created
     * Expectation: New M2Mreferral feature is not created in features table
     */
    public function testCreatingM2MFeatureWhenAlreadyExists()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createMerchantFixtures(1631258738);
        $this->mockPinot($merchant->getId(),6000);

        $feature = $this->fixtures->on('live')->create('feature', [
            'entity_id'   => $merchant->getId(),
            'name'        => Constants::M2M_REFERRAL,
            'entity_type' => 'merchant',
        ]);

        $this->createTransaction($merchant->getId(), 'payment', 300000);
        $this->createTransaction($merchant->getId(), 'payment', 200000);
        $this->createTransaction($merchant->getId(), 'payment', 100000);
        (new CronJobHandler())->handleCron(CronConstants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME, []);

        $features = $this->getDbLastEntity('feature', 'live');
        self::assertNotEmpty($features);
        self::assertEquals(Constants::M2M_REFERRAL, $features->getAttribute('name'));
        self::assertEquals($feature->getId(), $features->getId());
    }

    /**
     * Scenario: merchant have transacted in the past day,
     * merchant is in activated state,
     * merchant has payment above a threshold,
     * merchant is not activated for min time,
     * m2m referral feature already not created
     * Expectation: New M2Mreferral feature is not created in features table
     */
    public function testCreatingM2MFeatureNotEnoughActivatedTime()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createMerchantFixtures(Carbon::now(Timezone::IST)->getTimestamp());
        $this->mockPinot($merchant->getId(),6000);

        $this->createTransaction($merchant->getId(), 'payment', 300000);
        $this->createTransaction($merchant->getId(), 'payment', 200000);
        $this->createTransaction($merchant->getId(), 'payment', 100000);

        (new CronJobHandler())->handleCron(CronConstants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME, []);

        $features = $this->getDbLastEntity('feature', 'live');
        self::assertNull($features);
    }
    /**
     * Scenario: merchant have transacted in the past day,
     * merchant is in activated state,
     * merchant do not have payment above a threshold,
     * merchant is  activated for min time,
     * m2m referral feature already not created
     * Expectation: New M2Mreferral feature is not created in features table
     */
    public function testCreatingM2MFeatureNotEnoughPaymentVolumne()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createMerchantFixtures(1631258738);
        $this->mockPinot($merchant->getId(),3);

        $this->createTransaction($merchant->getId(), 'payment', 1);
        $this->createTransaction($merchant->getId(), 'payment', 1);
        $this->createTransaction($merchant->getId(), 'payment', 1);

        (new CronJobHandler())->handleCron(CronConstants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME, []);

        $features = $this->getDbLastEntity('feature', 'live');
        self::assertNull($features);
    }
    /**
     * Scenario: merchant have transacted in the past day,
     * merchant is in activated state,
     * merchant have payment above a threshold,
     * merchant is  activated for min time,
     * merchant do not have min transaction count
     * m2m referral feature already not created
     * Expectation: New M2MReferral feature is not created in features table
     */
    public function testCreatingM2MFeatureNotEnoughTransactionCount()
    {
        $this->createAndFetchMocks();
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createMerchantFixtures(1631258738);
        $this->mockPinot($merchant->getId(),3000);

        $this->createTransaction($merchant->getId(), 'payment', 300000);

        (new CronJobHandler())->handleCron(CronConstants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME, []);

        $features = $this->getDbLastEntity('feature', 'live');
        self::assertNull($features);
    }
}
