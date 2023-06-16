<?php

namespace Unit\Models\Merchant\Acs\AsvSdkIntegration;

use Mockery;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteResponse;
use Rzp\Accounts\Merchant\V1\MerchantWebsiteResponseByMerchantId;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;

class MerchantWebsiteTest extends TestCase
{

    private $websiteEntityJson1 = '{
                        "id": "K9UzmvitzJwyS4",
                        "audit_id": "KFz13cbuixnVO8",
                        "merchant_id": "K4O9sCGihrL2bH",
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
                        "audit_id": "KFz13cbuixnVOC",
                        "merchant_id": "K4O9sCGihrL2bH",
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
                        "created_at":1234,
                        "updated_at":1234
           }';

    private $websiteEntityJson3 = '{
                        "id": "K9UzmvitzJwyS3",
                        "audit_id": "testtestetst",
                        "merchant_id": "K4O9sCGihrL2bH",
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
                        "created_at":1234,
                        "updated_at":1234
           }';


    public function testGetById()
    {

        $merchantWebsite = new MerchantWebsite();
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        /* success */

        // prepare expected data //
        $websiteArray = json_decode($this->websiteEntityJson1, true);
        $expectedWebsiteEntity  =  new MerchantWebsiteEntity();
        $expectedWebsiteEntity->setRawAttributes($websiteArray);

        // prepare mocks //
        $merchantWebsiteResponse = new MerchantWebsiteResponse();
        $merchantWebsiteProto = new \Rzp\Accounts\Merchant\V1\MerchantWebsite();
        $merchantWebsiteProto->mergeFromJsonString($this->websiteEntityJson1, false);
        $merchantWebsiteResponse->setWebsite($merchantWebsiteProto);
        $merchantWebsiteMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([$merchantWebsiteResponse, null]);

        // Call and Assert //
        $gotWebsiteEntity = $merchantWebsite->getById("K4O9sCGihrL2bH");
        self::assertEquals($expectedWebsiteEntity->toArray(), $gotWebsiteEntity->toArray());

        /* Not found Exception */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_NOT_FOUND, "new");
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH",  $merchantWebsite->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $merchantWebsite->getById("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID, $e->getCode());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH",  $merchantWebsite->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $merchantWebsite->getById("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetByMerchantId()
    {

        $merchantWebsite = new MerchantWebsite();
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        /* success */

        // prepare expected data //
        $websiteEntity1 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson1);
        $websiteEntity2 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson2);
        $expectedCollection = (new MerchantWebsiteEntity)->newCollection([$websiteEntity1, $websiteEntity2]);

        // prepare mocks //
        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);
        $merchantWebsiteProto2 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson2);
        $merchantWebsiteResponse = new MerchantWebsiteResponseByMerchantId();
        $merchantWebsiteResponse->setWebsites([$merchantWebsiteProto1, $merchantWebsiteProto2]);

        $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([$merchantWebsiteResponse, null]);

        // Call and Assert //
        $gotWebsiteCollection = $merchantWebsite->getByMerchantId("K4O9sCGihrL2bH");
        self::assertEquals($expectedCollection->toArray(), $gotWebsiteCollection->toArray());

        /* Not found Exception */
        try {
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([ new MerchantWebsiteResponseByMerchantId(), null]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $response = $merchantWebsite->getByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals([],$response->toArray());
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $merchantWebsite->getByMerchantId("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetLatestByMerchantId()
    {

        $merchantWebsite = new MerchantWebsite();
        $merchantWebsiteMockClient  = $this->getMockClient();
        $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

        /* success */

        // prepare expected data //
        $websiteEntity3 = $this->getMerchantWebsiteEntityForJson($this->websiteEntityJson3);

        // prepare mocks //
        $merchantWebsiteProto1 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson1);
        $merchantWebsiteProto2 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson2);
        $merchantWebsiteProto3 = $this->getMerchantWebsiteProtoForJson($this->websiteEntityJson3);

        $merchantWebsiteResponse = new MerchantWebsiteResponseByMerchantId();
        $merchantWebsiteResponse->setWebsites([$merchantWebsiteProto3, $merchantWebsiteProto2, $merchantWebsiteProto1]);

        $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([$merchantWebsiteResponse, null]);

        // Call and Assert //
        $gotWebsite = $merchantWebsite->getLatestByMerchantId("K4O9sCGihrL2bH");
        self::assertEquals($websiteEntity3->toArray(), $gotWebsite->toArray());

        /* Not found  */
        try {
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([ new MerchantWebsiteResponseByMerchantId(), null]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $response = $merchantWebsite->getLatestByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals(null, $response);
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Validation Failure */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "new");
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([ null, $grpcError]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $response = $merchantWebsite->getLatestByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals(null,$response );
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $merchantWebsiteMockClient = $this->getMockClient();
            $merchantWebsiteMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $merchantWebsite->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantWebsite->getAsvSdkClient()->setWebsite($merchantWebsiteMockClient);

            $merchantWebsite->getLatestByMerchantId("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
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

    private function getMockClient() {
       return $this->getMockBuilder("Razorpay\Asv\Interfaces\WebsiteInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }
}
