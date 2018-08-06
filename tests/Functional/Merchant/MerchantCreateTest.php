<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Razorpay\OAuth\Application;
use Illuminate\Database\Eloquent\Factory;
use Razorpay\OAuth\Application\Entity as OAuthApp;

use RZP\Constants;
use RZP\Constants\Mode;
use RZP\Mail\User\PasswordReset;
use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Mail\User\PasswordReset as PasswordResetMail;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;
use RZP\Mail\Merchant\CreateSubMerchantPartner as CreateSubMerchantPartnerMail;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateMail;


class MerchantCreateTest extends TestCase
{
    use OAuthTrait;
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantCreateTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->appAuth();
    }

    public function testCreateMerchantWithDuplicateEmail()
    {
        $this->ba->adminAuth(Mode::TEST);

        $this->startTest();
    }

    public function testCreateMerchantWithDuplicateId()
    {
        $this->ba->adminAuth(Mode::TEST);

        $this->startTest();
    }

    public function testCreateMerchantAndRelations()
    {
        $this->ba->adminAuth();

        $this->merchantId = '1X4hRFHFx4UiXt';

        $content = $this->createMerchant();

        $this->assertSame($content['activated'], false);

        $this->checkSettlementSchedule($content);

        $this->checkTerminals();

        $this->checkBalances();

        $this->checkNetbankingBanks();

        $this->checkMethods();

        $this->checkMerchantDetails();
    }

    protected function createMerchant()
    {
        $testData = $this->testData['testCreateMerchant'];

        return $this->runRequestResponseFlow($testData);
    }

    protected function checkTerminals()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $testData = $this->testData['testGetTerminalsInTestForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth('live', null, 'org_' . Org::RZP_ORG);

        $testData = $this->testData['testGetTerminalsInLiveForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);
    }

    protected function checkBalances()
    {
        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt');

        $this->runRequestResponseFlow($this->testData['testBalanceInTestAfterCreatedMerchant']);

        $this->ba->proxyAuth('rzp_live_1X4hRFHFx4UiXt');

        $this->runRequestResponseFlow($this->testData['testBalanceInLiveAfterCreatedMerchant']);
    }

    protected function checkNetbankingBanks()
    {
        $this->checkNetbankingBanksInMode(Mode::TEST);

        $this->checkNetbankingBanksInMode(Mode::LIVE);
    }

    protected function checkMethods()
    {
        $this->ba->appAuthTest();

        $methods = $this->getEntityById('methods', '1X4hRFHFx4UiXt', true);

        $expectedMethods = [
            'amex'     => false,
            'mobikwik' => false,
            'paytm'    => false
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methods);
    }

    protected function checkMerchantDetails()
    {
        $this->ba->appAuthTest();

        $merchantDetails = $this->getEntityById('merchant_detail', '1X4hRFHFx4UiXt', true);

        $this->assertEquals($merchantDetails['contact_email'], 'test@localhost.com');
    }

    protected function checkSettlementSchedule($merchant)
    {
        $this->ba->appAuthTest();

        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $schedule = $this->getEntityById('schedule', $scheduleTask['schedule_id'], true);

        $this->assertEquals($merchant['id'], $scheduleTask['merchant_id']);
        $this->assertEquals($schedule['merchant_id'], '100000Razorpay');
        $this->assertEquals($schedule['period'], 'daily');
        $this->assertEquals($schedule['delay'], 3);
    }

    protected function checkNetbankingBanksInMode($mode)
    {
        $this->ba->adminAuth($mode);

        $testData = $this->testData['testGetBankAccountsAfterCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->assertSame(array(), $content['disabled']);
    }

    public function testCreateSubMerchant()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com', 'Submerchant');
        });

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateSubMerchantWithoutFeatureMarketplaceOrPartner()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testCreateSubMerchantWithoutName()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testCreateSubMerchantWrongUserRole()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->createUserMerchantMapping('10000000000000', 'finance');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'finance');

        $this->startTest();
    }

    public function testCreateSubMerchantWithEmail()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateSubMerchantWithEmailUserExists()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->fixtures->create('user', ['email' => 'submerchant@razorpay.com']);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        Mail::assertQueued(CreateSubMerchantMail::class, function ($mail)
        {
            return $mail->hasTo('submerchant@razorpay.com', 'Submerchant 2');
        });

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));
    }

    private function createUserMerchantMapping($merchantId, $role)
    {
        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => $role,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        return $user;

    }
    public function testCreateSubMerchantWithDuplicateEmail()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        // Just to check email collisions are still errors
        $this->fixtures->create('merchant', ['id' => '10000000000002', 'email' => 'test2@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testCreateSubMerchantByFullyManagedWOEmail()
    {
        Mail::fake();

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertNotQueued(CreateSubMerchantAffiliateMail::class);

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByFullyManagedWithEmail()
    {
        Mail::fake();

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertNotQueued(PasswordResetMail::class);

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByFullyManagedWithEmailUserExists()
    {
        Mail::fake();

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $user2 = $this->fixtures->create('user', ['email' => 'testsub@razorpay.com']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));

        $mapping2 = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user2['id']);

        $this->assertEquals(1, count($mapping2));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByAggregatorWithEmail()
    {
        Mail::fake();

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        // This should be empty once aggregator type's dashboard access is removed
        // in withEmail cases.
        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByAggregatorWithoutEmail()
    {
        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateSubMerchantByAggregatorWithoutApp()
    {
        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        (new Application\Repository())->deleteOrFail($app);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateSubMerchantByAggregatorExceptionWithoutEmail()
    {
        Mail::fake();

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        // TODO: Move to partner app post discussion on features in proxy auth
        $this->fixtures->merchant->addFeatures(['allow_sub_without_email']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user['id']);

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateMarketplaceLinkedAccount()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    public function testCreateMarketplaceLinkedAccountWithDashbaordUser()
    {
        Mail::fake();

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $merchant = Merchant\Entity::find("10000000000000");
        $merchant->reTag([Merchant\Entity::ENABLE_LA_DASHBOARD]);
        $merchant->saveOrFail();

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();

        Mail::assertQueued(PasswordReset::class, function ($mail)
        {
            return $mail->hasTo('linkedaccount@razorpay.com');
        });
    }

    public function testCreateMarketplaceLinkedAccountWithoutEmail()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    public function testCreateLinkedAccountMaxPaymentLimit()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 6000]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    /**
     * Testing Marketplace LA addition while also marked as partner of type bank.
     */
    public function testCreateMarketplaceLAWithoutEmailWithPartnerBank()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'bank']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    /**
     * Testing Marketplace LA addition while also marked as partner of type fully managed.
     */
    public function testCreateMarketplaceLAWithoutEmailWithPartnerFM()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    /**
     * Testing Partner sub merchant addition while also featured as marketplace
     */
    public function testCreateSubMerchantWithoutEmailWithPartnerFMAndMarketplace()
    {
        Mail::fake();

        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        list($app, $user) = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });
    }

    public function testLinkedAccountDefaultSchedule()
    {
        $this->fixtures->create('merchant',
                                [
                                    'id' => '10000000000002',
                                    'email' => 'test2@razorpay.com'
                                ]);

        $user = $this->createUserMerchantMapping('10000000000002', 'owner');

        // Define T+2 cycle for new merchant
        $schedule = [
            'interval'          => 1,
            'delay'             => 2,
            'hour'              => 0
        ];

        $this->fixtures->create('merchant:schedule_task',
                                [
                                    'merchant_id' => '10000000000002',
                                    'schedule'    => $schedule
                                ]);

        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000002');

        $this->ba->proxyAuth('rzp_test_10000000000002');

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $linkedAcc = $this->startTest();

        $this->ba->appAuthTest();

        // Check schedule entries for new linked account
        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $schedule = $this->getEntityById('schedule', $scheduleTask['schedule_id'], true);

        $this->assertEquals($linkedAcc['id'], $scheduleTask['merchant_id']);
        $this->assertEquals($schedule['delay'], 2);
    }

    public function testCreateLinkedAccountBatch()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $entries = $this->getLinkedAccountBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertEquals('Test Bank Account 2', $merchantDetail['bank_account_name']);

        $account = $this->getLastEntity('merchant', true);

        $this->assertEquals('test 2', $account['name']);
        $this->assertEquals(true, $account['activated']);
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }

    protected function getLinkedAccountBatchFileEntries(): array
    {
        return [
            [
                Header::BUSINESS_NAME       => 'test 1',
                Header::BANK_ACCOUNT_NUMBER => 'BANKACCNUMBEROF22CHARS',
                Header::BANK_BRANCH_IFSC    => 'SBIN0007105',
                Header::BANK_ACCOUNT_TYPE   => 'Current',
                Header::BANK_ACCOUNT_NAME   => 'Test Bank Account 1',
                Header::REFERENCE_ID        => 'REF001',
                Header::ACCOUNT_ID          => '',

            ],
            [
                Header::BUSINESS_NAME       => 'test 2',
                Header::BANK_ACCOUNT_NUMBER => '111000',
                Header::BANK_BRANCH_IFSC    => 'SBIN0007105',
                Header::BANK_ACCOUNT_TYPE   => 'Current',
                Header::BANK_ACCOUNT_NAME   => 'Test Bank Account 2',
                Header::REFERENCE_ID        => 'REF002',
                Header::ACCOUNT_ID          => '',
            ],
        ];
    }

    protected function getLastMappingForBothModes()
    {
        $test = $this->getLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                true,
                'test');

        $live = $this->getLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                true,
                'live');

        return [$test, $live];
    }

    protected function markPartnerAndCreateAppAndUserMapping(
        string $type = 'fully_managed',
        string $merchantId = '10000000000000')
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => $type]);

        $app = $this->createOAuthApplication(['merchant_id' => $merchantId, 'type' => 'partner']);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        return [$app, $user];
    }

    protected function verifyAccessMapEntries(OAuthApp $app, array $submerchant)
    {
        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertEquals($submerchant['id'], $testMapping['merchant_id']);

        $this->assertEquals($app->getId(), $testMapping['entity_id']);

        $this->assertEquals('application', $testMapping['entity_type']);

        $this->assertEquals($submerchant['id'], $liveMapping['merchant_id']);

        $this->assertEquals($app->getId(), $liveMapping['entity_id']);

        $this->assertEquals('application', $liveMapping['entity_type']);
    }
}
