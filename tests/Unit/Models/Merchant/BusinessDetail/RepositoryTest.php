<?php

namespace Unit\Models\Merchant\BusinessDetail;

use Razorpay\Asv\Error\GrpcError;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\BusinessDetail;
use RZP\Models\Merchant\BusinessDetail\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;

use Rzp\Accounts\Merchant\V1\MerchantBusinessDetailResponse;
use Rzp\Accounts\Merchant\V1\MerchantBusinessDetailResponseByMerchantId;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;

class RepositoryTest extends TestCase
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
                        "gst_details":null
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


    public function testGetBusinessDetailsForMerchantId()
    {
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson1);
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson2);
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson3);

        $merchantbusinessDetail = new BusinessDetail();

        // prepare expected data //
        $businessDetailEntity3 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson3);

        // prepare mocks //
        $merchantbusinessDetailProto1 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson1);
        $merchantbusinessDetailProto2 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson2);
        $merchantbusinessDetailProto3 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson3);

        $merchantbusinessDetailResponse = new MerchantBusinessDetailResponseByMerchantId();
        $merchantbusinessDetailResponse->setBusinessDetails([$merchantbusinessDetailProto3, $merchantbusinessDetailProto2, $merchantbusinessDetailProto1]);


        // test1: Splitz is on, request should go to account service.
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantbusinessDetailMockClient = $this->getMockClient();
        $merchantbusinessDetailMockClient->expects(
            $this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bG",
            $merchantbusinessDetail->getDefaultRequestMetaData())->willReturn([$merchantbusinessDetailResponse, null]
        );
        $merchantbusinessDetail->getAsvSdkClient()->setBusinessDetail($merchantbusinessDetailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $businessDetail = $repo->getBusinessDetailsForMerchantId("K4O9sCGihrL2bG");
        $businessDetail['audit_id'] = "testtesttest";
        self::assertEquals($businessDetailEntity3->toArray(), $businessDetail->toArray());

        // test2: Splitz is off, request not should go to account service.
        $splitzOff = $this->sampleSpltizOutput;
        $splitzOff["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOff);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantbusinessDetailMockClient = $this->getMockClient();
        $merchantbusinessDetailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantbusinessDetailResponse, null]);
        $merchantbusinessDetail->getAsvSdkClient()->setbusinessDetail($merchantbusinessDetailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $businessDetail = $repo->getBusinessDetailsForMerchantId("K4O9sCGihrL2bG");
        $businessDetail['audit_id'] = "testtesttest";
        self::assertEquals($businessDetailEntity3->toArray(), $businessDetail->toArray());

        // test3: Splitz call fails, request not should go to account service.
        $splitzOff = $this->sampleSpltizOutput;
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willThrowException(new \Exception("sample"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantbusinessDetailMockClient = $this->getMockClient();
        $merchantbusinessDetailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantbusinessDetailResponse, null]);
        $merchantbusinessDetail->getAsvSdkClient()->setbusinessDetail($merchantbusinessDetailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $businessDetail = $repo->getBusinessDetailsForMerchantId("K4O9sCGihrL2bG");
        $businessDetail['audit_id'] = "testtesttest";
        self::assertEquals($businessDetailEntity3->toArray(), $businessDetail->toArray());

        // test4: Splitz is on, save flow is on request should not got asv, should go to db.
        $repo = $this->getRepoWithSplitzAndSaveFlow("true", 0, true);
        $businessDetail = $repo->getBusinessDetailsForMerchantId("K4O9sCGihrL2bG");
        $businessDetail['audit_id'] = "testtesttest";
        self::assertEquals($businessDetailEntity3->toArray(), $businessDetail->toArray());
    }

    public function testbusinessDetailRepositoryFindRequestNotRoutedToAsv()
    {

        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson1);
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson2);
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson3);

        $businessDetailEntity1 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson1);
        $businessDetailEntity2 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson2);
        $businessDetailEntity3 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson3);

        // Find, FindOrFail & FindOrFail public should work fine if splitz is off.

        $repo = $this->getRepoWithSplitzAndSaveFlow("false", 2);
        $this->assertEquals($businessDetailEntity1->toArray(), $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4"));

        // Find, FindOrFail & FindOrFail public should work fine if save flow is true
        $repo = $this->getRepoWithSplitzAndSaveFlow("true", 0, true);
        $this->assertEquals($businessDetailEntity1->toArray(), $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4"));

        // Find, FindOrFail & FindOrFail public should work fine if splitz throws an exception.
        $repo = $this->getRepoWithSplitzAndSaveFlow("exception", 2);
        $this->assertEquals($businessDetailEntity3->toArray(), $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS3"));

        // Find, FindOrFail & FindOrFail public should work fine if we give columns value, splitz off.
        $repo = $this->getRepoWithSplitzAndSaveFlow("false", 0);
        $this->assertEquals(["id" => $businessDetailEntity3['id']], $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS3", ["id"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids.
        $repo = $this->getRepoWithSplitzAndSaveFlow("false", 0);
        $this->assertEqualsAssociativeByKey([$businessDetailEntity3->toArray(), $businessDetailEntity2->toArray()], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS5", "K9UzmvitzJwyS3"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids and columns
        $repo = $this->getRepoWithSplitzAndSaveFlow("false", 0);
        $this->assertEqualsAssociativeByKey([["id" => "K9UzmvitzJwyS5"], ["id" => "K9UzmvitzJwyS3"]], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS3", "K9UzmvitzJwyS5"], ["id"]));

        // Find, FindOrFail & FindOrFail public should work fine if we give multiple ids and columns
        $repo = $this->getRepoWithSplitzAndSaveFlow("false", 0);
        $this->assertEqualsAssociativeByKey([["id" => "K9UzmvitzJwyS5"], ["id" => "K9UzmvitzJwyS3"]], $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS3", "K9UzmvitzJwyS5"], ["id"]));

    }

    public function getRepoWithSplitzAndSaveFlow($splitzOutput, $splitzCount, $isExclusionFlowOrFailure = false)
    {
        if ($splitzOutput == "exception") {
            $this->splitzShouldThrowException($splitzCount);
        } else {
            $this->setSplitzWithOutput($splitzOutput, $splitzCount);
        }
        $repo = new Repository();
        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->any())->method('isExclusionFlowOrFailure')->willReturn($isExclusionFlowOrFailure);
        $repo->asvRouter = $asvRouterMock;
        return $repo;
    }

    public function testMerchantbusinessDetailFindOrFailRequestRoutedToAsv()
    {
        $repo = new Repository();

        $merchantbusinessDetail = new BusinessDetail();

        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson1);
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson2);
        $this->createMerchantbusinessDetailInDatabase($this->businessDetailEntityJson3);

        $businessDetailEntity1 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson1);
        $businessDetailEntity2 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson2);
        $businessDetailEntity3 = $this->getBusinessDetailEntityForJson($this->businessDetailEntityJson3);

        $merchantbusinessDetailProto1 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson1);
        $merchantbusinessDetailProto2 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson2);
        $merchantbusinessDetailProto3 = $this->getBusinessDetailProtoForJson($this->businessDetailEntityJson3);

        $merchantbusinessDetailResponse = (new MerchantbusinessDetailResponse())->setbusinessDetail($merchantbusinessDetailProto1);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on.
        $repo = $this->getRepoWithSplitzAndSaveFlow("true", 2);

        $this->setBusinessDetailMockClientWithIdAndResponse("K9UzmvitzJwyS4", $merchantbusinessDetailResponse, null, "getById", 2);
        $response = $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($businessDetailEntity1->toArray(), $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, "K9UzmvitzJwyS4"), $response);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, asv gives exception.
        $repo = $this->getRepoWithSplitzAndSaveFlow("true", 2);
        $this->setBusinessDetailMockClientWithIdAndResponse("K9UzmvitzJwyS4", null, new GrpcError(\Grpc\STATUS_DEADLINE_EXCEEDED, "test"), "getById", 2);
        $response = $this->getOutputForDbCalls($repo, "K9UzmvitzJwyS4");
        $this->assertEquals($businessDetailEntity1->toArray(), $response);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, "K9UzmvitzJwyS4"), $response);

        // FindOrFail & FindOrFailpublic should work fine if splitz is on, array of ids.
        $repo = $this->getRepoWithSplitzAndSaveFlow("true", 0);
        $this->setBusinessDetailMockClientWithIdAndResponse("K9UzmvitzJwyS4", $merchantbusinessDetailResponse, null, "getById", 0);
        $response = $this->getOutputForDbCalls($repo, ["K9UzmvitzJwyS4"]);
        $this->assertEquals([$businessDetailEntity1->toArray()], $response);
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

    private function getExceptionForFindOrFailAsv($repo, $id, $grpcError)
    {
        try {
            $this->setBusinessDetailMockClientWithIdAndResponse($id, null, $grpcError, "getById", 1);
            $repo->findOrFailAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindOrFailPublicAsv($repo, $id, $grpcError)
    {
        try {
            $this->setBusinessDetailMockClientWithIdAndResponse($id, null, $grpcError, "getById", 1);
            $repo->findOrFailPublicAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindAndFailDatabase($repo, $id)
    {
        try {
            $repo->findOrFailDatabase($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindAndFailPublicDatabase($repo, $id)
    {
        try {
            $repo->findOrFailPublicDatabase($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function setBusinessDetailMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $sdkWrapper = new BusinessDetail();
        $mockClient = $this->getMockClient();
        $mockClient->expects($this->exactly($count))->method($method)->with($id, $sdkWrapper->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $sdkWrapper->getAsvSdkClient()->setBusinessDetail($mockClient);
    }

    private function getOutputForRawDbCalls($repo, $id, $columns = null, $connectiontype = null)
    {
        if ($columns == null) {
            $findOrFailValue = $repo->findOrFailDatabase($id);
            $findOrFailPublicValue = $repo->findOrFailPublicDatabase($id);
        } else {
            $findOrFailValue = $repo->findOrFailDatabase($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailDatabase($id, $columns, $connectiontype);
        }

        if (is_array($id) && $columns == null) {
            for ($i = 0; $i < count($findOrFailValue); $i++) {
                $findOrFailValue[$i]['audit_id'] = "testtesttest";
                $findOrFailPublicValue[$i]['audit_id'] = "testtesttest";
            }
        } elseif ($columns == null) {
            $findOrFailValue['audit_id'] = "testtesttest";
            $findOrFailPublicValue['audit_id'] = "testtesttest";
        }

        $this->assertEquals($findOrFailValue->toArray(), $findOrFailPublicValue->toArray());
        return $findOrFailValue->toArray();
    }

    private function getOutputForDbCalls($repo, $id, $columns = null, $connectiontype = null)
    {
        if ($columns == null) {
            $findOrFailValue = $repo->findOrFail($id);
            $findOrFailPublicValue = $repo->findOrFailPublic($id);
        } else {
            $findOrFailValue = $repo->findOrFail($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailPublic($id, $columns, $connectiontype);
        }

        if (is_array($id) && $columns == null) {
            for ($i = 0; $i < count($findOrFailValue); $i++) {
                $findOrFailValue[$i]['audit_id'] = "testtesttest";
                $findOrFailPublicValue[$i]['audit_id'] = "testtesttest";
            }
        } elseif ($columns == null) {
            $findOrFailValue['audit_id'] = "testtesttest";
            $findOrFailPublicValue['audit_id'] = "testtesttest";
        }

        $this->assertEquals($findOrFailValue->toArray(), $findOrFailPublicValue->toArray());
        $this->assertEquals($this->getOutputForRawDbCalls($repo, $id, $columns, $connectiontype), $findOrFailPublicValue->toArray());

        return $findOrFailValue->toArray();
    }

    private function splitzShouldThrowException($count = 1)
    {
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willThrowException(new \Exception("sample"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return $splitzMock;
    }

    private function setSplitzWithOutput($output, $count = 1)
    {
        $splitz = $this->sampleSpltizOutput;
        $splitz["response"]["variant"]["variables"][0]["value"] = $output;
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willReturn($splitz);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return $splitz;
    }

    private function createMerchantbusinessDetailInDatabase($json)
    {
        $this->fixtures->create("merchant_business_detail",
            $this->getBusinessDetailEntityForJson($json)->toArray(),
        );
    }

    private function getBusinessDetailProtoForJson($json)
    {
        $businessDetailProto = new \Rzp\Accounts\Merchant\V1\BusinessDetail();
        $businessDetailProto->mergeFromJsonString($json, false);
        return $businessDetailProto;
    }

    private function getBusinessDetailEntityForJson($json)
    {
        $array = json_decode($json, true);
        $entity = new BusinessDetailEntity();
        $entity->setRawAttributes($array);
        return $entity;
    }


    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {
        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }

    private function getMockClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\BusinessDetailInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

    private function getAsvRouteMock($methods = [])
    {
        return $this->getMockBuilder(AsvRouter::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }
}
