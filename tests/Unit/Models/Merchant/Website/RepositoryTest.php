<?php

namespace Models\Merchant\Website;

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
                        "audit_id": "KFz13cbuixnVO8",
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
                        "audit_id": "KFz13cbuixnVOC",
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
                        "audit_id": "testtestetst",
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
        $website['audit_id'] = "testtestetst";
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
        $website['audit_id'] = "testtestetst";
        self::assertEquals($websiteEntity3->toArray(), $website->toArray());
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
