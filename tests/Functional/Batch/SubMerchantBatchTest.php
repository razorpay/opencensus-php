<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Batch\Header;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Feature\Constants as Feature;
use RZP\Mail\Merchant\CreateSubMerchantPartner;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate;
use RZP\Mail\Merchant\Activation as ActivationMail;
use RZP\Models\Merchant\Detail\Status as MerchantStatus;
use RZP\Mail\Admin\NotifyActivationSubmission as AdminSubmitMail;
use RZP\Mail\Merchant\NotifyActivationSubmission as MerchantSubmitMail;
use RZP\Services\RazorXClient;

class SubMerchantBatchTest extends TestCase
{
    use OAuthTrait;
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SubMerchantBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();


    }

    public function testCreateSubMerchantBatchAggregator()
    {
        $this->fixtures->merchant->addFeatures('aggregator');

        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        Queue::assertNotPushed(BatchJob::class);
    }

    /**
     * Does not use merchant emails as dummy. Follows through all steps
     * up to activation.
     */
    public function testProcessSubMerchantBatchPartnerNotDummyAllSteps()
    {
        Mail::fake();

        $entries = $this->setUpForProcessing(__FUNCTION__);

        //$this->createMerchantApplication('10000000000000', 'aggregator', 'FuMnzvfS6wsB5h');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        Mail::assertQueued(CreateSubMerchantPartner::class, 3);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function ($mail) use ($entries)
        {
            $this->assertEquals($mail->originProduct, 'primary');
            return $mail->hasTo($entries[0][Header::MERCHANT_EMAIL]);
        });

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function ($mail) use ($entries)
        {
            return $mail->hasTo($entries[1][Header::MERCHANT_EMAIL]);
        });

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function ($mail) use ($entries)
        {
            return $mail->hasTo($entries[2][Header::MERCHANT_EMAIL]);
        });

        Mail::assertQueued(AdminSubmitMail::class, 3);

        $emails = $this->getEntities('merchant_email', [], true)['items'];

        $this->assertEmpty($emails);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertTrue($merchantDetail['submitted']);

        $this->assertNotNull($merchantDetail['submitted_at']);

        $merchant = $this->getLastEntity('merchant', true);

        $this->assertTrue($merchant['activated']);

        $this->assertNotNull($merchant['activated_at']);
    }

    public function testSkipBankAccountRegistration()
    {
        $this->setUpForProcessing(__FUNCTION__, 'skipBankAccountRegistrationEntries');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $this->startTest();

        $this->assertProcessedCounts(1, 1, 0);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertTrue($merchantDetail['submitted']);

        $this->assertNotNull($merchantDetail['submitted_at']);

        $merchant = $this->getLastEntity('merchant', true);

        $this->assertTrue($merchant['activated']);

        $this->assertNotNull($merchant['activated_at']);
    }

    /**
     * Does not use merchant emails as dummy. Follows through all steps
     * up to form submit.
     */
    public function testProcessSubMerchantBatchPartnerNotDummySubmit()
    {
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $entries = $this->setUpForProcessing(__FUNCTION__);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        Mail::assertQueued(CreateSubMerchantPartner::class, 3);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function ($mail) use ($entries)
        {
            return $mail->hasTo($entries[0][Header::MERCHANT_EMAIL]);
        });

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function ($mail) use ($entries)
        {
            return $mail->hasTo($entries[1][Header::MERCHANT_EMAIL]);
        });

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function ($mail) use ($entries)
        {
            return $mail->hasTo($entries[2][Header::MERCHANT_EMAIL]);
        });

        Mail::assertQueued(AdminSubmitMail::class, 3);
        Mail::assertNotQueued(ActivationMail::class);

        $emails = $this->getEntities('merchant_email', [], true)['items'];

        $this->assertEmpty($emails);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertTrue($merchantDetail['submitted']);

        $this->assertNotNull($merchantDetail['submitted_at']);
    }

    /**
     * Uses merchant emails as dummy. Follows through all steps
     * up to activation.
     */
    public function testProcessSubMerchantBatchPartnerDummyEmailAllSteps()
    {
        Mail::fake();

        $entries = $this->setUpForProcessing(__FUNCTION__);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        Mail::assertQueued(CreateSubMerchantPartner::class, 3);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, 0);

        Mail::assertQueued(AdminSubmitMail::class, 3);

        $emails = $this->getEntities('merchant_email', [], true)['items'];

        $expectedEmails = [
            [
                'type' => 'partner_dummy',
                'email' => $entries[2][Header::MERCHANT_EMAIL],
            ],
            [
                'type' => 'partner_dummy',
                'email' => $entries[1][Header::MERCHANT_EMAIL],
            ],
            [
                'type' => 'partner_dummy',
                'email' => $entries[0][Header::MERCHANT_EMAIL],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedEmails, $emails);
    }

    /**
     * Uses merchant emails as dummy. Just creates submerchants
     */
    public function testProcessSubMerchantBatchPartnerDummyEmailCreate()
    {
        Mail::fake();

        $entries = $this->setUpForProcessing(__FUNCTION__);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        Mail::assertQueued(CreateSubMerchantPartner::class, 3);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, 0);

        $emails = $this->getEntities('merchant_email', [], true)['items'];

        $expectedEmails = [
            [
                'type' => 'partner_dummy',
                'email' => $entries[2][Header::MERCHANT_EMAIL],
            ],
            [
                'type' => 'partner_dummy',
                'email' => $entries[1][Header::MERCHANT_EMAIL],
            ],
            [
                'type' => 'partner_dummy',
                'email' => $entries[0][Header::MERCHANT_EMAIL],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedEmails, $emails);
    }

    /**
     * Tries to create and activate merchants with invalid input for activate
     * step for 1 merchant.
     */
    public function testProcessSubMerchantBatchPartnerInvalidFileEntriesForActivate()
    {
        // This is not handled yet but needs to be, WIP
        $this->markTestSkipped();

        Mail::fake();

        $this->markPartnerAndCreateApplication();

        $entries = $this->getDefaultFileEntries();

        $entries[2][Header::BANK_BRANCH_IFSC] = 'blah';

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $this->assertProcessedCounts(3, 2, 1);

        Mail::assertQueued(AdminSubmitMail::class, 2);
        Mail::assertQueued(MerchantSubmitMail::class, 2);
        Mail::assertQueued(CreateSubMerchantPartner::class, 2);
    }

    public function testProcessSubMerchantBatchPartnerInvalidInput()
    {
        Mail::fake();

        $this->setUpForProcessing(__FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertNull($batch);

        Mail::assertNotQueued(AdminSubmitMail::class);
        Mail::assertNotQueued(MerchantSubmitMail::class);
        Mail::assertNotQueued(CreateSubMerchantPartner::class);
        Mail::assertNotQueued(CreateSubMerchantAffiliate::class);
    }

    public function testCreateSubMerchantBatchPartner()
    {
        $this->fixtures->merchant->markPartner();

        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateSubMerchantBatchInvalidHeaders()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures('aggregator');

        $entries = $this->getDefaultFileEntries();

        foreach ($entries as & $entry)
        {
            unset($entry[Header::WEBSITE_URL]);
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        Mail::assertNotQueued(AdminSubmitMail::class);
        Mail::assertNotQueued(MerchantSubmitMail::class);
        Mail::assertNotQueued(CreateSubMerchantPartner::class);
        Mail::assertNotQueued(CreateSubMerchantAffiliate::class);
    }
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
                                  if ($feature === RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    public function testProcessSubMerchantBatchInstantActivation()
    {
        $this->setUpForProcessing(__FUNCTION__);

        $this->createAndFetchMocks();

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch3@razorpay.com'], 'test');

        $this->assertNotNull($merchant);

        $this->assertTrue($merchant->isActivated());

        $this->assertNotNull($merchant->getActivatedAt());

        $this->assertTrue($merchant->getInternationalAttribute());

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('instantly_activated', $merchantDetail->getActivationStatus());
    }

    public function testProcessSubMerchantBatchForNotEnablingInternational()
    {
        $this->setUpForProcessing(__FUNCTION__);

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch3@razorpay.com'], 'test');

        $this->assertNotNull($merchant);

        $this->assertFalse($merchant->getInternationalAttribute());

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('activated', $merchantDetail->getActivationStatus());
    }

    public function testProcessSubMerchantBatchForEnablingInternational()
    {
        $this->setUpForProcessing(__FUNCTION__);

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch3@razorpay.com'], 'test');

        $this->assertNotNull($merchant);

        $this->assertTrue($merchant->getInternationalAttribute());

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('activated', $merchantDetail->getActivationStatus());
    }

    public function testProcessSubMerchantBatchForUnregisteredMerchants()
    {
        $this->markTestSkipped("Skipping the test until 25-01-2021: manual testing done");
        $this->setUpForProcessing(__FUNCTION__, 'UnregisteredEntries');

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->startTest();

        $this->assertProcessedCounts(1, 1, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch2@razorpay.com'], 'live');

        $this->assertNotNull($merchant);

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('activated', $merchantDetail->getActivationStatus());
    }

    public function testProcessSubMerchantBatchForActivateAlreadyExistingMerchant()
    {
        // create merchant and instant activate merchant and submit
        $this->setUpForProcessing(__FUNCTION__);

        $this->createAndFetchMocks();

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch3@razorpay.com'], 'test');

        $this->assertNotNull($merchant);

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('instantly_activated', $merchantDetail->getActivationStatus());

        $customActionAttributes = [
            'type'   => 'sub_merchant',
            'config' => [
                'partner_id'         => '10000000000000',
                'use_email_as_dummy' => 0,
                'auto_activate'      => 1,
                'auto_submit'        => 1,
                'autofill_details'   => 1,
            ]
        ];

        $this->setUpForProcessingAndAddMerchantContext(__FUNCTION__);

        $data = &$this->testData[__FUNCTION__];

        $data['request']['content'] = $customActionAttributes;

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch3@razorpay.com'], 'test');

        $this->assertNotNull($merchant);

        $this->assertFalse($merchant->getInternationalAttribute());

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('activated', $merchantDetail->getActivationStatus());
    }

    public function testProcessSubMerchantBatchForEditingSubMerchantDetails()
    {
        // create merchant and autofill details
        $this->setUpForProcessing(__FUNCTION__, 'subMerchantEntry');

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $this->startTest();

        $this->assertProcessedCounts(1, 1, 0);

        $merchant = $this->getDbEntity('merchant', ['email' => 'merch1@razorpay.com'], 'test');

        $this->assertNotNull($merchant);

        $merchantDetail = $merchant->merchantDetail;

        // edit merchant details of the given merchant id
        $this->setUpForProcessingAndEditMerchantDetails(__FUNCTION__, $merchant->getId());

        $this->startTest();

        $merchantUpdate = $this->getDbEntity('merchant', ['email' => 'merch1@razorpay.com'], 'test');

        $this->assertNotNull($merchantUpdate);

        $merchantDetailUpdate = $merchantUpdate->merchantDetail;

        // check if business name is updated
        $this->assertNotEquals($merchantDetail->getBusinessName(), $merchantDetailUpdate->getBusinessName());

        // check if contact name is updated
        $this->assertNotEquals($merchantDetailUpdate->getContactName(), $merchantDetail->getContactName());

        //check if contact mobile is not updated
        $this->assertEquals($merchantDetailUpdate->getContactMobile(), $merchantDetail->getContactMobile());

        // check if email id remains the same
        $this->assertEquals($merchantUpdate->getEmail(), $merchant->getEmail());
    }

    protected function getDefaultFileEntries(): array
    {
        return $this->testData['defaultEntries'];
    }

    protected function setUpForProcessing($callee, $testData = 'defaultEntries'): array
    {
        $this->markPartnerAndCreateApplication();

        $entries = $this->testData[$testData];

        $this->createAndPutExcelFileInRequest($entries, $callee);

        return $entries;
    }

    protected function setUpForProcessingAndEditMerchantDetails($callee, $merchantId): array
    {
        $testData = 'subMerchantUpdateEntry';

        $entries = $this->testData[$testData];

        $entries[0][Header::MERCHANT_ID] = $merchantId;

        $this->createAndPutExcelFileInRequest($entries, $callee);

        return $entries;
    }

    protected function setUpForProcessingAndAddMerchantContext($callee, $testData = 'defaultEntries'): array
    {
        $this->markPartnerAndCreateApplication();

        $entries = $this->testData[$testData];

        foreach ($entries as &$entry)
        {
            $merchant = $this->getDbEntity('merchant', ['email' => $entry[Header::MERCHANT_EMAIL]]);

            $entry[Header::MERCHANT_ID] = $merchant->getId();
        }

        $this->createAndPutExcelFileInRequest($entries, $callee);

        return $entries;
    }

    protected function assertProcessedCounts(int $processed, int $success, int $failed)
    {
        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals($processed, $batch['processed_count']);
        $this->assertEquals($success, $batch['success_count']);
        $this->assertEquals($failed, $batch['failure_count']);
        $this->assertEquals('processed', $batch['status']);
    }

    public function testCreateSubMerchantBatchWithActivatedMccPending()
    {
        $this->setUpForProcessing(__FUNCTION__);

        $this->fixtures->merchant->editPricingPlanId(Pricing::DEFAULT_PRICING_PLAN_ID);

        $orgId = '100000razorpay';

        $this->fixtures->create('feature', [
            'name'          => Feature::ORG_SUB_MERCHANT_MCC_PENDING,
            'entity_id'     =>$orgId,
            'entity_type'   => 'org',
        ]);

        $this->startTest();

        $this->assertProcessedCounts(3, 3, 0);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertTrue($merchantDetail['submitted']);
        $this->assertNotNull($merchantDetail);
        $this->assertNotNull($merchantDetail['merchant_id']);
        $this->assertEquals(MerchantStatus::ACTIVATED_MCC_PENDING, $merchantDetail['activation_status']);

        $this->assertArraySubset([
            MerchantStatus::NEEDS_CLARIFICATION,
            MerchantStatus::ACTIVATED
        ], $merchantDetail['allowed_next_activation_statuses']);
    }

    public function testCreateSubMerchantBatchAndRunDedupeWithInvalidPermission()
    {
        $this->setUpForProcessing(__FUNCTION__);

        $orgId = '100000razorpay';

        $this->fixtures->create('feature', [
            'name'          => Feature::ORG_SUB_MERCHANT_MCC_PENDING,
            'entity_id'     => $orgId,
            'entity_type'   =>'org',
        ]);

        $this->startTest();

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertFalse($merchantDetail['submitted']);
        $this->assertNull($merchantDetail['activation_status']);
        $this->assertEmpty($merchantDetail['allowed_next_activation_statuses']);
    }

    protected function markPartnerAndCreateApplication()
    {
        $this->fixtures->merchant->markPartner();

        return $this->createPartnerApplicationAndGetClientByEnv('dev', ['partner_type'=>'fully_managed']);
    }
}
