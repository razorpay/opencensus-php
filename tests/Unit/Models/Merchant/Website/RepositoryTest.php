<?php

namespace Unit\Models\Merchant\Website;

use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteResponse;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteResponseByMerchantId;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use RZP\Models\Merchant\Website\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;

class RepositoryTest extends TestCase
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


        // test1: Splitz is on, request should go to account service.
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bG", $merchantWebsite->getDefaultRequestMetaData())->willReturn([$merchantWebsiteResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        $repo = new Repository();
        $website = $repo->getWebsiteDetailsForMerchantId("K4O9sCGihrL2bG");
        self::assertEquals($websiteEntity3->toArray(), $website->toArray());

        // test2: Splitz is off, request not should go to account service.
        $splitzOff = $this->sampleSpltizOutput;
        $splitzOff["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOff);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantWebsiteResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        $repo = new Repository();
        $website = $repo->getWebsiteDetailsForMerchantId("K4O9sCGihrL2bG");
        $website['audit_id'] = "testtesttest";
        self::assertEquals($websiteEntity3->toArray(), $website->toArray());

        // test3: Splitz call fails, request not should go to account service.
        $splitzOff = $this->sampleSpltizOutput;
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willThrowException(new \Exception("sample"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsiteMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantWebsiteResponse, null]);
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        $repo = new Repository();
        $website = $repo->getWebsiteDetailsForMerchantId("K4O9sCGihrL2bG");
        $website['audit_id'] = "testtesttest";
        self::assertEquals($websiteEntity3->toArray(), $website->toArray());
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

        // Find, FindOrFail & FindOrFail public should work fine if splitz is off.
        $this->setSplitzWithOutput("false", 2);
        $this->assertEquals($websiteEntity1->toArray(), $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4"));

        // Find, FindOrFail & FindOrFail public should work fine if splitz throws an exception.
        $this->splitzShouldThrowException(2);
        $this->assertEquals($websiteEntity3->toArray(), $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS3"));

        // Find, FindOrFail & FindOrFail public should work fine if we give columns value, splitz off.
        $this->setSplitzWithOutput("false", 0);
        $this->assertEquals(["id" => $websiteEntity3->getId()], $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS3", ["id"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids.
        $this->setSplitzWithOutput("false", 0);
        $this->assertEqualsAssociativeByKey([$websiteEntity3->toArray(), $websiteEntity2->toArray()], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS5", "K9UzmvitzJwyS3"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids and columns
        $this->setSplitzWithOutput("false",0);
        $this->assertEqualsAssociativeByKey([["id" => "K9UzmvitzJwyS5"], ["id" => "K9UzmvitzJwyS3"]], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS3", "K9UzmvitzJwyS5"], ["id"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids and columns
        $this->setSplitzWithOutput("false",0);
        $this->assertEqualsAssociativeByKey([["id" => "K9UzmvitzJwyS5"], ["id" => "K9UzmvitzJwyS3"]], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS3", "K9UzmvitzJwyS5"], ["id"]));
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
        $this->setSplitzWithOutput("true", 2);
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", $merchantWebsiteResponse, null,"getById", 2);
        $response = $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($websiteEntity1->toArray(), $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, "K9UzmvitzJwyS4"), $response);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, asv gives exception.
        $this->setSplitzWithOutput("true", 2);
        $this->setMerchantWebsiteMockClientWithIdAndResponse("K9UzmvitzJwyS4", null, new GrpcError(\Grpc\STATUS_DEADLINE_EXCEEDED, "test"),"getById", 2);
        $response = $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($websiteEntity1->toArray(), $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, "K9UzmvitzJwyS4"), $response);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, array of ids.
        $this->setSplitzWithOutput("true", 0);
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
}
