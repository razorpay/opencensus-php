<?php

namespace Unit\Models\Address;

use Config;
use Google\Protobuf\StringValue;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Account\V1\Stakeholder;
use Rzp\Accounts\Merchant\V1\Address;
use Rzp\Accounts\Merchant\V1\AddressResponseByStakeholderId;
use Rzp\Accounts\Merchant\V1\AddressSaveRequest;
use Rzp\Accounts\Merchant\V1\EntitySaveResponse;
use Rzp\Accounts\Merchant\V1\MerchantSaveRequest;
use Rzp\Accounts\Merchant\V1\SaveRequest;
use Rzp\Accounts\Merchant\V1\SaveResponse;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\WriteEnabledOnAsv;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Address\Repository;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Merchant;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Stakeholder\Repository as StakeholderRepo;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder as StakeholderWrapper;
use Unit\Models\Merchant\TestingHelper\RepositoryTestHelper;


class RepositoryTest extends RepositoryTestHelper
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

        // Test Case 1: ExclusionFlow false - Splitz should Never be called - Request should go to account service - Merchant Address is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
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

        // Test Case 2: ExclusionFlow true - Splitz should Never be called - Request should not go to account service - Merchant Address is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
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

        // Test Case 3:  ExclusionFlow false - Splitz should Never be called - Request should go to account service - Merchant Address is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest');
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

        // Test Case 4: ExclusionFlow false - Splitz should Never be called - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest');
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

    public function testAddressSaveOrFailAsv()
    {

        /*
         *  Base Setup for the test
         *
         */

        $repo     = new Repository();
        $address = new StakeholderWrapper();

        $entity1 = $this->getAddressEntityFromJson($this->addressEntityJson1);

        $proto1 = $this->getAddressProtoFromJson($this->addressEntityJson1);
        $proto1->setCreatedAt(0);
        $proto1->setUpdatedAt(0);


        $saveResponse = (new SaveResponse())->setAddresses(
            [
                new EntitySaveResponse(
                [
                    "id" => "JWNkBHL4Waqqf8",
                    "created_at" => 10,
                    "updated_at" => 10,
                ]
            )
            ]
        );

        /*
         * Test 1: The save or fail ASV should not be reached if write is not enabled.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = false;
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);

        /*
        * Test  2: The save or fail ASV should not be reached if Splitz is off.
        */

        // false, false
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["false", "false"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);

        // true, false
        $this->setSplitzWithOutputForBulk(["true", "false"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);

        // false, true
        $this->setSplitzWithOutputForBulk(["false", "true"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($entity1);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        /*
        * Test  3: The save or fail ASV should not be reached if Splitz throws exception.
        */
        $this->setSplitzWithOutputForBulk(["false", "true"], 1, true);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);
//
        /*
        * Test  4-1: Save Or should work fine if splitz is on, created updated_at should be updated.
         *  Case for create
        */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $addressSaveRequest3 = new AddressSaveRequest();
        $entity1 = $this->getAddressEntityFromJson($this->addressEntityJson1);
        $addressSaveRequest3->setAddress($proto1);
        $addressSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setAddressSaveRequests([$addressSaveRequest3]);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $address->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($entity1);
        self::assertEquals(10, $entity1['created_at']);
        self::assertEquals(10, $entity1['updated_at']);

        /*
         * Test  4-2: Save Or should work fine if splitz is on, created updated_at should be updated. This is case of update
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $entity1['city']                        = "pune";
        $proto1->setCity((new StringValue())->setValue("pune"));
        $addressSaveRequest3 = new AddressSaveRequest();
        $addressSaveRequest3->setAddress($proto1);
        $addressSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setAddressSaveRequests([$addressSaveRequest3]);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $address->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($entity1);
        self::assertEquals(10, $entity1['created_at']);
        self::assertEquals(10, $entity1['updated_at']);


        /*
         * Test  4-3: Save operation should not happen if no dirty field present
         */
        $entity1->setRawAttributes($entity1->getAttributes(), true);
        $addressSaveRequest3 = new AddressSaveRequest();
        $addressSaveRequest3->setAddress($proto1);
        $addressSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setAddressSaveRequests([$addressSaveRequest3]);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->never())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $address->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($entity1);
        self::assertEquals(10, $entity1['created_at']);
        self::assertEquals(10, $entity1['updated_at']);


        /*
        * Test 5: Save or fail should fail, if Splitz is on, asv throw exception.
        */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $entity1 = $this->getAddressEntityFromJson($this->addressEntityJson1);
        $proto1 = $this->getAddressProtoFromJson($this->addressEntityJson1);
        $proto1->setCreatedAt(0);
        $proto1->setUpdatedAt(0);
        $addressSaveRequest3 = new AddressSaveRequest();
        $addressSaveRequest3->setAddress($proto1);
        $addressSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setAddressSaveRequests([$addressSaveRequest3]);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->
        willThrowException(new \RZP\Exception\BaseException("I am ASV Exception.", "ASV_SERVER_ERROR"));
        $address->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        try {
            $repo->saveOrFail($entity1);
            self::fail("Exception was expected.");
        } catch (\Exception $e) {
            self::assertEquals(\Illuminate\Database\QueryException::class, get_class($e));
            self::assertEquals("ASV_SERVER_ERROR", $e->getCode());
            self::assertEquals("I am ASV Exception. (SQL: )", $e->getMessage());
            self::assertEquals([], $e->getBindings());
            self::assertEquals("", $e->getSql());

            //created_at, updated_at not changed
            self::assertEquals(2313, $entity1['created_at']);
            self::assertEquals(2342, $entity1['updated_at']);
        }
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
