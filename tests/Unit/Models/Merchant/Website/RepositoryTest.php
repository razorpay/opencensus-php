<?php

namespace Unit\Models\Merchant\Website;

use Config;
use Database\Connection;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantDocumentSaveRequest;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteSaveRequest;
use RZP\Models\Merchant\Website\Entity;
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
use function PHPUnit\Framework\assertNotEquals;

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


    public function testMerchantWebsiteSaveOrFailReadMigration()
    {
        $attributes = [
            'merchant_id'              => '10000000000000',
            'status'                   => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                ]
            ]
        ];

        $this->validateSaveOrFailReadMigration("merchant_website", $attributes, new Repository());
    }

    public function testMerchantWebsiteSaveOrFailMigration()
    {
        $entity = $this->fixtures->create("merchant_website");

        $attributes = [
            'merchant_id'              => '10000000000000',
            'status'                   => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                ]
            ]
        ];

        Config::set('applications.asv_v2.stop_asv_entity_writes_merchant_website', false);

        $this->validateSaveOrFailMigration("merchant_website", $attributes, new Repository(), $entity, true);

        Config::set('applications.asv_v2.stop_asv_entity_writes_merchant_website', true);
        // enable flag
        $attributes = [

            'status'                   => 'notsubmitted',
            "shipping_period"          => "4-6 days",
            "refund_request_period"    => "4-6 days",
            "refund_process_period"    => "4-6 days",
            "additional_data"          => [
                "support_contact_number" => "9980004018",
                "support_email"          => "kakarla.vasanthi12@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 4,
                    "status"         => "notsubmitted",
                ]
            ]
        ];
        $this->validateSaveOrFailMigration("merchant_website", $attributes, new Repository(), $entity, false);
    }


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

    public function assertEqualsAssociativeByKey($array1, $array2, $key = "id")
    {
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

    public function testMerchantWebsiteFindOrFailRequestRoutedToAsv() {

        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson1);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson2);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson3);

        $websiteEntity1 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson1);

        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);

        $merchantWebsiteResponse = (new MerchantWebsiteResponse())->setWebsite($merchantWebsiteProto1);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on.
        $repo = new Repository();
        $repo->repo->transactionOnLiveAndTestAndAsv(function () use ($repo) {
            $merchantWebsite = $this->fixtures->on(Connection::ASV_WRITER)->create("merchant_website");
            $repo->asvRouter = $this->getMockAsvRouterInRepository('shouldRouteFindToAccountService', 4, true, null);
            $response        = $this->getOutputForDbCalls($repo, $merchantWebsite->getId());
        });

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, asv gives exception.
        $repo = new Repository();
        $merchantWebsite = $this->fixtures->create("merchant_website");
        $repo->asvRouter = $this->getMockAsvRouterInRepository('shouldRouteFindToAccountService', 4, false, null);
        $response = $this->getOutputForDbCalls($repo, $merchantWebsite->getId());
    }

    public function testStakholderRepositoryFindOrFailErrors()
    {
        $repo = new Repository();
         //Match not found Exception from DB and ASV: FindOrFail
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

    public function testMerchantWebsiteFindRequestRoutedToAsv() {

        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson1);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson2);
        $this->createMerchantWebsiteInDatabase($this->websiteEntityJson3);

        $websiteEntity1 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson1);

        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);

        $merchantWebsiteResponse = (new MerchantWebsiteResponse())->setWebsite($merchantWebsiteProto1);

        // Find should work fine if splitz is on.
        $repo = new Repository();
        $this->setSplitzWithOutput("true", 1); // Splitz should never be called
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", $merchantWebsiteResponse, null,"getById", 1);
        $response = $this->getOutputForDbCallsForFind($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($websiteEntity1->toArray(), $response);

        // Find should work fine if splitz is on, asv gives exception.
        $repo = new Repository();
        $this->setSplitzWithOutput("true", 1);  // Splitz should never be called
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", null, new GrpcError(\Grpc\STATUS_DEADLINE_EXCEEDED, "test"),"getById", 1);
        $response = $this->getOutputForDbCallsForFind($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($websiteEntity1->toArray(), $response);
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

    private function getOutputForRawDbCallsForFind($repo, $id, $columns = null, $connectiontype = null) {
        if($columns == null) {
            $findValue = $repo->findDatabase($id);
        } else {
            $findValue = $repo->findDatabase($id, $columns, $connectiontype);
        }

        if(is_array($id) && $columns==null ) {
            for($i = 0; $i < count($findValue); $i++) {
                $findValue[$i]['audit_id'] = "testtesttest";
            }
        } elseif($columns == null) {
            $findValue['audit_id'] = "testtesttest";
        }

        return $findValue->toArray();
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

    private function getOutputForDbCallsForFind($repo, $id, $columns = null, $connectiontype = null) {
        if($columns == null) {
            $findValue = $repo->find($id);
        } else {
            $findValue = $repo->find($id, $columns, $connectiontype);
        }

        if(is_array($id) && $columns == null) {
            for($i = 0; $i < count($findValue); $i++) {
                $findValue[$i]['audit_id'] = "testtesttest";
            }
        } elseif($columns == null) {
            $findValue['audit_id'] = "testtesttest";
        }

        $this->assertEquals($this->getOutputForRawDbCallsForFind($repo, $id, $columns,$connectiontype), $findValue->toArray());

        return $findValue->toArray();
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

    public function testMerchantWebsiteUpdatedAt()
    {
        $repo = new Repository();
        $websiteEntity = (new Entity());
        $id = $websiteEntity->generateUniqueId();
        $websiteEntity->setId($id);
        $websiteEntity->fill([
            Entity::MERCHANT_ID => $id,
            Entity::AUDIT_ID => $id,
            Entity::UPDATED_AT => 1234,
        ]);
        $repo->repo->transactionOnLiveAndTest(function() use ($websiteEntity, $repo){
            $repo->saveOrFail($websiteEntity);
        });
        $websiteEntity1 = $repo->findOrFail($id);
        assertNotEquals(1234, $websiteEntity1->getUpdatedAt());

        $websiteEntity1->setUpdatedAt(1234);
        $repo->repo->transactionOnLiveAndTest(function() use ($websiteEntity1, $repo){
             $repo->saveOrFail($websiteEntity1);
        });

        $websiteEntity2 = $repo->findOrFail($id);

        assertNotEquals(1234, $websiteEntity2->getUpdatedAt());
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
