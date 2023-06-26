<?php

namespace Unit\Models\BankingAccount;

use Mockery;
use RZP\Tests\TestCase;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Service;
use RZP\Models\BankingAccount\Activation\Detail\Region;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccountService\BasDtoAdapter;
use RZP\Models\BankingAccountService\Constants as BasConstants;
use RZP\Tests\Functional\CustomAssertions;

class BankingAccountServiceTest extends TestCase
{

    use CustomAssertions;

    protected $bankingAccountService;

    protected $bankingAccountCoreMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BankingAccountServiceTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.pincodesearcher.mock', true);
        $this->app['config']->set('applications.banking_account.mock', false);

        $this->bankingAccountCoreMock = Mockery::mock('RZP\Models\BankingAccount\Core')->makePartial();

        $this->bankingAccountService = new Service($this->app['pincodesearch'], $this->bankingAccountCoreMock);

    }

    public function testCheckServiceableByRBLValid()
    {
        $content = "110020";

        $requestResponse = [28.5388479, 77.2753728, null];

        $this->bankingAccountCoreMock->shouldReceive('getLocationFromPincode')->andReturn($requestResponse);

        $response = $this->bankingAccountService->CheckServiceableByRBL($content);

        $this->assertTrue($response['serviceability']);
    }

    public function testCheckServiceableByRBLInvalid()
    {
        $content = "110";

        $expected = [
            'serviceability' => false,
            'errorMessage'   => "PINCODE is not valid"
        ];

        $response = $this->bankingAccountService->CheckServiceableByRBL($content);

        $this->assertEquals($expected, $response);
    }

    public function testCheckServiceableByRBLGMapApiFailure()
    {
        $content = "110020";

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_INTEGRATION_ERROR);

        $response = (new Service($this->app['pincodesearch']))->CheckServiceableByRBL($content);

        $this->assertTrue($response['serviceability']);
    }

    public function testStateToRegionMapping()
    {
        // case-sensitive check
        $region = (new Region)->getRegionFromState(Region::Maharashtra);

        $this->assertEquals(Region::WEST, $region);

        // case-insensitive check
        $region = (new Region)->getRegionFromState('maHarasHTrA');

        $this->assertEquals(Region::WEST, $region);
    }

    public function testApiToBasDtoAdapter()
    {
        $testData = $this->testData['testApiToBasDtoAdapter'];

        $input = $testData['apiInput'];

        $basInput = (new BasDtoAdapter)->transformApiInputToBasInput($input);

        $expectedOutput = $testData['expectedBasInput'];

        $this->assertArraySelectiveEquals($expectedOutput, $basInput);

        $input['activation_detail']['comment'] = [
            'comment' => 'sample comment while changing assignee',
            'source_team' => 'ops',
            'source_team_type' => 'internal',
            'type' => 'internal',
            'added_at' => 1597217557,
        ];

        $expectedOutput['banking_account_application']['comment_input'] = [
            'comment' => 'sample comment while changing assignee',
            'source_team' => 'ops',
            'source_team_type' => 'internal',
            'type' => 'internal',
            'added_at' => 1597217557000,
        ];

        $basInput = (new BasDtoAdapter)->transformApiInputToBasInput($input);

        $this->assertArraySelectiveEquals($expectedOutput, $basInput);
    }

    public function testBasResponseToApiResponseAdapter()
    {
        $testData = $this->testData['testBasResponseToApiResponseAdapter'];

        $basResponse = $testData['basResponse'];

        $expectedResponse = $testData['expectedApiResponse'];

        $apiResponse = (new BasDtoAdapter)->fromBasResponseToApiResponse($basResponse);

        $additionalDetails = json_decode($apiResponse['banking_account_activation_details']['additional_details'], true);

        $apiResponse['banking_account_activation_details']['additional_details'] = $additionalDetails;

        $this->assertArraySelectiveEquals($expectedResponse, $apiResponse);
    }

    public function testFreshDeskRequired()
    {

        $activationDetail = [
            ActivationDetail\Entity::MERCHANT_POC_NAME => 'name',
            ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'designation',
            ActivationDetail\Entity::MERCHANT_POC_EMAIL => 'email@gmail.com',
            ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER => '1234567890',
            ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'doc address',
            ActivationDetail\Entity::MERCHANT_CITY => 'Bengaluru',
            ActivationDetail\Entity::MERCHANT_REGION => 'East',
            ActivationDetail\Entity::COMMENT => 'Sample comment',
            ActivationDetail\Entity::SALES_TEAM => 'sme',
            ActivationDetail\Entity::BUSINESS_NAME => 'businessname',
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'sole_proprietorship',
            ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
            ActivationDetail\Entity::SALES_POC_PHONE_NUMBER => '1234567890',
            ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => '123456',
            ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => '123456',
            ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => '123456',
            ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => true,
            ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                ActivationDetail\Entity::GREEN_CHANNEL => false
            ],
        ];

        $core = (new BankingAccount\Core());

        $validator = new ActivationDetail\Validator();
        
        $core = (new BankingAccount\Core());

        $requiredFields = $validator->getRequiredActivationDetailsKeysFreshDesk();

        $check = $core->checkRequiredFieldsPresentForFreshDeskTicket(
            $activationDetail,
            $requiredFields,
            'freshDeskActivationDetails',
            $validator);

        $this->assertTrue($check);

        $activationDetail[ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE] = '';

        $check = $core->checkRequiredFieldsPresentForFreshDeskTicket(
            $activationDetail,
            $requiredFields,
            'freshDeskActivationDetails',
            $validator);

        $this->assertFalse($check);
    }
}
