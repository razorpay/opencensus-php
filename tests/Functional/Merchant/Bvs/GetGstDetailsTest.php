<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use App;
use Config;
use Mail;
use RZP\Trace\TraceCode;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Store\Core;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Store\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use Rzp\Bvs\Probe\V1\GetGstDetailsResponse;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\BvsValidation\Repository;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class GetGstDetailsTest extends TestCase
{

    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/GetGstDetailsTestData.php';

        parent::setUp();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::KARZA_BANK_ACCOUNT_VERIFICATION or
                                      $feature === RazorxTreatment::BVS_PENNY_TESTING)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    public function testGetGstDetailsSuccess()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::SUCCESS);

        $this->startTest();

        $keys = [
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantDetail['merchant_id'],
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        Constants::INTERNAL);

        $this->assertNotNull($data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT]);
    }

    public function testGetGstDetailsSuccessFromStore()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchantDetail['merchant_id']);
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        $data = [
            Constants::NAMESPACE            => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::GST_DETAILS_FROM_PAN => json_encode(["22AAACR5055K1ZH",
                                                            "03AAACR5055K2ZG"
                                                           ]),
        ];

        $data = (new StoreCore())->updateMerchantStore('BRRPK8070K', $data, Constants::INTERNAL);

        $data = [
            Constants::NAMESPACE                              => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => 1,
        ];

        $data = (new StoreCore())->updateMerchantStore($merchantDetail["merchant_id"], $data, Constants::INTERNAL);

        $this->startTest();

        $keys = [
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantDetail['merchant_id'],
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        Constants::INTERNAL);

        $this->assertNotNull($data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT]);
        $this->assertEquals(2, $data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT]);

    }

    public function testGetGstDetailsFailure()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::FAILURE);

        $this->startTest();
    }

    public function testGetGstDetailsRateLimitExhausted()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);

        $data = [
            Constants::NAMESPACE                              => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => DetailConstants::GET_GST_DETAILS_MAX_ATTEMPT + 1
        ];

        (new Core())->updateMerchantStore($merchantDetail['merchant_id'], $data, Constants::INTERNAL);

        $this->startTest();
    }

    public function testGetGstDetailsInvalidBusinessType()
    {
        $merchantDetailsData = [
            'business_type' => 5,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);

        $this->startTest();
    }

    public function testSaveActivationDetailsWithBankDetails()
    {

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
        ]);

        $merchantId = $merchantDetails["merchant_id"];

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Constant::BANK_ACCOUNT, $bvsValidation->getArtefactType());
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(1, $pennyTestingAttemptsCount);

    }

    public function testSaveActivationDetailsWithDifferentBankDetails()
    {

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
            'bank_account_name'         => 'Test1',
            'bank_account_number'       => '111001',
            'bank_branch_ifsc'          => 'SBIN0007105',
        ]);

        $merchantId = $merchantDetails["merchant_id"];

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Constant::BANK_ACCOUNT, $bvsValidation->getArtefactType());
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(1, $pennyTestingAttemptsCount);

    }

    public function testSaveActivationDetailsWithoutBankDetails()
    {

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
            'bank_account_name'         => 'Test',
            'bank_account_number'       => '111001',
            'bank_branch_ifsc'          => 'SBIN0007105',
        ]);

        $merchantId = $merchantDetails["merchant_id"];

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNotEmpty($bvsValidation);

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(1, $pennyTestingAttemptsCount);

    }

    public function testSaveActivationDetailsWithBankDetailsLimitBreached()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
            'bank_account_name'         => 'Test1',
            'bank_account_number'       => '111001',
            'bank_branch_ifsc'          => 'SBIN0007105',
            'bank_account_type'         => 'savings',
        ]);

        $merchantId = $merchantDetails['merchant_id'];

        $this->fixtures->on('live')->edit('merchant', $merchantId);

        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        $data = [
            StoreConstants::NAMESPACE                          => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => 10
        ];

        $data = (new StoreCore())->updateMerchantStore($merchantId,
                                                       $data,
                                                       StoreConstants::INTERNAL);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNull($bvsValidation);

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(10, $pennyTestingAttemptsCount);
    }

    public function testSaveActivationDetailsWithoutBankDetailsLimitBreached()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
            'bank_account_name'         => 'Test',
            'bank_account_number'       => '111001',
            'bank_branch_ifsc'          => 'SBIN0007105',
        ]);

        $merchantId = $merchantDetails['merchant_id'];

        $this->fixtures->on('live')->edit('merchant', $merchantId);

        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        $data = [
            StoreConstants::NAMESPACE                          => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => 10
        ];

        $data = (new StoreCore())->updateMerchantStore($merchantId,
                                                       $data,
                                                       StoreConstants::INTERNAL);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNull($bvsValidation);

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(10, $pennyTestingAttemptsCount);
    }

    public function testSubmitFormPennyTestingLimitBreached()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
            'bank_account_name'         => 'Test1',
            'bank_account_number'       => '111001',
            'bank_branch_ifsc'          => 'SBIN0007105',
            'bank_account_type'         => 'savings',
        ]);

        $merchantId = $merchantDetails['merchant_id'];

        $this->fixtures->on('live')->edit('merchant', $merchantId);

        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        $data = [
            StoreConstants::NAMESPACE                          => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => 10
        ];

        $data = (new StoreCore())->updateMerchantStore($merchantId,
                                                       $data,
                                                       StoreConstants::INTERNAL);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNull($bvsValidation);

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(10, $pennyTestingAttemptsCount);
    }

    public function testSubmitFormPennyTestingLimitNotBreachedWithoutPreviousInSyncBVSCall()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'instantly_activated',
            'bank_account_name'         => 'Test1',
            'bank_account_number'       => '111001',
            'bank_branch_ifsc'          => 'SBIN0007105',
            'bank_details_verification_status'=>'failed'
        ]);

        $merchantId = $merchantDetails['merchant_id'];

        $this->fixtures->on('live')->edit('merchant', $merchantId);

        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId,'merchant','bank_account');
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Constant::BANK_ACCOUNT, $bvsValidation->getArtefactType());
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(1, $pennyTestingAttemptsCount);
    }

    public function testSubmitFormPennyTestingLimitNotBreachedWithPreviousInSyncBVSCall()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                    => 4,
            'business_category'                => 'financial_services',
            'business_subcategory'             => 'accounting',
            'activation_flow'                  => 'whitelist',
            'activation_form_milestone'        => 'L1',
            'poi_verification_status'          => 'pending',
            'promoter_pan'                     => 'AAAPA1234J',
            'activation_status'                => 'instantly_activated',
            'bank_account_name'                => 'Test1',
            'bank_account_number'              => '111001',
            'bank_branch_ifsc'                 => 'SBIN0007105',
            'bank_details_verification_status' => 'failed'
        ]);

        $merchantId = $merchantDetails['merchant_id'];

        $this->fixtures->on('live')->edit('merchant', $merchantId);

        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        $this->startTest();

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantId, 'merchant', 'bank_account');
        $this->assertNotEmpty($bvsValidation);

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantId,
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $this->assertEquals(1, $pennyTestingAttemptsCount);
    }
}
