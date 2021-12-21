<?php

namespace RZP\Tests\Functional\Merchant\BvsVerification;

use DB;
use Mail;
use Config;
use Mockery;

use RZP\Constants;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\EsDao;
use RZP\Models\Merchant\Core;
use RZP\Models\User\Role;
use RZP\Services\DiagClient;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Mail\Merchant\Rejection;
use Functional\Helpers\BvsTrait;
use Illuminate\Http\UploadedFile;
use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Mail\Merchant\MerchantDashboardEmail;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Merchant\Bvs\BvsValidationTest;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Document\Entity as MerchantDocuments;
use RZP\Models\Workflow\Action\Repository as ActionRepository;
use RZP\Mail\Merchant\RazorpayX\AccountActivationConfirmation;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;


class MerchantDetailTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BankAccountVerificationTestData.php';

        parent::setUp();
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
            "merchantCoreMock" => $mockMC
        ];
    }

    protected function mockHubSpotClient($methodName, $times = 1)
    {
        $hubSpotMock = $this->getMockBuilder(HubspotClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods([$methodName])
                            ->getMock();

        $this->app->instance('hubspot', $hubSpotMock);

        $hubSpotMock->expects($this->exactly($times))
                    ->method($methodName);
    }

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::KARZA_BANK_ACCOUNT_VERIFICATION)
                                  {
                                      return 'on';
                                  }

                                  return 'on';
                              }));
    }

    protected function disableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::KARZA_BANK_ACCOUNT_VERIFICATION)
                                  {
                                      return 'off';
                                  }

                                  return 'on';
                              }));
    }

    public function testFillingBankDetailsWhenEmptyExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant_detail',
                                                            [
                                                                'merchant_id' => $merchant->getId()
                                                            ]);

        $this->disableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);
    }

    public function testFillingBankDetailsWhenEmptyExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant_detail',
                                                            [
                                                                'merchant_id' => $merchant->getId()
                                                            ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNotNull($bvsValidation);
    }

    public function testFillingOtherDetailsWhenEmptyExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant_detail',
                                                            [
                                                                'merchant_id'                      => $merchant->getId(),
                                                                'bank_details_verification_status' => 'initiated'
                                                            ]);

        $this->disableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();
        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);

    }

    public function testFillingOtherDetailsWhenEmptyExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant_detail',
                                                            [
                                                                'merchant_id'                      => $merchant->getId(),
                                                                'bank_details_verification_status' => 'pending'
                                                            ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();
        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);
    }

    public function testL2SubmitWhenBankVerifiedExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant_detail:valid_fields',
                                                            [
                                                                'merchant_id'                      => $merchant->getId(),
                                                                'bank_branch_ifsc'                 => 'CBIN0281697',
                                                                'bank_account_number'              => '0002020000304030434',
                                                                'bank_details_verification_status' => 'verified'
                                                            ]);

        $this->disableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();
        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);

    }

    public function testL2SubmitWhenBankVerifiedExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant_detail:valid_fields',
                                                            [
                                                                'merchant_id'                      => $merchant->getId(),
                                                                'bank_branch_ifsc'                 => 'CBIN0281697',
                                                                'bank_account_number'              => '0002020000304030434',
                                                                'bank_details_verification_status' => 'verified'
                                                            ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();
        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);
    }

    public function testL2SubmitWhenBankNotVerifiedExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->create('merchant');
        $merchant = $this->fixtures->create('merchant_detail:valid_fields',
                                            [
                                                'merchant_id'                      => $merchant->getId(),
                                                'bank_branch_ifsc'                 => 'CBIN0281697',
                                                'bank_account_number'              => '0002020000304030434',
                                                'bank_details_verification_status' => 'pending',
                                                'activation_form_milestone'        => 'L1'
                                            ]);

        $this->disableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNotNull($bvsValidation);
    }

    public function testL2SubmitWhenBankNotVerifiedExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->create('merchant');
        $merchant = $this->fixtures->create('merchant_detail:valid_fields',
                                            [
                                                'merchant_id'                      => $merchant->getId(),
                                                'bank_branch_ifsc'                 => 'CBIN0281697',
                                                'bank_account_number'              => '0002020000304030434',
                                                'bank_details_verification_status' => 'pending',
                                                'activation_form_milestone'        => 'L1'
                                            ]);
        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNotNull($bvsValidation);

    }

    public function testNCBankDetailsSubmitWhenBankNotVerifiedExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant        = $this->fixtures->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'not_matched',
                                                       'activation_status'                => 'needs_clarification',
                                                       'kyc_clarification_reasons'        => [
                                                           'clarification_reasons' => [],
                                                           'additional_details'    => [
                                                               'bank_account_number' => [[
                                                                                             'reason_type' => 'predefined',
                                                                                             'field_value' => '0002020000304030434',
                                                                                             'reason_code' => 'unable_to_validate_acc_number',
                                                                                             'from'        => 'system'
                                                                                         ]]
                                                           ]
                                                       ],
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0
                                                   ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNotNull($bvsValidation);

    }

    public function testNCBankDetailsSubmitWhenBankNotVerifiedExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant        = $this->fixtures->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'not_matched',
                                                       'activation_status'                => 'needs_clarification',
                                                       'kyc_clarification_reasons'        => [
                                                           'clarification_reasons' => [],
                                                           'additional_details'    => [
                                                               'bank_account_number' => [[
                                                                                             'reason_type' => 'predefined',
                                                                                             'field_value' => '0002020000304030434',
                                                                                             'reason_code' => 'unable_to_validate_acc_number',
                                                                                             'from'        => 'system'
                                                                                         ]]
                                                           ]
                                                       ],
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0
                                                   ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNotNull($bvsValidation);

    }

    public function testNCOtherDetailsSubmitWhenBankVerifiedExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant        = $this->fixtures->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'verified',
                                                       'promoter_pan_name'=>'BRRPK8070A',
                                                       'activation_status'                => 'needs_clarification',
                                                       'kyc_clarification_reasons'        => [
                                                           'clarification_reasons' => [
                                                               'promoter_pan_name' => [[
                                                                                                                   'reason_type' => 'predefined',
                                                                                                                   'field_value' => 'adnakdad',
                                                                                                                   'reason_code' => 'signatory_name_not_matched',
                                                                                                                   'is_current'  => true,
                                                                                                                   'from'        => 'admin'
                                                                                                               ],
                                                           ],
                                                               ],
                                                           'additional_details'    => []
                                                       ],
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0
                                                   ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);

    }

    public function testNCOtherDetailsSubmitWhenBankVerifiedExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant        = $this->fixtures->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'verified',
                                                       'promoter_pan_name'=>'BRRPK8070A',
                                                       'activation_status'                => 'needs_clarification',
                                                       'kyc_clarification_reasons'        => [
                                                           'clarification_reasons' => [
                                                               'promoter_pan_name' => [[
                                                                                           'reason_type' => 'predefined',
                                                                                           'field_value' => 'adnakdad',
                                                                                           'reason_code' => 'signatory_name_not_matched',
                                                                                           'is_current'  => true,
                                                                                           'from'        => 'admin'
                                                                                       ],
                                                               ],
                                                           ],
                                                           'additional_details'    => []
                                                       ],
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0
                                                   ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);

    }

    public function testNCOtherDetailsSubmitWhenBankNotVerifiedExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant        = $this->fixtures->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'incorrect_details',
                                                       'promoter_pan_name'=>'BRRPK8070A',
                                                       'activation_status'                => 'needs_clarification',
                                                       'kyc_clarification_reasons'        => [
                                                           'clarification_reasons' => [
                                                               'promoter_pan_name' => [[
                                                                                           'reason_type' => 'predefined',
                                                                                           'field_value' => 'adnakdad',
                                                                                           'reason_code' => 'signatory_name_not_matched',
                                                                                           'is_current'  => true,
                                                                                           'from'        => 'admin'
                                                                                       ],
                                                               ],
                                                           ],
                                                           'additional_details'    => []
                                                       ],
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0
                                                   ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);

    }

    public function testNCOtherDetailsSubmitWhenBankNotVerifiedExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant        = $this->fixtures->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'incorrect_details',
                                                       'promoter_pan_name'=>'BRRPK8070A',
                                                       'activation_status'                => 'needs_clarification',
                                                       'kyc_clarification_reasons'        => [
                                                           'clarification_reasons' => [
                                                               'promoter_pan_name' => [[
                                                                                           'reason_type' => 'predefined',
                                                                                           'field_value' => 'adnakdad',
                                                                                           'reason_code' => 'signatory_name_not_matched',
                                                                                           'is_current'  => true,
                                                                                           'from'        => 'admin'
                                                                                       ],
                                                               ],
                                                           ],
                                                           'additional_details'    => []
                                                       ],
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0
                                                   ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);

    }

    public function testFillingBankAccountNameAfterL2ExperimentOff()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'verified',
                                                       'bank_account_name'=>'vasanthi kakarla',
                                                       'activation_status'                => 'under_review',
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0,
                                                       'activation_form_milestone'        => 'L1'
                                                   ]);
        $this->disableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);
    }

    public function testFillingBankAccountNameAfterL2ExperimentOn()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant');
        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   [
                                                       'merchant_id'                      => $merchant->getId(), 'activation_form_milestone' => 'L2',
                                                       'bank_branch_ifsc'                 => 'CBIN0281697',
                                                       'bank_account_number'              => '0002020000304030434',
                                                       'bank_details_verification_status' => 'verified',
                                                       'bank_account_name'=>'vasanthi kakarla',
                                                       'activation_status'                => 'under_review',
                                                       'submitted'                        => 1,
                                                       'locked'                           => 0,
                                                       'activation_form_milestone'        => 'L1'
                                                   ]);
        $this->disableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchant->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'bank_account',
                                             'validation_unit' => 'identifier']);

        $this->assertNull($bvsValidation);
    }



}
