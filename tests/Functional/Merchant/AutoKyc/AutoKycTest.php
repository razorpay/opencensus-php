<?php


namespace Functional\Merchant\AutoKyc;


use Carbon\Carbon;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Services\MerchantRiskClient;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Tests\Functional\Fixtures\Entity\Workflow;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Cron\Core as CronJobHandler;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\Cron\Collectors\FOHRemovalDataCollector;
use RZP\Models\Merchant\Cron\Collectors\MerchantAutoKycPassDataCollector;

class AutoKycTest extends TestCase
{
    public function testGetApplicableActivationStatusForMerchantNotYetRegistered()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setNonImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForImpersonatedMerchantNotYetRegistered()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
        ]);

        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForMerchantIndividual()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setNonImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForImpersonatedMerchantIndividual()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
        ]);

        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testNoMerchantFoundAutoKycTriggerCMMA()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::INDIVIDUAL),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
        ]);

        // this will not throw any error
        (new CronJobHandler())->handleCron(CronConstants::MERCHANT_AUTO_KYC_FAILURE_CRON_JOB_NAME, []);
        $this->assertNotEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    public function testValidMerchantFoundAutoKycTriggerCMMA()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setNonImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
        ]);

        (new CronJobHandler())->handleCron(CronConstants::MERCHANT_AUTO_KYC_FAILURE_CRON_JOB_NAME, []);
        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, (new DetailCore)->getApplicableActivationStatus($merchantDetails));
    }

    protected function mockRazorxAndMerchantRiskClient()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $merchantRiskMock = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantImpersonatedDetails'])
            ->getMock();

        $this->app->instance('merchantRiskClient', $merchantRiskMock);
    }

    public function setImpersonatedMerchant()
    {
        $this->app->merchantRiskClient->method('getMerchantImpersonatedDetails')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id" => "Gz5tpWukNj9e4l",
                "fields" => [
                    [
                        "impersonation_id" => "Gz6Rhe3pKP5EXo",
                        "field" => "business_website",
                        "config_key" => "business_website",
                        "list" => "blacklist",
                        "score" => 1260,
                    ],
                ],
                "merchant_id" => "Gz5tpWukNj9e4l",
                "entity_type" => "merchant"
            ]);
    }

    public function setNonImpersonatedMerchant()
    {
        $this->app->merchantRiskClient->method('getMerchantImpersonatedDetails')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id" => "Gz5tpWukNj9e4l",
                "entity_type" => "merchant"
            ]);
    }

    public function testNoMerchantFoundAMPTriggerCMMA()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
            Entity::ACTIVATION_STATUS => 'rejected',
        ]);

        $merchantId = $merchantDetails->getId();

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $actionState = $this->fixtures->create('state', [
            'entity_id' => $merchantId,
            'entity_type' => 'merchant_detail',
            'name' => 'activated_mcc_pending',
            'created_at' => $currentCronRunTimestamp,
            'updated_at' => $currentCronRunTimestamp,
        ]);

        $collectorData = (new MerchantAutoKycPassDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        if($merchantIds === null)
        {
            $countOfMerchantIds = 0;
        }
        else
        {
            $countOfMerchantIds = count($merchantIds);
        }

        $this->assertEquals(0, $countOfMerchantIds);
    }

    public function testValidMerchantFoundAMPTriggerCMMA()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setNonImpersonatedMerchant();

        $merchantDetails = $this->fixtures->merchant_detail->create([
            Entity::POI_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::POA_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BANK_DETAILS_VERIFICATION_STATUS => POIStatus::VERIFIED,
            Entity::BUSINESS_TYPE => (new BusinessType())->getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Entity::BUSINESS_CATEGORY => 'tours_and_travel',
            Entity::BUSINESS_SUBCATEGORY => 'accommodation',
            Entity::ACTIVATION_STATUS => 'activated_mcc_pending',
        ]);

        $merchantId = $merchantDetails->getId();

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $actionState = $this->fixtures->create('state', [
            'entity_id' => $merchantId,
            'entity_type' => 'merchant_detail',
            'name' => 'activated_mcc_pending',
            'created_at' => $currentCronRunTimestamp,
            'updated_at' => $currentCronRunTimestamp,
        ]);

        $collectorData = (new MerchantAutoKycPassDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(1, count($merchantIds));
    }

    public function testFohMerchantFailingLastTransaction()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $this->fixtures->create('admin', ['email' => 'randomemail2@rzp.com', 'org_id' => Org::RZP_ORG, 'id' => 'RzrpyOrgAdmnId',]);

        $this->fixtures->create('merchant', ['id' => '10000000000000', 'hold_funds' => true]);

        $workflowAction = $this->fixtures->create('workflow_action', [
            'entity_id'         => '10000000000000',
            'entity_name'       => 'merchant',
            'approved'          => 1,
            'permission_id'     => Permission::EDIT_MERCHANT_SUSPEND,
            'workflow_id'       => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
            'state_changer_id'  => 'RzrpyOrgAdmnId'
        ]);

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'created_at' => $lastCronRunTimestamp]);

        $this->fixtures->create('feature', ['name' => 'cash_on_card', 'entity_id' => '10000000000000', 'entity_type' => 'merchant']);

        $collectorData = (new FOHRemovalDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(0, count($merchantIds));
    }

    public function testFohMerchantFailingOnHold()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $this->fixtures->create('admin', ['email' => 'randomemail2@rzp.com', 'org_id' => Org::RZP_ORG, 'id' => 'RzrpyOrgAdmnId',]);

        $this->fixtures->create('merchant', ['id' => '10000000000000', 'hold_funds' => false]);

        $workflowAction = $this->fixtures->create('workflow_action', [
            'entity_id'         => '10000000000000',
            'entity_name'       => 'merchant',
            'approved'          => 1,
            'permission_id'     => Permission::EDIT_MERCHANT_SUSPEND,
            'workflow_id'       => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
            'state_changer_id'  => 'RzrpyOrgAdmnId'
        ]);

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'created_at' => $lastCronRunTimestamp]);

        $this->fixtures->create('feature', ['name' => 'cash_on_card', 'entity_id' => '10000000000000', 'entity_type' => 'merchant']);

        $this->fixtures->create('dispute',['merchant_id' => '10000000000000', 'status' => 'won', 'deduct_at_onset' => true]);

        $collectorData = (new FOHRemovalDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(0, count($merchantIds));
    }

    public function testFohMerchantFailingDisputeFilter()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $this->fixtures->create('admin', ['email' => 'randomemail2@rzp.com', 'org_id' => Org::RZP_ORG, 'id' => 'RzrpyOrgAdmnId',]);

        $this->fixtures->create('merchant', ['id' => '10000000000000', 'hold_funds' => false]);

        $workflowAction = $this->fixtures->create('workflow_action', [
            'entity_id'         => '10000000000000',
            'entity_name'       => 'merchant',
            'approved'          => 1,
            'permission_id'     => Permission::EDIT_MERCHANT_SUSPEND,
            'workflow_id'       => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
            'state_changer_id'  => 'RzrpyOrgAdmnId'
        ]);

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'created_at' => $lastCronRunTimestamp]);

        $this->fixtures->create('feature', ['name' => 'cash_on_card', 'entity_id' => '10000000000000', 'entity_type' => 'merchant']);

        $this->fixtures->create('dispute',['merchant_id' => '10000000000000', 'status' => 'won', 'deduct_at_onset' => true]);

        $collectorData = (new FOHRemovalDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(0, count($merchantIds));
    }

    public function testFohMerchantFailingLEATagFilter()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $this->fixtures->create('admin', ['email' => 'randomemail2@rzp.com', 'org_id' => Org::RZP_ORG, 'id' => 'RzrpyOrgAdmnId',]);

        $this->fixtures->create('merchant', ['id' => '10000000000000', 'hold_funds' => false, 'tags' => "RISK_LEA_DEBIT-FREEZE_"]);

        $workflowAction = $this->fixtures->create('workflow_action', [
            'entity_id'         => '10000000000000',
            'entity_name'       => 'merchant',
            'approved'          => 1,
            'permission_id'     => Permission::EDIT_MERCHANT_SUSPEND,
            'workflow_id'       => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
            'state_changer_id'  => 'RzrpyOrgAdmnId'
        ]);

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'created_at' => $lastCronRunTimestamp]);

        $this->fixtures->create('feature', ['name' => 'cash_on_card', 'entity_id' => '10000000000000', 'entity_type' => 'merchant']);

        $this->fixtures->create('dispute',['merchant_id' => '10000000000000', 'status' => 'won', 'deduct_at_onset' => true]);

        $collectorData = (new FOHRemovalDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(0, count($merchantIds));
    }

    public function testFohMerchantFailingCapitalProductsFeaturesFilter()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $this->fixtures->create('admin', ['email' => 'randomemail2@rzp.com', 'org_id' => Org::RZP_ORG, 'id' => 'RzrpyOrgAdmnId',]);

        $this->fixtures->create('merchant', ['id' => '10000000000000', 'hold_funds' => false, 'tags' => "RISK_LEA_DEBIT-FREEZE_"]);

        $workflowAction = $this->fixtures->create('workflow_action', [
            'entity_id'         => '10000000000000',
            'entity_name'       => 'merchant',
            'approved'          => 1,
            'permission_id'     => Permission::EDIT_MERCHANT_SUSPEND,
            'workflow_id'       => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
            'state_changer_id'  => 'RzrpyOrgAdmnId'
        ]);

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('payment', ['merchant_id' => '10000000000000', 'created_at' => $lastCronRunTimestamp]);

        $this->fixtures->create('feature', ['name' => 'cash_on_card', 'entity_id' => '10000000000000', 'entity_type' => 'merchant']);

        $this->fixtures->create('dispute',['merchant_id' => '10000000000000', 'status' => 'won', 'deduct_at_onset' => true]);

        $this->fixtures->create('feature', ['name' => 'withdraw_loc', 'entity_id' => '10000000000000', 'entity_type' => 'merchant']);

        $collectorData = (new FOHRemovalDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(0, count($merchantIds));
    }

    public function testValidFohRemovalMerchant()
    {
        $this->mockRazorxAndMerchantRiskClient();

        $this->setImpersonatedMerchant();

        $this->fixtures->create('admin', [
            'email'               => 'randomemail2@rzp.com',
            'org_id'              => Org::RZP_ORG,
        ]);

        $this->fixtures->create('workflow_action', [
            'entity_id'     => '10000000000000',
            'entity_name'   => 'merchant',
            'approved'      => 1,
            'permission_id' => Permission::EDIT_MERCHANT_SUSPEND,
            'workflow_id'   => 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID,
        ]);

        $this->fixtures->create('merchant', [
            'id'            => '10000000000000',
            'hold_funds'    => true,
        ]);

        $lastCronRunTimestamp = Carbon::now()->subHours(2)->getTimestamp();

        $currentCronRunTimestamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('payment', [
            'merchant_id'   => '10000000000000',
            'created_at'    => $currentCronRunTimestamp
        ]);

        $this->fixtures->create('dispute',[
            'merchant_id'   => '10000000000000',
            'status'        => 'won',
            'deduct_at_onset' => true
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'withdraw_loc',
            'entity_id'     => '10000000000000',
            'entity_type'   => 'merchant',

        ]);

        $collectorData = (new FOHRemovalDataCollector($lastCronRunTimestamp, $currentCronRunTimestamp, []))->collectDataFromSource();

        $data = $collectorData->getData();

        $merchantIds = $data[Constants::MERCHANT_IDS] ?? null;

        $this->assertEquals(1, count($merchantIds));
    }
}
