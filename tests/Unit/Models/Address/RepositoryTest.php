<?php

namespace Unit\Models\Address;

use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Account\V1\Stakeholder;
use Rzp\Accounts\Merchant\V1\Address;
use Rzp\Accounts\Merchant\V1\AddressResponseByStakeholderId;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Address\Repository;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Stakeholder\Repository as StakeholderRepo;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder as StakeholderWrapper;


class RepositoryTest extends TestCase
{

    private $stakeholderEntityJson1 = '{
                            "id": "K9UzmvitzJwyS9",
                            "audit_id": "testtesttest",
                            "merchant_id": "K9UzmvitzJwyS8",
                            "email": "123@gmail.com",
                            "name": "test",
                            "phone_primary": "1234567890",
                            "phone_secondary": "1234567890",
                            "director": 1,
                            "executive": 1,
                            "percentage_ownership": 100,
                            "poi_identification_number": "1234567890",
                            "poi_status": "verified",
                            "poa_status": "verified",
                            "verification_metadata": "{\"test\":\"test\"}",
                            "notes": "{\"test\":\"test\"}",
                            "created_at": "12342",
                            "updated_at": "12342",
                            "deleted_at": null,
                            "pan_doc_status": "verified",
                            "aadhaar_esign_status": "verified",
                            "aadhaar_pin": "1234",
                            "aadhaar_linked": 1,
                            "aadhaar_verification_with_pan_status": "verified",
                            "bvs_probe_id": "1234"
                }';


        private $stakeholderEntityJson2 = '{
                            "id": "K9UzmvitzJwyS4",
                            "audit_id": "testtesttest",
                            "merchant_id": "K9UzmvitzJwyS0",
                            "email": "123@gmail.com",
                            "name": "test",
                            "phone_primary": "1234567890",
                            "phone_secondary": "1234567890",
                            "director": 1,
                            "executive": 1,
                            "percentage_ownership": 100,
                            "poi_identification_number": "1234567890",
                            "poi_status": "verified",
                            "poa_status": "verified",
                            "verification_metadata": "{\"test\":\"test\"}",
                            "notes": "{\"test\":\"test\"}",
                            "created_at": "12342",
                            "updated_at": "12342",
                            "deleted_at": null,
                            "pan_doc_status": "verified",
                            "aadhaar_esign_status": "verified",
                            "aadhaar_pin": "1234",
                            "aadhaar_linked": 1,
                            "aadhaar_verification_with_pan_status": "verified",
                            "bvs_probe_id": "1234"
                }';

    private $addressEntityJson1 = '{
         "id": "K9UzmvitzJwyS3",
          "entity_id": "K9UzmvitzJwyS9",
          "entity_type": "stakeholder",
          "line1":"342",
          "line2":"343",
          "city" : "23",
          "zipcode" :"2323",
          "state": "dfsdf",
          "country": "IND",
          "type" : "residential",
          "primary": 1,
          "deleted_at": null,
          "created_at": "2313",
          "updated_at": "2342",
          "contact": "435#",
          "tag" : "425",
          "landmark": "34f",
          "name": "afaF",
          "source_id":"Sdfsf",
          "source_type": "sdfsdf"
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

    public function testGetAddressByTypeAndMerchantId()
    {
        // Set splitz experiment
        Config::set('applications.asv_v2.splitz_experiment_address_read_by_stakeholder_id', 'K1ZaAHZ7Lnumc6');

        $this->createAddressInDatabase($this->addressEntityJson1);
        $this->createStakeholderInDatabase($this->stakeholderEntityJson1);
        $this->createStakeholderInDatabase($this->stakeholderEntityJson2);

        $address = new StakeholderWrapper();

        $stakeholder = (new StakeholderRepo())->fetchStakeholdersDatabase("K9UzmvitzJwyS8")->first();
        $stakeholderNoAddress = (new StakeholderRepo())->fetchStakeholdersDatabase("K9UzmvitzJwyS0")->first();

        $addressEntity1 = $this->getAddressEntityFromJson($this->addressEntityJson1);
        $addressProto1 = $this->getAddressProtoFromJson($this->addressEntityJson1);

        $addressResponse = new AddressResponseByStakeholderId();
        $addressResponse->setAddress($addressProto1);

        // Test Case 1: SaveRoute false - Splitz is on - Request should go to account service - Merchant Address is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K9UzmvitzJwyS9", $address->getDefaultRequestMetaData())->willReturn([$addressResponse, null]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");
        self::assertEquals($addressEntity1->toArray(), $gotAddress->toArray());

        // Test Case 2: SaveRoute true - Splitz is on - Request should not go to account service - Merchant Address is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(0))->method("getByStakeholderId")->with("K9UzmvitzJwyS9", $address->getDefaultRequestMetaData())->willReturn([$addressResponse, null]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(true);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");
        self::assertEquals($addressEntity1->toArray(), $gotAddress->toArray());

        // Test Case 3:  SaveRoute true - Splitz is on - Request should go to account service - Merchant Address is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K9UzmvitzJwyS9", $address->getDefaultRequestMetaData())->willReturn([new AddressResponseByStakeholderId(), null]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");;
        self::assertEquals(null, $gotAddress);

        // Test Case 4: SaveRoute false - Splitz is off - Request should not go to account service

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(0))->method("getByStakeholderId")->with($this->any())->willReturn([$addressResponse, null]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");
        self::assertEquals($addressEntity1->toArray(), $gotAddress->toArray());

        // Test Case 5: SaveRoute false - Splitz is off - Request  should not  go to account service - Merchant Address not Found

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(0))->method("getByStakeholderId")->with($this->any())->willReturn([$addressResponse, null]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholderNoAddress, "residential");;
        self::assertEquals(null, $gotAddress);

        // Test Case 6: SaveRoute false - Splitz call fails - Request should not go to account service.

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willThrowException(new \Exception("some error occurred while calling splitz"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(0))->method("getByStakeholderId")->with($this->any())->willReturn([$addressResponse, null]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");
        self::assertEquals($addressEntity1->toArray(), $gotAddress->toArray());


        // Test Case 7: SaveRoute false - Splitz is on - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $addressMockClient = $this->getMockClient();
        $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K9UzmvitzJwyS9", $address->getDefaultRequestMetaData())->willReturn([null, new GrpcError(\Grpc\STATUS_ABORTED, "new")]);
        $address->getAsvSdkClient()->setAddress($addressMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotAddress = $repo->fetchPrimaryAddressForStakeholderOfTypeResidential($stakeholder, "residential");
        self::assertEquals($addressEntity1->toArray(), $gotAddress->toArray());
    }

    private function createStakeholderInDatabase($json)
    {
        $this->fixtures->create("stakeholder",
            $this->getStakeholderEntityFromJson($json)->toArrayWithRawValuesForAccountService()
        );
    }

    private function getStakeholderEntityFromJson(string $json): StakeholderEntity
    {
        $stakeholderArray = json_decode($json, true);
        $stakeholderEntity = new StakeholderEntity();
        $stakeholderEntity->setRawAttributes($stakeholderArray);
        return $stakeholderEntity;
    }

    private function getOutputForRawDbCalls($repo, $id, $columns = null, $connectiontype = null)
    {
        if ($columns === null) {
            $findOrFailValue = $repo->findOrFailDatabase($id);
            $findOrFailPublicValue = $repo->findOrFailPublicDatabase($id);
        } else {
            $findOrFailValue = $repo->findOrFailDatabase($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailPublicDatabase($id, $columns, $connectiontype);
        }

        $findOrFailValueArray = $findOrFailValue->toArray();
        $findOrFailPublicValueArray = $findOrFailPublicValue->toArray();

        if (is_array($id)) {
            $findOrFailValueArray = $this->convertEntitiesToAssociativeArrayBasedOnId($findOrFailValueArray);
            $findOrFailPublicValueArray = $this->convertEntitiesToAssociativeArrayBasedOnId($findOrFailPublicValueArray);
        }

        $this->assertEquals($findOrFailValueArray, $findOrFailPublicValueArray);
        return $findOrFailValueArray;
    }


    private function convertEntitiesToAssociativeArrayBasedOnId(array $arrays): array
    {
        $result = [];
        foreach ($arrays as $array) {
            $result[$array['id']] = $array;
        }

        return $result;
    }

    private function createAddressInDatabase($json)
    {
        $this->fixtures->create("address",
            $this->getAddressEntityFromJson($json)->toArray(),
        );
    }


    private function getAddressProtoFromJson(string $json): Address
    {
        $addressProto = new Address();
        $addressProto->mergeFromJsonString($json, false);
        return $addressProto;
    }

    private function getAddressEntityFromJson(string $json): AddressEntity
    {
        $addressArray = json_decode($json, true);
        $addressEntity = new AddressEntity();
        $addressEntity->setRawAttributes($addressArray);
        return $addressEntity;
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
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\AddressInterface")
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
