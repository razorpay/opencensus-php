<?php

namespace Functional\TurboUpi;

use RZP\Exception\ServerErrorException;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class TurboUpiTest extends TestCase
{
    use HeimdallTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/TurboUpiTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', [
            'org_id'   => $this->org->getId(),
            'hostname' => 'dashboard.sampleorg.dev',
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    public function testFetchTurboUpiErrorMappings()
    {
        $this->setErrorMappingConfigInRedis();
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchTurboUpiErrorMappingsWhenRedisFetchFails()
    {
        $this->ba->publicAuth();
        $testData = $this->testData['testFetchTurboUpiErrorMappings'];

        $this->makeRequestAndCatchException(function() use ($testData)
        {
            $this->startTest($testData);
        },
        ServerErrorException::class,
        "Failed to fetch error mappings from Redis");
    }

    public function testSetUpiTurboErrorMappingsAdmin()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testRecordTurboUpiCustomerConsent()
    {
        $this->ba->publicAuth();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetRawContent($request);

        $this->assertEquals(201, $response->getStatusCode());

        $this->assertEquals(true, $response["success"]);
    }

    private function testTurboUpiCustomerConsentSpecificExceptionHandling($request, $expectedErrorMessage)
    {
        $this->makeRequestAndCatchException(function() use($request)
        {
            $this->makeRequestAndGetContent($request);
        },
            BadRequestValidationFailureException::class,
            $expectedErrorMessage);
    }

//  Test to check prefetch bank list
    public function testTurboUpiCustomerConsentHandlingExceptions()
    {
        $this->ba->publicAuth();

        $all_requests = $this->testData[__FUNCTION__];

        // Testing for invalid type
        $this->testTurboUpiCustomerConsentSpecificExceptionHandling($all_requests["errorFromType"]['request'], "The selected type is invalid.");

        // Testing for prefetch banks
        $this->testTurboUpiCustomerConsentSpecificExceptionHandling($all_requests["errorFromPrefetchBank"]['request'], "The metadata.prefetch bank field is required.");

        // Testing for Priority of bank
        $this->testTurboUpiCustomerConsentSpecificExceptionHandling($all_requests["errorFromBankPriority"]['request'], "The metadata.prefetch_bank.0.priority must be a string.");

        // Testing for Display name
        $this->testTurboUpiCustomerConsentSpecificExceptionHandling($all_requests["errorFromBankDisplayName"]['request'], "The metadata.prefetch_bank.1.display_name must be a string.");
    }

    public function testTurboUpiCustomerRewardEligibilityForCred()
    {
        $this->ba->publicAuth();
        $this->startTest();
    }

    protected function setErrorMappingConfigInRedis()
    {
        $errorMapping = [
            'gateways' => [
                'upi_axisolive' => [
                    "XL"  => [
                        "public_error_code"   => "BAD_REQUEST_ERROR",
                        "internal_error_code" => "BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED",
                        "error_description"   => "Payment was unsuccessful as you have breached the limit to enter UPI PIN incorrectly. Try using another method."
                    ],
                    "ZM"  => [
                        "public_error_code"   => "BAD_REQUEST_ERROR",
                        "internal_error_code" => "BAD_REQUEST_PAYMENT_PIN_INCORRECT",
                        "error_description"   => "You have entered an incorrect PIN on the UPI app. Please retry with the correct PIN."
                    ],
                    "U90" => [
                        "public_error_code"   => "BAD_REQUEST_ERROR",
                        "internal_error_code" => "BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED",
                        "error_description"   => "User account is locked."
                    ]
                ]
            ],
            'common' => [
            ],
            "fallback" => [
                "public_error_code"   => "SERVER_ERROR",
                "internal_error_code" => "FALLBACK_ERROR",
                "description"         => "We are facing some trouble completing your request at the moment. Please try again shortly.",
                "reason"              => "server_error",
                'source'              => "internal",
                "step"                => "payment_authorization"
            ]
        ];

        $errorMappingHash = hash('sha256', json_encode($errorMapping));

        (new Service())->setConfigKeys([
                                           ConfigKey::TURBO_SDK_ERROR_MAPPINGS      => $errorMapping,
                                           ConfigKey::TURBO_SDK_ERROR_MAPPINGS_HASH => $errorMappingHash
                                       ]);

    }
}
