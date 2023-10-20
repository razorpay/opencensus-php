<?php

namespace Unit\Models\Merchant\Website;

use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantDocumentSaveRequest;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteSaveRequest;
use Unit\Models\Merchant\TestingHelper\RepositoryTestHelper;
use Rzp\Accounts\Merchant\V1\EntitySaveResponse;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Entity as MerchantEntity;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteResponse;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteResponseByMerchantId;
use Rzp\Accounts\Merchant\V1\SaveRequest;
use Rzp\Accounts\Merchant\V1\SaveResponse;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\WriteEnabledOnAsv;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use RZP\Models\Merchant\Website\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;

class RepositoryTest extends RepositoryTestHelper
{

    private $websiteEntityJson1 = '{
                        "id": "K9UzmvitzJwyS4",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                        "deliverable_type": "1",
                        "shipping_period":  "3-5 days",
                        "refund_request_period": "7 days",
                        "warranty_period": "123 days",
                        "merchant_website_details": "{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "admin_website_details": "{\"website\": {\"https://example.com/\": {\"terms\": {\"url\": \"https://www.hello.com/\"}, \"pricing\": {\"url\": \"\"}, \"privacy\": {\"url\": \"\"}, \"about_us\": {\"url\": \"https://hello.com/about_us\"}, \"comments\": \"sss\", \"contact_us\": {\"url\": \"https://www.hello.com/\"}}}}",
                        "additional_data":"{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "status": "submitted",
                        "grace_period": 1,
                        "send_communication": 1,
                        "refund_process_period": "23days",
                        "created_at":1234,
                        "updated_at":1234
           }';

    private $websiteEntityJson2 = '{
                        "id": "K9UzmvitzJwyS5",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                        "deliverable_type": "1",
                        "shipping_period":  "3-6 days",
                        "refund_request_period": "7 days",
                        "warranty_period": "123 days",
                        "merchant_website_details": "{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "admin_website_details": "{\"website\": {\"https://example.com/\": {\"terms\": {\"url\": \"https://www.hello.com/\"}, \"pricing\": {\"url\": \"\"}, \"privacy\": {\"url\": \"\"}, \"about_us\": {\"url\": \"https://hello.com/about_us\"}, \"comments\": \"sss\", \"contact_us\": {\"url\": \"https://www.hello.com/\"}}}}",
                        "additional_data":"{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "status": "submitted",
                        "grace_period": 1,
                        "send_communication": 1,
                        "refund_process_period": "23days",
                        "created_at":12345,
                        "updated_at":1234
           }';

    private $websiteEntityJson3 = '{
                        "id": "K9UzmvitzJwyS3",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                        "deliverable_type": "1",
                        "shipping_period":  "3-6 days",
                        "refund_request_period": "7 days",
                        "warranty_period": "123 days",
                        "merchant_website_details": "{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "admin_website_details": "{\"website\": {\"https://example.com/\": {\"terms\": {\"url\": \"https://www.hello.com/\"}, \"pricing\": {\"url\": \"\"}, \"privacy\": {\"url\": \"\"}, \"about_us\": {\"url\": \"https://hello.com/about_us\"}, \"comments\": \"sss\", \"contact_us\": {\"url\": \"https://www.hello.com/\"}}}}",
                        "additional_data":"{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "status": "submitted",
                        "grace_period": 1,
                        "send_communication": 1,
                        "refund_process_period": "23days",
                        "created_at":12346,
                        "updated_at":1234
           }';

    private $sampleSpltizOutput = [
        'status_code' => 200,
        'response' => [
            'id' => '10000000000000',
            'project_id' => 'K1ZCHBSn7hbCMN',
            'experiment' => [
                'id' => 'K1ZaAGS9JfAUHj',
                'name' => 'CallSyncDviationAPI',
                'exclusion_group_id' => '',
            ],
            'variant' => [
                'id' => 'K1ZaAHZ7Lnumc6',
                'name' => 'Dummy Enabled',
                'variables' => [
                    [
                        'key' => 'enabled',
                        'value' => 'true',
                    ]
                ],
                'experiment_id' => 'K1ZaAGS9JfAUHj',
                'weight' => 100,
                'is_default' => false
            ],
            'Reason' => 'bucketer',
            'steps' => [
                'sampler',
                'exclusion',
                'audience',
                'assign_bucket'
            ]
        ]
    ];

