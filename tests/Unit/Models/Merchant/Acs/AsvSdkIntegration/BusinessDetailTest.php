<?php

namespace Unit\Models\Merchant\Acs\AsvSdkIntegration;

use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantBusinessDetailResponse;
use Rzp\Accounts\Merchant\V1\MerchantBusinessDetailResponseByMerchantId;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\BusinessDetail;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;

class BusinessDetailTest extends TestCase
{

    private $businessDetailEntityJson1 = '{
                          "id": "K9UzmvitzJwyS4",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                        "business_parent_category": "1",
                        "blacklisted_products_category":  "3-5 days",
                        "website_details": "{\"terms\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "app_urls": "{\"website\": {\"https://example.com/\": {\"terms\": {\"url\": \"https://www.hello.com/\"}, \"pricing\": {\"url\": \"\"}, \"privacy\": {\"url\": \"\"}, \"about_us\": {\"url\": \"https://hello.com/about_us\"}, \"comments\": \"sss\", \"contact_us\": {\"url\": \"https://www.hello.com/\"}}}}",
                        "plugin_details":"{\"terms2\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "lead_score_components":"{\"terms3\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "metadata":"{\"term43\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "onboarding_source": "submitted",
                        "pg_use_case": "1124",
                        "miq_sharing_date": 1,
                        "testing_credentials_date": 23,
                        "created_at":1234,
                        "updated_at":1234,
                        "gst_details": null
           }';

    private $businessDetailEntityJson2 = '{
                              "id": "K9UzmvitzJwyS5",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                        "business_parent_category": "134",
                        "blacklisted_products_category":  "3-5 dfays",
                        "website_details": "{\"terms\": {\"statuas\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "app_urls": null,
                        "plugin_details":"{\"terms2\": {\"sftatus\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "lead_score_components":"{\"tefrms3\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "metadata":"{\"term43\": {\"sftatus\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "onboarding_source": "fsubmitted",
                        "pg_use_case": "11324",
                        "miq_sharing_date": 14,
                        "testing_credentials_date": 323,
                        "created_at":12345,
                        "updated_at":1234,
                        "gst_details":"{\"terms2\": {\"sftatus\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}"
           }';

    private $businessDetailEntityJson3 = '{
                         "id": "K9UzmvitzJwyS3",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                        "business_parent_category": null,
                        "blacklisted_products_category":  "3-5 dayfs",
                        "website_details": "{\"tefrms\": {\"staatus\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "app_urls": "{\"websfite\": {\"httaps://example.com/\": {\"terms\": {\"url\": \"https://www.hello.com/\"}, \"pricing\": {\"url\": \"\"}, \"privacy\": {\"url\": \"\"}, \"about_us\": {\"url\": \"https://hello.com/about_us\"}, \"comments\": \"sss\", \"contact_us\": {\"url\": \"https://www.hello.com/\"}}}}",
                        "plugin_details":"{\"terms2\": {\"sftatus\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "lead_score_components":"{\"trerms3\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "metadata":"{\"term43\": {\"s34tatus\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/tnc\"}}, \"updated_at\": 1661350497, \"published_url\": null, \"section_status\": 2}, \"refund\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350384, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/refund\", \"section_status\": 3}, \"privacy\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/pp\"}}, \"updated_at\": 1661350352, \"published_url\": null, \"section_status\": 1}, \"shipping\": {\"status\": \"submitted\", \"website\": null, \"updated_at\": 1661350559, \"published_url\": \"https://sme-dashboard.dev.razorpay.in/compliance/K9UzmvitzJwyS4/shipping\", \"section_status\": 3}, \"contact_us\": {\"status\": \"submitted\", \"website\": {\"https://example.com/\": {\"url\": \"https://example.com/contact_us\"}}, \"updated_at\": 1661350424, \"published_url\": null, \"section_status\": 1}}",
                        "onboarding_source": "subddmitted",
                        "pg_use_case": "124",
                        "miq_sharing_date": 4,
                        "testing_credentials_date": 25,
                        "created_at":12346,
                        "updated_at":1234,
                        "gst_details":null
     }';


    public function testGetById()
    {

        $merchantBusinessDetail = new BusinessDetail();
        $merchantBusinessDetailMockClient  = $this->getMockClient();
        $merchantBusinessDetail->getAsvSdkClient()->setBusinessDetail($merchantBusinessDetailMockClient);

        /* success */

        // prepare expected data //
        $businessDetailArray = json_decode($this->businessDetailEntityJson1, true);
        $expectedBusinessDetailsEntity  =  new BusinessDetailEntity();
        $expectedBusinessDetailsEntity->setRawAttributes($businessDetailArray);

        // prepare mocks //
        $businessDetailResponse = new MerchantBusinessDetailResponse();
        $businessDetailProto = new \Rzp\Accounts\Merchant\V1\BusinessDetail();
        $businessDetailProto->mergeFromJsonString($this->businessDetailEntityJson1, false);
        $businessDetailResponse->setBusinessDetail($businessDetailProto);
        $merchantBusinessDetailMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH", $merchantBusinessDetail->getDefaultRequestMetaData())->willReturn([$businessDetailResponse, null]);

        // Call and Assert //
        $gotBusinessDetailEntity = $merchantBusinessDetail->getById("K4O9sCGihrL2bH");
        self::assertEquals($expectedBusinessDetailsEntity->toArray(), $gotBusinessDetailEntity->toArray());

        /* Not found Exception */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_NOT_FOUND, "new");
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH",  $merchantBusinessDetail->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantBusinessDetail->getAsvSdkClient()->setBusinessDetail($businessDetailMockClient);

            $merchantBusinessDetail->getById("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID, $e->getCode());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH",  $merchantBusinessDetail->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantBusinessDetail->getAsvSdkClient()->setBusinessDetail($businessDetailMockClient);

            $merchantBusinessDetail->getById("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetByMerchantId()
    {

        $businessDetail = new BusinessDetail();
        $businessDetailMockClient  = $this->getMockClient();
        $businessDetail->getAsvSdkClient()->setBusinessDetail($businessDetailMockClient);

        /* success */

        // prepare expected data //
        $businessDetailEntity1 = $this->getMerchantBusinessDetailEntityForJson($this->businessDetailEntityJson1);
        $businessDetailEntity2 = $this->getMerchantBusinessDetailEntityForJson($this->businessDetailEntityJson2);
        $expectedCollection = (new BusinessDetailEntity())->newCollection([$businessDetailEntity1, $businessDetailEntity2]);

        // prepare mocks //
        $businessDetailProto1 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson1);
        $businessDetailProto2 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson2);
        $businessDetailResponse = new MerchantBusinessDetailResponseByMerchantId();
        $businessDetailResponse->setBusinessDetails([$businessDetailProto1, $businessDetailProto2]);

        $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([$businessDetailResponse, null]);

        // Call and Assert //
        $gotbusinessDetailCollection = $businessDetail->getByMerchantId("K4O9sCGihrL2bH");
        self::assertEquals($expectedCollection->toArray(), $gotbusinessDetailCollection->toArray());

        /* Not found Exception */
        try {
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([ new MerchantBusinessDetailResponseByMerchantId(), null]);
            $businessDetail->getAsvSdkClient()->setBusinessDetail($businessDetailMockClient);

            $response = $businessDetail->getByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals([],$response->toArray());
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $businessDetail->getAsvSdkClient()->setBusinessDetail($businessDetailMockClient);

            $businessDetail->getByMerchantId("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetLatestByMerchantId()
    {

        $businessDetail = new BusinessDetail();
        $businessDetailMockClient  = $this->getMockClient();
        $businessDetail->getAsvSdkClient()->setBusinessDetail($businessDetailMockClient);

        /* success */

        // prepare expected data //
        $businessDetailEntity3 = $this->getMerchantBusinessDetailEntityForJson($this->businessDetailEntityJson3);

        // prepare mocks //
        $businessDetailProto1 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson1);
        $businessDetailProto2 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson2);
        $businessDetailProto3 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson3);

        $businessDetailResponse = new MerchantBusinessDetailResponseByMerchantId();
        $businessDetailResponse->setBusinessDetails([$businessDetailProto3, $businessDetailProto2, $businessDetailProto1]);

        $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([$businessDetailResponse, null]);

        // Call and Assert //
        $gotbusinessDetail = $businessDetail->getLatestByMerchantId("K4O9sCGihrL2bH");
        self::assertEquals($businessDetailEntity3->toArray(), $gotbusinessDetail->toArray());

        /* Not found  */
        try {
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([ new MerchantbusinessDetailResponseByMerchantId(), null]);
            $businessDetail->getAsvSdkClient()->setbusinessDetail($businessDetailMockClient);

            $response = $businessDetail->getLatestByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals(null, $response);
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Validation Failure */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "new");
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([ null, $grpcError]);
            $businessDetail->getAsvSdkClient()->setbusinessDetail($businessDetailMockClient);

            $response = $businessDetail->getLatestByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals(null,$response );
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $businessDetailMockClient = $this->getMockClient();
            $businessDetailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $businessDetail->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $businessDetail->getAsvSdkClient()->setbusinessDetail($businessDetailMockClient);

            $businessDetail->getLatestByMerchantId("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    private function getBusinessDetailProtoForJson($json) {
        $businessDetailProto = new \Rzp\Accounts\Merchant\V1\BusinessDetail();
        $businessDetailProto->mergeFromJsonString($json, false);
        return $businessDetailProto;
    }

    private function getMerchantBusinessDetailEntityForJson($json){
        $businessDetailArray = json_decode($json, true);
        $businessDetailEntity  =  new BusinessDetailEntity();
        $businessDetailEntity->setRawAttributes($businessDetailArray);
        return $businessDetailEntity;
    }

    private function getMockClient() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\BusinessDetailInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }
}
