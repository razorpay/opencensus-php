<?php

namespace Unit\Models\Merchant\Detail;

use Config;
use Mockery;
use RZP\Tests\Functional;
use Razorpay\Asv\Error\GrpcError;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\Merchant\Detail\Repository;
use RZP\Models\Merchant\Entity as MerchantEntity;
use Rzp\Accounts\Merchant\V1\MerchantDetailResponse;
use Rzp\Accounts\Merchant\V1\MerchantDetail as MerchantDetailProto;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDetail;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;


use const Grpc\STATUS_DEADLINE_EXCEEDED;

class RepositoryTest extends Functional\TestCase
{
    use Functional\AsvFindOrFailAndFindOrFailPublicTrait;

    use MocksSplitz;

    private $merchantDetailEntityJson1 = '{
        "merchant_id": "CzmiCwTPCL3t2K",
        "contact_name": "contact name",
        "contact_email": "email@test.com",
        "contact_landline": "1425168400",
        "business_type": "1",
        "business_name": "business name",
        "business_description": "business description",
        "business_dba": "business dba",
        "business_website": "https://razorpay.com",
        "additional_websites": "[\"https://razorpay.in\"]",
        "business_international": 1,
        "business_paymentdetails": "B2C",
        "business_registered_address": "B-85 Bais godam industrial area Bengaluru",
        "business_registered_address_l2": "Koramangala",
        "business_registered_state": "Karnataka",
        "business_registered_city": "Bengaluru",
        "business_registered_district": "Benga1234",
        "business_registered_pin": "560034",
        "business_registered_country": "IN",
        "business_operation_address": "B-85 Bais godam industrial area Bengaluru",
        "business_operation_address_l2": "Koramangala",
        "business_operation_state": "Karnataka",
        "business_operation_city": "Bengaluru",
        "business_operation_district": "Benga1234",
        "business_operation_pin": "560034",
        "business_operation_country": "IN",
        "business_doe": "2014-03-07",
        "gstin": "12AAAAA0000A1Z5",
        "p_gstin": "12AAAAA0000A1Z5",
        "company_cin": "U12345DL2020PLC098765",
        "company_pan": "ABCTY1234D",
        "company_pan_name": "company_pan_name",
        "business_category": "ecommerce",
        "business_subcategory": "fashion_and_lifestyle",
        "business_model": "we  are India\'s first online market place for ABC product and services",
        "transaction_volume": 3,
        "transaction_value": 10000,
        "promoter_pan": "ABCPD1234A",
        "promoter_pan_name": "promoter pan name",
        "date_of_birth": null,
        "bank_name": "bank name",
        "bank_account_number": "1234567890",
        "bank_account_name": "bank account name",
        "bank_account_type": null,
        "bank_branch": "HDFC Daltonganj",
        "bank_branch_ifsc": "HDFC0000009",
        "bank_beneficiary_address1": null,
        "bank_beneficiary_address2": null,
        "bank_beneficiary_address3": null,
        "bank_beneficiary_city": null,
        "bank_beneficiary_state": null,
        "bank_beneficiary_pin": null,
        "website_about": "http://www.example.com/aboutus.html",
        "website_contact": "http://www.example.com/contact.html",
        "website_privacy": "http://www.example.com/contact.html",
        "website_terms": "http://www.example.com/privacy.html",
        "website_refund": "http://www.example.com/refund.html",
        "website_pricing": "http://www.example.com/pricing.html",
        "website_login": "http://www.example.com/login.html",
        "business_proof_url": "http://www.example.com/login.html",
        "business_operation_proof_url": "qfejekRDYRGQmELOLisT",
        "business_pan_url": "vIzxGYKozmmUsxZdtmsY",
        "address_proof_url": "CjudQUyOTdXqNmHDRUpA",
        "promoter_proof_url": "EFyulGhkwvToOfExTMjo",
        "promoter_pan_url": "OsJweUCIMLrakfbNGkLd",
        "promoter_address_url": "yZCvTXjduiBrbmQfZezb",
        "form_12a_url": "CYEvSqGUCrjbNBIJFXWe",
        "form_80g_url": "xBFptvkUFksVFjITNtAp",
        "transaction_report_email": "test@razorpay.com",
        "role": "QImXPEyfrxIzzSCmknzL",
        "department": "nwIAejuFbQSBoaCnyzeO",
        "comment": "comment",
        "steps_finished": "[1,2,3]",
        "activation_progress": 100,
        "locked": 1,
        "activation_status": "activated",
        "poi_verification_status": "pending",
        "poa_verification_status": "initiated",
        "bank_details_verification_status": "verified",
        "activation_flow": "whitelist",
        "international_activation_flow": "whitelist",
        "clarification_mode": "email",
        "kyc_clarification_reasons": "{\"nc_count\": 1, \"additional_details\": [], \"clarification_reasons\": {\"aadhar_front\": [{\"from\": \"admin\", \"nc_count\": 1, \"created_at\": 1663228017, \"is_current\": true, \"reason_code\": \"illegible_doc\", \"reason_type\": \"predefined\"}]}, \"clarification_reasons_v2\": {\"aadhar_front\": [{\"from\": \"admin\", \"nc_count\": 1, \"created_at\": 1663228017, \"is_current\": true, \"reason_code\": \"illegible_doc\", \"reason_type\": \"predefined\"}]}}",
        "kyc_additional_details": "{\"business_description\": \"description\"}",
        "kyc_id": "10000000000000",
        "archived_at": 124,
        "reviewer_id": "reviewer_id",
        "issue_fields": "business_name",
        "issue_fields_reason": "invalid details",
        "internal_notes": "internal notes",
        "custom_fields": "{\"tnc\":{\"accepted\":1,\"ip_address\":\"201.189.12.23\",\"time\":1561110415,\"url\":\"https:\\/\\/rtll.com\\/tnc\",\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_14_4)\"},\"apps\":[{\"name\":\"Ratnalal Shopping App\",\"links\":{\"android\":\"https:\\/\\/playstore.google.com\\/appId\\/122\",\"ios\":\"https:\\/\\/appstore.com\\/appId\\/122\"}}]}",
        "marketplace_activation_status": "approved",
        "virtual_accounts_activation_status": "approved",
        "subscriptions_activation_status": "approved",
        "submitted": 1,
        "submitted_at": 1564469732,
        "created_at": 1681136761,
        "updated_at": 1681136761,
        "estd_year": 2022,
        "authorized_signatory_residential_address": "mHkGDDCoiwxyCuDWTVZL",
        "authorized_signatory_dob": "1990-02-15",
        "platform": "web",
        "fund_account_validation_id": "10000000000000",
        "date_of_establishment": "2013-01-19",
        "company_pan_verification_status": "initiated",
        "gstin_verification_status": "failed",
        "cin_verification_status": "verified",
        "personal_pan_doc_verification_status": "verified",
        "company_pan_doc_verification_status": "initiated",
        "bank_details_doc_verification_status": "failed",
        "shop_establishment_number": "123456789",
        "shop_establishment_verification_status": "pending",
        "client_applications": "{\"ios\": [{\"url\": \"appstore.acme.org\", \"name\": \"Acme\"}], \"android\": [{\"url\": \"playstore.acme.org\", \"name\": \"Acme\"}]}",
        "fraud_type": "ZojoUSAQmZJzYBtGCCoi",
        "bas_business_id": "PLCRZSJdSNPoJs",
        "msme_doc_verification_status": "xocxILgcaBZNszQLOSNu",
        "activation_form_milestone": "HoXFbNijINsPAvHXVODZ",
        "fund_addition_va_ids": "{\"fee_credit\": \"va_LIc0SnP6OMuXxH\"}",
        "iec_code": "vQLqjXFLysUxUwgazteo",
        "bank_branch_code_type": "jAVZyOKntxyGYONlebvS",
        "bank_branch_code": "wnFkbHKPuBwnNjoQlnDo",
        "industry_category_code": "GaaBIOYREOeAKgWKkfcR",
        "industry_category_code_type": "iIfMMCYyTFbVSgHjgxBo"
    }';

    private $merchantEntityJson1 = '{
        "id": "CzmiCwTPCL3t2K",
        "org_id": "100000razorpay",
        "name": "consequatur",
        "email": "test@razorpay.com",
        "account_code": null,
        "parent_id": null,
        "legal_entity_id": null,
        "activated": 0,
        "activated_at": 1687262076,
        "archived_at": null,
        "suspended_at": null,
        "live": 0,
        "live_disable_reason": null,
        "hold_funds": 0,
        "hold_funds_reason": null,
        "pricing_plan_id": null,
        "website": "http://mertz.com/est-nam-quos-iste-aliquid-vel-et-est-mollitia",
        "category": "5399",
        "international": 1,
        "product_international": "1111000000",
        "billing_label": "Test Merchant",
        "display_name": null,
        "channel": "axis",
        "transaction_report_email": "test@razorpay.com",
        "fee_bearer": 0,
        "fee_model": 0,
        "fee_credits_threshold": null,
        "amount_credits_threshold": null,
        "refund_credits_threshold": null,
        "refund_source": 0,
        "linked_account_kyc": 0,
        "has_key_access": 0,
        "partner_type": null,
        "brand_color": null,
        "handle": null,
        "activation_source": null,
        "signup_source": null,
        "business_banking": 0,
        "logo_url": null,
        "icon_url": null,
        "invoice_label_field": null,
        "risk_rating": 3,
        "risk_threshold": null,
        "receipt_email_enabled": 1,
        "receipt_email_trigger_event": 1,
        "max_payment_amount": null,
        "max_international_payment_amount": null,
        "auto_refund_delay": null,
        "default_refund_speed": "normal",
        "auto_capture_late_auth": 0,
        "convert_currency": null,
        "category2": null,
        "invoice_code": "123456789011",
        "notes": null,
        "whitelisted_ips_live": null,
        "whitelisted_ips_test": null,
        "whitelisted_domains": null,
        "second_factor_auth": 0,
        "restricted": 0,
        "dashboard_whitelisted_ips_live": null,
        "dashboard_whitelisted_ips_test": null,
        "partnership_url": null,
        "external_id": null,
        "purpose_code": null,
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "signup_via_email": 1,
        "balance_threshold": null,
        "audit_id": "M4Au4oJAxUkdNV",
        "country_code": "IN"
     }';


    /**
     * @throws \Exception
     */
    public function testMerchantDetailRepositoryFindById()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_detail_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantInDatabase($this->merchantEntityJson1);
        $this->createMerchantDetailInDatabase($this->merchantDetailEntityJson1);

        $merchantDetailEntity1 = $this->getMerchantDetailEntityFromJson($this->merchantDetailEntityJson1);
        $merchantDetailEntity1Array = $merchantDetailEntity1->toArray();
        $merchantDetailProto1 = $this->getMerchantDetailProtoFromJson($this->merchantDetailEntityJson1);

        // Test Case 1 - ExcludedRoute true - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, true, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantDetailEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 2 - SaveRoute false - Splitz off - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantDetailEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->splitzShouldThrowException(2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantDetailEntity1Array, "CzmiCwTPCL3t2K");


        // Test Case 4 - SaveRoute false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $merchantDetailEntityForFindOrFail = $repo->findOrFail("CzmiCwTPCL3t2K", ["merchant_id"]);
        $merchantDetailEntityForFindOrFailPublic = $repo->findOrFailPublic("CzmiCwTPCL3t2K", ["merchant_id"]);
        $this->assertEquals(["merchant_id" => $merchantDetailEntity1Array['merchant_id']], $merchantDetailEntityForFindOrFail->toArray());
        $this->assertEquals(["merchant_id" => $merchantDetailEntity1Array['merchant_id']], $merchantDetailEntityForFindOrFailPublic->toArray());

        // Test Case 5 - SaveRoute false - array of ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $merchantDetailEntityForFindOrFail = $repo->findOrFail(["CzmiCwTPCL3t2K"]);
        $merchantDetailEntityForFindOrFailPublic = $repo->findOrFailPublic(["CzmiCwTPCL3t2K"]);
        $merchantDetailEntityForFindOrFailArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($merchantDetailEntity1Array, $merchantDetailEntityForFindOrFail->first()->toArray());
        $merchantDetailEntityForFindOrFailPublicArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($merchantDetailEntity1Array, $merchantDetailEntityForFindOrFailPublic->first()->toArray());
        $this->assertEquals($merchantDetailEntity1Array, $merchantDetailEntityForFindOrFailArray);
        $this->assertEquals($merchantDetailEntity1Array, $merchantDetailEntityForFindOrFailPublicArray);

        $merchantDetailResponse = (new MerchantDetailResponse())->setMerchantDetail($merchantDetailProto1);

        // Test Case 6 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", $merchantDetailResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantDetailEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 7 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service - Exception occurs fallback to DB
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", null, new GrpcError(STATUS_DEADLINE_EXCEEDED, "deadline exceeded"), "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantDetailEntity1Array, "CzmiCwTPCL3t2K");


        $repo = new Repository();

        // Test Case 8 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 9 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 10 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );

        // Test Case 11 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );
    }

    public function testFilterL1NotSubmittedMerchantIds()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzExperiment($output);

        $routeMock = Mockery::mock('RZP\Http\Route')->makePartial();

        $this->app->instance('api.route', $routeMock);

        $routeMock->shouldReceive('getCurrentRouteName')->andReturn('merchant_onboarding_crons');

        $detailRepository = $this->getMockBuilder(Repository::class)
                                 ->onlyMethods(["filterL1NotSubmittedMerchantIdsFromWda"])
                                 ->getMock();

        $detailRepository->expects($this->exactly(1))->method('filterL1NotSubmittedMerchantIdsFromWda')->willReturn(["100001Razorpay", "100000Razorpay"]);

        $response = $detailRepository->filterL1NotSubmittedMerchantIds(1688539025, 1688542625);

        $this->assertCount(2, $response);
    }

    public function testFilterL2BankDetailsNotSubmittedMerchantIds()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzExperiment($output);

        $routeMock = Mockery::mock('RZP\Http\Route')->makePartial();

        $this->app->instance('api.route', $routeMock);

        $routeMock->shouldReceive('getCurrentRouteName')->andReturn('merchant_onboarding_crons');

        $detailRepository = $this->getMockBuilder(Repository::class)
                                 ->onlyMethods(["filterL2BankDetailsNotSubmittedMerchantIdsFromWda"])
                                 ->getMock();

        $detailRepository->expects($this->exactly(1))->method('filterL2BankDetailsNotSubmittedMerchantIdsFromWda')->willReturn(["100001Razorpay", "100000Razorpay"]);

        $response = $detailRepository->filterL2BankDetailsNotSubmittedMerchantIds(1688539025, 1688542625);

        $this->assertCount(2, $response);
    }

    /**
     * @throws \Exception
     */
    protected function callFindOrFailAndFindOrFailPublicAndCompare($repo, $expectedMerchantDetailArray, $merchantId)
    {
        $merchantDetailEntityForFindOrFail = $repo->findOrFail($merchantId);
        $merchantDetailEntityForFindOrFailPublic = $repo->findOrFailPublic($merchantId);
        $merchantDetailEntityForFindOrFailArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantDetailArray, $merchantDetailEntityForFindOrFail->toArray());
        $merchantDetailEntityForFindOrFailPublicArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantDetailArray, $merchantDetailEntityForFindOrFailPublic->toArray());

        $this->assertEquals($expectedMerchantDetailArray, $merchantDetailEntityForFindOrFailArray);
        $this->assertEquals($expectedMerchantDetailArray, $merchantDetailEntityForFindOrFailPublicArray);
    }


    protected function removeNonExistingKeysFromEntityFetchedFromDB(array $entityArrayExpected, array $entityArrayFromDB): array
    {
        foreach ($entityArrayFromDB as $key => $value) {
            if (array_key_exists($key, $entityArrayExpected) !== true) {
                unset($entityArrayFromDB[$key]);
            }
        }

        return $entityArrayFromDB;
    }

    protected function setEntityMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchantDetail = new MerchantDetail();
        $merchantDetailMockClient = $this->getMockClient();
        $merchantDetailMockClient->expects($this->exactly($count))->method($method)->with($id, $merchantDetail->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchantDetail->getAsvSdkClient()->setMerchantDetail($merchantDetailMockClient);
    }

    private function createMerchantDetailInDatabase($json)
    {
        $this->fixtures->create("merchant_detail",
            $this->getMerchantDetailEntityFromJson($json)->toArray(),
        );
    }

    private function createMerchantInDatabase($json)
    {
        $this->fixtures->create("merchant",
            $this->getMerchantEntityFromJson($json)->toArray(),
        );
    }

    private function getMerchantDetailProtoFromJson(string $json): MerchantDetailProto
    {
        $merchantDetailProto = new MerchantDetailProto();
        $merchantDetailProto->mergeFromJsonString($json, false);
        return $merchantDetailProto;
    }

    private function getMerchantEntityFromJson(string $json): MerchantEntity
    {
        $merchantArray = json_decode($json, true);
        $merchantEntity = new MerchantEntity();
        $merchantEntity->setRawAttributes($merchantArray);
        return $merchantEntity;
    }


    private function getMerchantDetailEntityFromJson(string $json): MerchantDetailEntity
    {
        $merchantDetailArray = json_decode($json, true);
        $merchantDetailEntity = new MerchantDetailEntity();
        $merchantDetailEntity->setRawAttributes($merchantDetailArray);
        return $merchantDetailEntity;
    }


    private function getMockClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\MerchantDetailsInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }
}