    private $merchantEntityJson1 = '{
        "id": "K4O9sCGihrL2bG",
        "org_id": "100000razorpay",
        "default_refund_speed": "normal",
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "country_code": "IN"
     }';

    private $merchantDetailEntityJson1 = '{
        "merchant_id": "K4O9sCGihrL2bG",
        "additional_websites": "[\"https://razorpay.in\"]",
        "steps_finished": "[1,2,3]",
        "kyc_clarification_reasons": "{\"nc_count\": 1, \"additional_details\": [], \"clarification_reasons\": {\"aadhar_front\": [{\"from\": \"admin\", \"nc_count\": 1, \"created_at\": 1663228017, \"is_current\": true, \"reason_code\": \"illegible_doc\", \"reason_type\": \"predefined\"}]}, \"clarification_reasons_v2\": {\"aadhar_front\": [{\"from\": \"admin\", \"nc_count\": 1, \"created_at\": 1663228017, \"is_current\": true, \"reason_code\": \"illegible_doc\", \"reason_type\": \"predefined\"}]}}",
        "kyc_additional_details": "{\"business_description\": \"description\"}",
        "custom_fields": "{\"tnc\":{\"accepted\":1,\"ip_address\":\"201.189.12.23\",\"time\":1561110415,\"url\":\"https:\\/\\/rtll.com\\/tnc\",\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_14_4)\"},\"apps\":[{\"name\":\"Ratnalal Shopping App\",\"links\":{\"android\":\"https:\\/\\/playstore.google.com\\/appId\\/122\",\"ios\":\"https:\\/\\/appstore.com\\/appId\\/122\"}}]}",
        "client_applications": "{\"ios\": [{\"url\": \"appstore.acme.org\", \"name\": \"Acme\"}], \"android\": [{\"url\": \"playstore.acme.org\", \"name\": \"Acme\"}]}",
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "fund_addition_va_ids": "{\"fee_credit\": \"va_LIc0SnP6OMuXxH\"}",
        "industry_category_code_type": "iIfMMCYyTFbVSgHjgxBo"
    }';


    public function testGetWebsiteDetailsForMerchantId()
    {
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson1);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson2);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson3);


        $merchantWebsite = new MerchantWebsite();

        // prepare expected data //
        $websiteEntity3 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson3);

        // prepare mocks //
        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);
        $merchantWebsiteProto2 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson2);
        $merchantWebsiteProto3 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson3);

        $merchantWebsiteResponse = new MerchantWebsiteResponseByMerchantId();
        $merchantWebsiteResponse->setWebsites([$merchantWebsiteProto3, $merchantWebsiteProto2,$merchantWebsiteProto1]);


        // Test1: Splitz is on(No effect should be whether splitz is on or off), request should go to account service. -
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bG", $merchantWebsite->getDefaultRequestMetaData())->willReturn([$merchantWebsiteResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        $repo = new Repository();
        $website = $repo->getWebsiteDetailsForMerchantId("K4O9sCGihrL2bG");
        self::assertEquals($websiteEntity3->toArray(), $website->toArray());

        // test2: Splitz is off(No effect should be whether splitz is on or off), request  should go to account service.
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bG", $merchantWebsite->getDefaultRequestMetaData())->willReturn([$merchantWebsiteResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        $repo = new Repository();
        $website = $repo->getWebsiteDetailsForMerchantId("K4O9sCGihrL2bG");
        $website['audit_id'] = "testtesttest";
        self::assertEquals($websiteEntity3->toArray(), $website->toArray());

    }

    public function assertEqualsAssociativeByKey($array1, $array2, $key = "id") {
        $compareArray1 = [];
        $compareArray2 = [];

        foreach ($array1 as $item) {
            $compareArray1[$item[$key]] = $item;
        }

        foreach ($array2 as $item) {
            $compareArray2[$item[$key]] = $item;
        }

        self::assertEquals($compareArray1, $compareArray2);
    }
    public function testWebsiteRepositoryFindRequestNotRoutedToAsv()
    {
        $repo = new Repository();

        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson1);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson2);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson3);

        $websiteEntity1 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson1);
        $websiteEntity2 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson2);
        $websiteEntity3 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson3);


        // Find, FindOrFail & FindOrFail public should work fine if we give columns value, Fetch data from DB
        $this->setSplitzWithOutput("false", 0);
        $this->assertEquals(["id" => $websiteEntity3->getId()], $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS3", ["id"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids, Fetch data from DB
        $this->setSplitzWithOutput("false", 0);
        $this->assertEqualsAssociativeByKey([$websiteEntity3->toArray(), $websiteEntity2->toArray()], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS5", "K9UzmvitzJwyS3"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids and columns, Fetch data from DB
        $this->setSplitzWithOutput("false",0);
        $this->assertEqualsAssociativeByKey([["id" => "K9UzmvitzJwyS5"], ["id" => "K9UzmvitzJwyS3"]], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS3", "K9UzmvitzJwyS5"], ["id"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids and columns, Fetch data from DB
        $this->setSplitzWithOutput("false",0);
        $this->assertEqualsAssociativeByKey([["id" => "K9UzmvitzJwyS5"], ["id" => "K9UzmvitzJwyS3"]], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS3", "K9UzmvitzJwyS5"], ["id"]));
    }

    public function testMerchantWebsiteFindOrFailRequestRoutedToAsv() {
        $repo = new Repository();

        $merchantWebsite = new MerchantWebsite();

        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson1);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson2);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson3);

        $websiteEntity1 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson1);
        $websiteEntity2 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson2);
        $websiteEntity3 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson3);

        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);
        $merchantWebsiteProto2 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson2);
        $merchantWebsiteProto3 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson3);

        $merchantWebsiteResponse = (new MerchantWebsiteResponse())->setWebsite($merchantWebsiteProto1);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on.
        $this->setSplitzWithOutput("true", 0); // Splitz should never be called
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", $merchantWebsiteResponse, null,"getById", 2);
        $response = $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($websiteEntity1->toArray(), $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, "K9UzmvitzJwyS4"), $response);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, asv gives exception.
        $this->setSplitzWithOutput("true", 0);  // Splitz should never be called
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", null, new GrpcError(\Grpc\STATUS_DEADLINE_EXCEEDED, "test"),"getById", 2);
        $response = $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($websiteEntity1->toArray(), $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, "K9UzmvitzJwyS4"), $response);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, array of ids.
        $this->setSplitzWithOutput("true", 0);  // Splitz should never be called
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", $merchantWebsiteResponse, null,"getById", 0);
        $response = $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS4"]);
        $this->assertEquals([$websiteEntity1->toArray()], $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, ["K9UzmvitzJwyS4"]), $response);

        // Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

        // Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );
    }
    public function testMerchantWebsiteSaveOrFail() {


        /*
         *  Base Setup for the test
         *
         */

        $repo = new Repository();
        $merchantWebsite = new MerchantWebsite();

        $websiteEntity1 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson1);
        $websiteEntity2 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson2);
        $websiteEntity3 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson3);

        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);
        $merchantWebsiteProto1->setCreatedAt(0);
        $merchantWebsiteProto1->setUpdatedAt(0);
        $merchantWebsiteProto2 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson2);
        $merchantWebsiteProto2->setCreatedAt(0);
        $merchantWebsiteProto2->setUpdatedAt(0);
        $merchantWebsiteProto3 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson3);
        $merchantWebsiteProto3->setCreatedAt(0);
        $merchantWebsiteProto3->setUpdatedAt(0);

        $saveResponse = (new SaveResponse())->setMerchantWebsite(new EntitySaveResponse(
            [
                "id" => "K9UzmvitzJwyS4",
                "created_at" => 10,
                "updated_at" => 10,
                "audit_id" => "newtesttesttest"
            ]
        ));

        /*
         * Test 1: The save or fail ASV should not be reached if write is not enabled.
         * Comment this testcase when writes are to be enabled.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = false;
        $repo->saveOrFail($websiteEntity1);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);

        /*
        * Test  2: The save or fail ASV should not be reached if Splitz is off.
        */

        // false, false
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["false", "false"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity2);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);

        // true, false
        $this->setSplitzWithOutputForBulk(["true", "false"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity2);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);

        // false, true
        $this->setSplitzWithOutputForBulk(["false", "true"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity2);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);

        /*
        * Test  3: The save or fail ASV should not be reached if Splitz throws exception.
        */
        $this->setSplitzWithOutputForBulk(["false", "true"], 1, true);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity2);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);

        /*
        * Test  4-1: Save Or should work fine if splitz is on, created updated_at should be updated.
        */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $websiteEntity1->audit_id = "testtesttest";
        $merchantWebsiteSaveRequest1 = new MerchantWebsiteSaveRequest();
        $merchantWebsiteSaveRequest1->setMerchantWebsite($merchantWebsiteProto1);
        $merchantWebsiteSaveRequest1->setFields(array_keys($websiteEntity1->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantWebsiteSaveRequest($merchantWebsiteSaveRequest1);
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity1);
        self::assertEquals(10, $websiteEntity1['created_at']);
        self::assertEquals(10, $websiteEntity1['updated_at']);
        self::assertEquals("newtesttesttest", $websiteEntity1['audit_id']);

        /*
         * Test  4-2: Save Or should work fine if splitz is on, created updated_at should be updated.
         */
        $websiteEntity2->audit_id = "testtesttest";
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $merchantWebsiteSaveRequest2 = new MerchantWebsiteSaveRequest();
        $merchantWebsiteSaveRequest2->setMerchantWebsite($merchantWebsiteProto2);
        $merchantWebsiteSaveRequest2->setFields(array_keys($websiteEntity2->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantWebsiteSaveRequest($merchantWebsiteSaveRequest2);
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity2);

        self::assertEquals(10, $websiteEntity2['created_at']);
        self::assertEquals(10, $websiteEntity2['updated_at']);
        self::assertEquals("newtesttesttest", $websiteEntity2['audit_id']);

        /*
         * Test  4-3: Save Or should work fine if splitz is on, created updated_at should be updated.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $websiteEntity3->audit_id = "testtesttest";
        $merchantWebsiteSaveRequest3 = new MerchantWebsiteSaveRequest();
        $merchantWebsiteSaveRequest3->setMerchantWebsite($merchantWebsiteProto3);
        $merchantWebsiteSaveRequest3->setFields(array_keys($websiteEntity3->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantWebsiteSaveRequest($merchantWebsiteSaveRequest3);
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($websiteEntity3);
        self::assertEquals(10, $websiteEntity3['created_at']);
        self::assertEquals(10, $websiteEntity3['updated_at']);
        self::assertEquals("newtesttesttest", $websiteEntity3['audit_id']);

        /*
        * Test 5: Save or fail should fail, if Splitz is on, asv throw exception.
        */


        $websiteEntity3 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson3);
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->
        expects($this->once())->
        method("save")->
        with($saveRequest)->
        willThrowException(new \RZP\Exception\BaseException("I am ASV Exception.", "ASV_SERVER_ERROR"));
        $merchantWebsite->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        try {
            $repo->saveOrFail($websiteEntity3);
            self::fail("Exception was expected.");
        } catch (\Exception $e) {
            self::assertEquals(\Illuminate\Database\QueryException::class, get_class($e));
            self::assertEquals("ASV_SERVER_ERROR", $e->getCode());
            self::assertEquals("I am ASV Exception. (SQL: )", $e->getMessage());
            self::assertEquals([], $e->getBindings());
            self::assertEquals("", $e->getSql());

            //created_at, updated_at not changed
            self::assertEquals(12346, $websiteEntity3['created_at']);
            self::assertEquals(1234, $websiteEntity3['updated_at']);
            // update should not happen since save failed.
            self::assertEquals("testtesttest", $websiteEntity3['audit_id']);
        }
    }

    private function getExceptionForFindOrFailAsv($repo, $id, $grpcError) {
        try {
            $this->setMerchantWebsiteMockClientWithIdAndResponse($id, null, $grpcError,"getById", 1);
            $repo->findOrFailAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindOrFailPublicAsv($repo, $id, $grpcError) {
        try {
            $this->setMerchantWebsiteMockClientWithIdAndResponse($id, null, $grpcError,"getById", 1);
            $repo->findOrFailPublicAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindAndFailDatabase($repo, $id) {
        try {
            $repo->findOrFailDatabase($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindAndFailPublicDatabase($repo, $id) {
        try {
            $repo->findOrFailPublicDatabase($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function setMerchantWebsiteMockClientWithIdAndResponse($id, $response, $error, $method, $count) {
        $merchantWebsite = new MerchantWebsite();
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly($count))->method($method)->with($id, $merchantWebsite->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);
    }

    private function getOutputForRawDbCalls($repo, $id, $columns = null, $connectiontype = null) {
        if($columns == null) {
            $findOrFailValue = $repo->findOrFailDatabase($id);
            $findOrFailPublicValue = $repo->findOrFailPublicDatabase($id);
        } else {
            $findOrFailValue = $repo->findOrFailDatabase($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailDatabase($id, $columns, $connectiontype);
        }

        if(is_array($id) && $columns==null ) {
            for($i = 0; $i < count($findOrFailValue); $i++) {
                $findOrFailValue[$i]['audit_id'] = "testtesttest";
                $findOrFailPublicValue[$i]['audit_id'] = "testtesttest";
            }
        } elseif($columns == null) {
            $findOrFailValue['audit_id'] = "testtesttest";
            $findOrFailPublicValue['audit_id'] = "testtesttest";
        }

        $this->assertEquals($findOrFailValue->toArray(), $findOrFailPublicValue->toArray());
        return $findOrFailValue->toArray();
    }

    private function getOutputForDbCalls($repo, $id, $columns = null, $connectiontype = null) {
        if($columns == null) {
            $findOrFailValue = $repo->findOrFail($id);
            $findOrFailPublicValue = $repo->findOrFailPublic($id);
        } else {
            $findOrFailValue = $repo->findOrFail($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailPublic($id, $columns, $connectiontype);
        }

        if(is_array($id) && $columns == null) {
            for($i = 0; $i < count($findOrFailValue); $i++) {
                $findOrFailValue[$i]['audit_id'] = "testtesttest";
                $findOrFailPublicValue[$i]['audit_id'] = "testtesttest";
            }
        } elseif($columns == null) {
            $findOrFailValue['audit_id'] = "testtesttest";
            $findOrFailPublicValue['audit_id'] = "testtesttest";
        }

        $this->assertEquals($findOrFailValue->toArray(), $findOrFailPublicValue->toArray());
        $this->assertEquals($this->getOutputForRawDbCalls($repo, $id, $columns,$connectiontype), $findOrFailPublicValue->toArray());

        return $findOrFailValue->toArray();
    }

    private function splitzShouldThrowException($count = 1) {
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willThrowException(new \Exception("sample"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return $splitzMock;
    }

    private function setSplitzWithOutput($output, $count = 1) {
        $splitz = $this->sampleSpltizOutput;
        $splitz["response"]["variant"]["variables"][0]["value"] = $output;
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willReturn($splitz);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return $splitz;
    }

    private function createMerchantWebsiteInDatabase($json) {
        $this->fixtures->create("merchant_website",
            $this->getMerchantWebsiteEntityForJson($json)->toArray(),
        );
    }

    private function getMerchantWebsiteProtoForJson($json) {
        $merchantWebsiteProto = new \Rzp\Accounts\Merchant\V1\MerchantWebsite();
        $merchantWebsiteProto->mergeFromJsonString($json, false);
        return $merchantWebsiteProto;
    }

    private function getMerchantWebsiteEntityForJson($json){
        $websiteArray = json_decode($json, true);
        $websiteEntity  =  new MerchantWebsiteEntity();
        $websiteEntity->setRawAttributes($websiteArray);
        return $websiteEntity;
    }


    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {
        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }

    private function getMockClient() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\WebsiteInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

    protected function getWriteMockClient() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\WriteInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

    public function testMerchantWebsiteAssociation()
    {
        $entitiesData = [
            [
                "relationName" => "merchantWebsite",
                "asvEntity" => new MerchantWebsite(),
                "asvResponseEntity" => new MerchantWebsiteResponseByMerchantId(),
                "setterFunctionName" => 'setWebsite',
                "responseSetterFunctionName" => 'setWebsites',
                "entityRepo" => new Repository(),
                "entityRepoName" =>  'merchant_website',
                "entityName" => "merchant_website",
                "entityData" => $this->websiteEntityJson1,
                "entityClass" => new MerchantWebsiteEntity(),
                "entityProtoClass"  => new \Rzp\Accounts\Merchant\V1\MerchantWebsite(),
                "AssociatedEntityRepo" => new \RZP\Models\Merchant\Detail\Repository(),
                "AssociatedEntityName" => "merchant_detail",
                "AssociatedEntityData" => $this->merchantDetailEntityJson1,
                "AssociatedEntityClass" =>  new MerchantDetailEntity(),
                "mockBuilderInterface" => "Razorpay\Asv\Interfaces\WebsiteInterface",
                "merchant_id" => "K4O9sCGihrL2bG",
                "shouldEntityNeedsToBeCreated" => true,
                "isDependentEntity" => true,
                "dependentEntity" => [
                    [
                        "dependentEntityName" => "merchant",
                        "dependentEntityData" => $this->merchantEntityJson1,
                        "dependentEntityClass" => new MerchantEntity(),

                    ]
                ]
            ]
        ];

        $this->runTestsForImplicitJoin($entitiesData);
    }

    public function getMockAsvRouterInRepository($method, $count, $response, $error)
    {
        $asvRouterMock = $this->getAsvRouteMock([$method]);
        if ($error === null) {
            $asvRouterMock->expects($this->exactly($count))->method($method)->willReturn($response);
        } else {
            $asvRouterMock->expects($this->exactly($count))->method($method)->willThrowException($error);
        }

        return $asvRouterMock;
    }

    private function getAsvRouteMock($methods = [])
    {
        return $this->getMockBuilder(AsvRouter::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function setEntityMockClientWithMerchantIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchantWebsite = new MerchantWebsite();
        $merchantWebsiteMockClient = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly($count))->method($method)->with($id, $merchantWebsite->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);
    }
}
