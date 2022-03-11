<?php

namespace Unit\Models\Merchant\Detail;

use App;
use Config;
use Carbon\Carbon;
use RZP\Models\Coupon;
use RZP\Constants\Mode;
use RZP\Models\Coupon\Constants;
use RZP\Models\Merchant\Detail\Core;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Escalations;
use RZP\Services\RazorXClient;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Tests\Functional\Merchant\MerchantTest;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\Fixtures\Entity\BvsValidation;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Service as MDS;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Merchant\M2MReferral\Status as M2MEntityStatus;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\BvsValidation\Entity as BVSEntity;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\Cron as CronJobHandler;
use RZP\Models\Merchant\Document;

class CoreTest extends TestCase
{
    protected $repo;
    protected $app;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CoreTestData.php';

        parent::setUp();
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    private function createTransaction(string $merchantId, string $type, int $amount, int $createdAt = null)
    {
        if($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'          => $type,
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId,
            'created_at'    => $createdAt
        ]);
    }

    private function createPayment(string $merchantId, int $amount, int $createdAt = null)
    {
        if($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on('live')->create('payment', [
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId,
            'created_at'    => $createdAt
        ]);
    }

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes, $customBvsDetails)
    {
        $defaultMerchantAttributes = [
            'promoter_pan'            => 'BRRPK8070K',
            'promoter_pan_name'       => 'kakarla vasanthi',
            'company_pan'             => 'ABCCD1234A',
            'business_name'           => 'xyz',
            'bank_account_number'     => '1234567890',
            'bank_branch_ifsc'        => 'UTIB0002953',
            'bank_account_name'       => 'XYZ',
            'company_cin'             => 'U67190TN2014PTC096971',
            'gstin'                   => '01AADCB1234M1ZX',
        ];

        $merchantDetail = $this->fixtures->create(
            'merchant_detail:valid_fields',
            array_merge($defaultMerchantAttributes, $customMerchantAttributes));

        $mid = $merchantDetail->getId();

        $defaultVerificationDetailAttributes = [
            'merchant_id'          => $mid,
            'artefact_type'        => 'gstin',
            'artefact_identifier'  => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
            array_merge($defaultVerificationDetailAttributes, $customVerificationDetailAttributes));

        $defaultBvsDetails = [
            'owner_type'        => 'merchant',
            'owner_id'          => $mid,
            'validation_status' => BvsValidationConstants::CAPTURED,
            'platform'          => 'pg',
            'created_at'        => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $bvsValidation = $this->fixtures->create(
            'bvs_validation',
            array_merge($defaultBvsDetails, $customBvsDetails));

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::AADHAR_FRONT,
                'file_store_id' => '123123',
            ]);

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::AADHAR_BACK,
                'file_store_id' => '123123'
            ]);

        return [
            'merchant_detail'    => $merchantDetail,
            'verificationDetail' => $verificationDetail,
            'bvsValidation'      => $bvsValidation
        ];
    }

    protected function mockBvsService(string $bvsResponse)
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', $bvsResponse);
    }

    public function testBvsPartlyExecutedValidationProcessForPOIValidation()
    {
        $this->createAndFetchMocks();
        // set poi_verification_status as null and validation status as success
        $fixtures = $this->createAndFetchFixtures([
            Entity::POI_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PERSONAL_PAN,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationProcessForPOIValidationFailure()
    {
        $this->createAndFetchMocks();

        // set poi_verification_status as null and validation status as failure
        $fixtures = $this->createAndFetchFixtures([
            Entity::POI_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PERSONAL_PAN,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);


        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::FAILED, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::FAILED, $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForPersonalPanValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'poi_verification_status' => 'initiated',
        ],[],[
            'artefact_type'     => 'personal_pan',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForPersonalPanValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'personal_pan_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'personal_pan',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAVoterIdUnitProof()
    {
        $this->createAndFetchMocks();

        // set poa verification as null and test for artifact voter id with validation unit proof
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::VOTERS_ID,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::VOTER_ID_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAPassportUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PASSPORT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::PASSPORT_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAVoterIdIdentifierProof()
    {
        $this->createAndFetchMocks();

        // set poa_verification_status as null for voters id with validation unit as identifier
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::VOTERS_ID,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::VOTER_ID_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAPassportIdentifierProof()
    {
        $this->createAndFetchMocks();

        // set poa_verification_status as null for passport with validation unit identifier
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PASSPORT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::PASSPORT_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAFailed()
    {
        $this->createAndFetchMocks();

        // set poa_verification_status as null for passport with validation unit identifier
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PASSPORT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::PASSPORT_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::FAILED, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::FAILED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBusinessPanValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'company_pan_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'business_pan',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::COMPANY_PAN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBusinessPanValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'company_pan_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'business_pan',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'failed';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('failed', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('failed', $merchant->getAttribute(Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBankAccountValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'bank_details_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'bank_account',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedBankAccountValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        // set bank_details_verification_status as null with validation status success
        $fixtures = $this->createAndFetchFixtures([
            Entity::BANK_DETAILS_VERIFICATION_STATUS => null
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();


        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedBankAccountValidationUnitIdentifierFailure()
    {
        $this->createAndFetchMocks();

        // set bank_details_verification_status as null with validation status success
        $fixtures = $this->createAndFetchFixtures([
            Entity::BANK_DETAILS_VERIFICATION_STATUS => null
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();


        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::FAILED, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::FAILED, $merchant->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBankAccountValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'bank_details_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'bank_account',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForCINValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'cin_verification_status'  => 'initiated'
        ],[],[
            'artefact_type'     => 'cin',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::CIN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForLLPDeedValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'cin_verification_status'  => 'initiated'
        ],[],[
            'artefact_type'     => 'llp_deed',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'failed';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('failed', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('failed', $merchant->getAttribute(Entity::CIN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForGSTINValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'gstin_verification_status'  => 'initiated'
        ],[],[
            'artefact_type'     => 'gstin',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::GSTIN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForGSTINValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([],[
            'artefact_type'        => 'gstin',
            'artefact_identifier'  => 'doc',
        ],[
            'artefact_type'     => 'gstin',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];
        $verificationDetail = $fixtures['verificationDetail'];
        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

         $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
         $this->assertEquals('success', $bvs_validation->getValidationStatus());

         $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
             $merchantId,
             'gstin',
             'doc'
         );

         $this->assertEquals('verified', $verificationDetail->getAttribute('status'));
    }

    public function testBvsValidationProcessForMSMEValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'msme_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'msme',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::MSME_DOC_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForShopEstablishmentValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'shop_establishment_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'shop_establishment',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForShopEstablishmentValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([], [
            'artefact_type'        => 'shop_establishment',
            'artefact_identifier'  => 'doc',
        ], [
            'artefact_type'     => 'shop_establishment',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];
        $verificationDetail = $fixtures['verificationDetail'];
        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());

        $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
            $merchantId,
            'shop_establishment',
            'doc'
        );

        $this->assertEquals('verified', $verificationDetail->getAttribute('status'));
    }

    public function testBvsValidationProcessForOnlyLatestValidation()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'shop_establishment_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'shop_establishment',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation_latest = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation_latest->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));

        $bvs_validation_old = $this->fixtures->create(
            'bvs_validation',[
            'owner_type'        => 'merchant',
            'owner_id'          => $merchantId,
            'validation_status' => 'captured',
            'platform'          => 'pg',
            'created_at'        => Carbon::now()->subDays(3)->getTimestamp(),
            'artefact_type'     => 'gstin',
            'validation_unit'   => 'identifier',
        ]);

        $bvsResponse = 'failed';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation_old->getValidationId());
        $this->assertEquals('failed', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));
    }

    public function testSegmentEventPushForFirstTransaction()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }
    public function testM2MSegmentEventPushForFirstTransaction()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchant=$merchantDetail->merchant;

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $input = [
            M2MReferralEntity::MERCHANT_ID => $merchantId,
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];
        $m2m = (new \RZP\Models\Merchant\M2MReferral\Core())->createM2MReferral($merchant, $input);

        (new Escalations\Core)->handleMtuCouponApply();
    }
    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    public function testMtuCouponApplicationOnFirstTransaction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $promotionAttributes = [
            'pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ];

        $promotion = $this->fixtures->on('live')->create('promotion', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::MTU_COUPON
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $data = [
            StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::MTU_COUPON_POPUP_COUNT       => 1
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $data, StoreConstants::INTERNAL);

        (new Escalations\Core)->handleMtuCouponApply();

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP],
            StoreConstants::INTERNAL);

        $this->assertTrue($data[StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP]);

        $merchant = $this->getDbLastEntity('merchant');

        $isCouponApplied = (new Coupon\Core)->isCouponApplied($merchant, Coupon\Constants::MTU_COUPON);

        $this->assertTrue($isCouponApplied);
    }

    public function testMtuCouponApplicationOnFirstTransactionExistingPromotion()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $p = $this->fixtures->on('live')->create('promotion', [
            'name'          => 'RZPNEO',
            'product'       => 'banking',
            'credit_amount' => 0,
            'iterations'    => 1
        ]);

        $this->fixtures->on('live')->create('merchant_promotion', [
            'merchant_id'           => $merchantId,
            'promotion_id'          => $p['id'],
            'start_time'            => time(),
            'remaining_iterations'  => 1,
            'expired'               => 0
        ]);

        $promotionAttributes = [
            'pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ];

        $promotion = $this->fixtures->on('live')->create('promotion', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::MTU_COUPON
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $data = [
            StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::MTU_COUPON_POPUP_COUNT       => 1
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $data, StoreConstants::INTERNAL);

        (new Escalations\Core)->handleMtuCouponApply();

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP],
            StoreConstants::INTERNAL);

        $this->assertNull($data[StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP]);

        $merchant = $this->getDbLastEntity('merchant');

        $isCouponApplied = (new Coupon\Core)->isCouponApplied($merchant, Coupon\Constants::MTU_COUPON);

        $this->assertFalse($isCouponApplied);
    }

    public function testNonRazorpayMerchantMtuCouponApplicationOnFirstTransaction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantId, ['org_id' => Org::HDFC_ORG]);

        $promotionAttributes = [
            'pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ];

        $promotion = $this->fixtures->on('live')->create('promotion', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::MTU_COUPON
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP],
            StoreConstants::INTERNAL);

        $this->assertNull($data[StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP]);

        $merchant = $this->getDbLastEntity('merchant');

        $isCouponApplied = (new Coupon\Core)->isCouponApplied($merchant, Coupon\Constants::MTU_COUPON);

        $this->assertFalse($isCouponApplied);
    }

    public function testEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertTrue($response['showMtuPopup']);
    }
    public function testM2MNotEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $merchant=$this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $input = [
            M2MReferralEntity::MERCHANT_ID => $merchantId,
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];
        $m2m = (new \RZP\Models\Merchant\M2MReferral\Core())->createM2MReferral($merchant, $input);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }
    public function testNotEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDay()->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }

    public function testSubMerchantFalseCase()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['isSubMerchant']);
    }

    public function testSubMerchantTrueCase()
    {
        $merchantId = '1X4hRFHFx4UiXX';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => $merchantId]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertTrue($response['isSubMerchant']);
    }

    public function testNonRazorpayMerchantNotEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $this->fixtures->org->createHdfcOrg();

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp(),
            'org_id' => Org::HDFC_ORG
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }

    public function testSegmentEventPushForFirstTransactionWithUserDeviceDetail()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $this->fixtures->on('live')->create('user_device_detail', [
            'merchant_id' => $merchantDetail->getMerchantId()
        ]);

        $this->fixtures->on('live')->create('merchant_user', [
            'merchant_id' => $merchantDetail->getMerchantId()
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }

    public function testSegmentEventSkipIfNotFirstTransaction()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        // Create transaction that is 4 days old (since cron picks last 3 days transacted merchants)
        $this->createTransaction(
            $merchantId, 'payment', 10000, Carbon::now()->subDays(4)->getTimestamp());
        $this->createPayment($merchantId, 10000, Carbon::now()->subDays(4)->getTimestamp());

        // Create new transaction
        $this->createTransaction($merchantId, 'payment', 10000);
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }

    public function testSegmentEventIfTwoTransactionsDuringSameTime()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        // Create new 2 transactions
        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }


    public function testValidationFieldsIfAadhaarEsignVerificationIsDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified'
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertFalse(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarEsignVerificationIsNotDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarIsNotLinked()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'         => $mid,
            'aadhaar_linked'      => 0
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testCouponFlowForInvalidInput()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage("The code field is required");

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $input = [];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForInvalidCoupon()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_COUPON_CODE);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            'code' => "XYZ"
        ];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForCouponCodeAlreadyUsed()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_COUPON_ALREADY_USED);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode
        ];

        $this->fixtures->on('live')->create('merchant_promotion', [
            'merchant_id'           => $merchant->getId(),
            'promotion_id'          => $promotion->getId(),
            'start_time'            => time(),
            'remaining_iterations'  => 1,
            'expired'               => 0
        ]);

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForCouponCodeLimitReached()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_COUPON_LIMIT_REACHED);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $coupon->setAttribute('used_count', 200);

        $coupon->saveOrFail();

        $input = [
            'code' => $couponCode
        ];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForInvalidCreditType()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ONLY_AMOUNT_CREDITS_COUPON_APPLICABLE);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
            'credit_type'    => 'reward_fee',
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode
        ];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForSuccessCase()
    {
        $merchant = $this->fixtures->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode
        ];

        $this->fixtures->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);

        $primaryBalance = $this->getDbEntityById('merchant', $merchant->getId())->primaryBalance;

        $this->assertEquals(0, $primaryBalance->getAmountCredits());

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);

        $this->assertEquals(1000, $primaryBalance->reload()->getAmountCredits());
    }

    public function testCouponFlowForExistingCredits()
    {
        $merchant = $this->fixtures->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode1 = 'randomXYZ1';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode1,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode1,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode1
        ];

        $balance = $this->fixtures->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);

        $primaryBalance = $this->getDbEntityById('merchant', $merchant->getId())->primaryBalance;

        $this->assertEquals(0, $primaryBalance->getAmountCredits());

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);

        $this->assertEquals(1000, $primaryBalance->reload()->getAmountCredits());

        $couponCode2 = 'randomXYZ2';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode2,
            'product'        => 'primary',
            'credit_amount'  => 50,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode2,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode2
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertFalse($response['applied']);
        $this->assertEquals($response['data']['available_credits'], 1000);
    }

    public function testCouponFlowForForceExpireExistingCredits()
    {
        $merchant = $this->fixtures->on('live')->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode1 = 'randomXYZ1';

        $promotion1 = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode1,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput1 = [
            "entity_id"     => "prom_".$promotion1->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode1,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput1);

        $input = [
            'code' => $couponCode1
        ];

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);

        $primaryBalance = $this->getDbEntityById('merchant', $merchant->getId())->primaryBalance;

        $this->assertEquals(0, $primaryBalance->getAmountCredits());

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);

        $this->assertEquals(1000, $primaryBalance->reload()->getAmountCredits());

        $couponCode2 = 'randomXYZ2';

        $promotion2 = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode2,
            'product'        => 'primary',
            'credit_amount'  => 20,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion2->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode2,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode2
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertFalse($response['applied']);
        $this->assertEquals($response['data']['available_credits'], 1000);

        $token = $response['token'];

        $input = [
            'code'  => $couponCode2,
            'token' => $token
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);
        $this->assertEquals(20, $primaryBalance->reload()->getAmountCredits());
    }

    public function testCouponFlowForExistingCreditsNotThroughCoupon()
    {
        $merchant = $this->fixtures->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $existingAmountCredits = 20000;

        $credits = $this->fixtures->on('live')->create('credits', [
            'merchant_id'  => $merchant->getId(),
            'value'        => $existingAmountCredits,
            'used'         => 0,
            'campaign'     => 'OFFERMTU2',
            'type'         => 'amount',
            'promotion_id' => null,
            'expired_at'   => Carbon::now()->addYear()->timestamp,
        ]); //amount credits not through coupon flow

        $merchantBalance = $this->fixtures->on('live')->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId(),
            'credits'       => $existingAmountCredits
        ]);

        $this->assertEquals($existingAmountCredits, $merchantBalance->getAmountCredits());

        $couponCode1 = 'randomXYZ1';

        $promotion1 = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode1,
            'product'        => 'primary',
            'credit_amount'  => 999,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput1 = [
            "entity_id"     => "prom_".$promotion1->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode1,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput1);

        $input = [
            'code' => $couponCode1
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertFalse($response['applied']);
        $this->assertEquals($existingAmountCredits, $response['data']['available_credits']);

        $token = $response['token'];

        $input = [
            'code'  => $couponCode1,
            'token' => $token
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);
        $this->assertEquals(999, $merchantBalance->reload()->getAmountCredits());
    }

    public function testBusinessRegisteredStateCodeValidation()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'Maharashtra'
        ];

        (new DetailEntity)->build($input);
    }

    public function testIsMerchantTncApplicableSuccess()
    {
        $core = new DetailCore();

        $merchantId = '2cXSLlUU8V9sXl';

        $this->fixtures->create('org',[
            'id' => ORG_ENTITY::AXIS_ORG_ID,
        ]);

        $merchant = $this->fixtures->create('merchant',[
            'org_id'      => ORG_ENTITY::AXIS_ORG_ID,
            'id'          => $merchantId,
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id'      => $merchant->getId(),
        ]);

        $this->mockRazorxTreatment();

        $isMerchantTncApplicable = $core->isMerchantTncApplicable($merchant);

        $this->assertEquals(true, $isMerchantTncApplicable);
    }

    public function testBusinessRegisteredStateCodeValidationOnInvalid2DigitCode()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'XT'     // invalid state code
        ];

        (new DetailEntity)->build($input);
    }

    public function testBusinessRegisteredStateCodeValidationSuccess()
    {
        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'MH'     // valid state code
        ];

        $merchantDetail = (new DetailEntity)->build($input);

        $this->assertEquals($merchantDetail->getBusinessRegisteredState(), 'MH');
    }

    public function testActivationProgressAfterFirstLogin()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(10, $response['verification']['activation_progress']);
    }

    public function testGroupBankDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupBankDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupBankDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testGroupPromoterPanDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupedPromoterPanDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupedPromoterPanDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testGroupCompanyPanDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupedCompanyPanDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupedCompanyPanDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testAddToExistingClarificationReasonV2()
    {
        $core = new DetailCore();

        $testData = $this->testData['existingClarificationReasonV2Data'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $newClarificationReasonV2 = $this->testData['newClarificationReasonV2Data'];

        $fixedTime = (new Carbon())->timestamp(1583548200);

        Carbon::setTestNow($fixedTime);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review'
        ]);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'needs_clarification'
        ]);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review'
        ]);

        $output= $core->getUpdatedKycClarificationReasons(
            $newClarificationReasonV2,
            $merchantDetails->getId(),
            DetailConstant::ADMIN
        );

        $expectedOutput = $this->testData['updatedClarificationReasonV2Output'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testActivationProgressL1Filled()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
            'activation_form_milestone' => 'L1'
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(60, $response['verification']['activation_progress']);
    }

    public function testActivationProgressL2Filled()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
            'activation_form_milestone' => 'L2'
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(80, $response['verification']['activation_progress']);
    }

    public function testActivationProgressActivatedMCCPending()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated_mcc_pending',
        ]);

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'activated_mcc_pending',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(90, $response['verification']['activation_progress']);
    }

    public function testActivationProgressActivated()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(100, $response['verification']['activation_progress']);
    }

    public function testActivationProgressTncGenerated()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
        ]);

        $this->fixtures->create('merchant_tnc', [
            'merchant_id'           => $merchantDetails->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(85, $response['verification']['activation_progress']);
    }

    public function testL2RequiresPOI()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA1234J']);

        $this->assertEquals(false, $response);
    }

    public function testL2RequiresPoiVerificationPending()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA1234J']);

        $this->assertEquals(true, $response);
    }

    public function testL2RequiresPoiNew()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA6969J']);

        $this->assertEquals(true, $response);
    }

    public function testApplicableActivationStatus()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testActivatedMccPendingActivationStatus()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
            'submitted'=>true,
            'business_Website'=> null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
                         'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);

        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);


    }

    public function testActivatedMccPendingActivationStatusPartnership()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
                         'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);

        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);
    }

    public function testActivatedMccPendingActivationStatusCOI()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 6,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 =>true,
            'business_Website'          => null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
            'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);
        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);
    }

    public function testFetchVerificationErrorCodesNoArtefactMatch()
    {
        // when no records are matched for unsupported  artefact type
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::VOTERS_ID,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantId);
        $this->assertEmpty($error_codes);
    }

    public function testFetchVerificationErrorCodesNoErrorRecords()
    {
        // when no records are matched for supported artefact type
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantId);
        $this->assertEmpty($error_codes);
    }

    public function testFetchVerificationErrorCodesMatchDescriptionStatusFailed()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE => 'NO_PROVIDER_ERROR',
            BVSEntity::ERROR_DESCRIPTION => 'input document does not match  AadhaarBack document'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantId);
        $expectedOutput = [Entity::POA_VERIFICATION_STATUS => 'AADHAAR_BACK_NOT_MATCHED'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testFetchVerificationErrorCodesEmptyDescription()
    {
        // when records are found and error description does not exist.
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE => 'DOCUMENT_UNIDENTIFIABLE',
            BVSEntity::ERROR_DESCRIPTION => ''
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantId);
        // output falls back to the error code
        $expectedOutput = [Entity::POA_VERIFICATION_STATUS => 'AADHAAR_DOCUMENT_UNIDENTIFIABLE'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testFetchVerificationErrorCodesNotMatchDescription()
    {
        // when records are found and error description is not defined in map
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ], [], [
            BVSConstants::ARTEFACT_TYPE => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE => 'INPUT_DATA_ISSUE',
            BVSEntity::ERROR_DESCRIPTION => 'UNDEFINED IN MAP'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantId);
        // output falls back to the error code
        $expectedOutput = [Entity::POA_VERIFICATION_STATUS => 'AADHAAR_INPUT_DATA_ISSUE'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testActivatedMccPendingActivationStatusTrust()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 9,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 =>true,
            'business_Website'          => null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
            'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);
        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);

    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testM2MOfferMtuCronJob()
    {
        $this->createAndFetchMocks();
        $this->mockRazorxTreatment();

        $merchant = $this->repo->merchant->findorfail('10000000000011');
        $input = [
            M2MReferralEntity::MERCHANT_ID => '10000000000011',
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];

        $m2m = (new \RZP\Models\Merchant\M2MReferral\Core())->createM2MReferral($merchant, $input);


        (new CronJobHandler\Core())->handleCron("first-payment-offer-daily-notification", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);


    }
}
