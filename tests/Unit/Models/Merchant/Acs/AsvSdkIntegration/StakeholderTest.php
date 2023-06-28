<?php

namespace Unit\Models\Merchant\Acs\AsvSdkIntegration;

use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\AddressResponseByStakeholderId;
use Rzp\Accounts\Merchant\V1\StakeholderResponse;
use Rzp\Accounts\Merchant\V1\StakeholderResponseByMerchantId;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\Entity as AddressEntity;


class StakeholderTest extends TestCase
{

    private $stakeholderEntityJson1 = '{
                            "id": "K9UzmvitzJwyS4",
                            "audit_id": "testtesttest",
                            "merchant_id": "K4O9sCGihrL2bG",
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
                            "deleted_at": "12342",
                            "pan_doc_status": "verified",
                            "aadhaar_esign_status": "verified",
                            "aadhaar_pin": "1234",
                            "aadhaar_linked": 1,
                            "aadhaar_verification_with_pan_status": "verified",
                            "bvs_probe_id": "1234"
                }';

    private $stakeholderEntityJson2 = '{
                         "id": "K9UzmvitzJwyS5",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                         "email": "123@ggmail.com",
                        "name": "test",
                        "phone_primary": "12343567890",
                        "phone_secondary": "12534567890",
                        "director": 1,
                        "executive": 1,
                        "percentage_ownership": 100,
                        "poi_identification_number": "1234567890",
                        "poi_status": "vevrified",
                        "poa_status": "vevrified",
                        "verification_metadata": "{\"te3st\":\"test\"}",
                        "notes": "{\"t3est\":\"test\"}",
                        "created_at": "123342",
                        "updated_at": "122342",
                        "deleted_at": "12342",
                        "pan_doc_status": "verified",
                        "aadhaar_esign_status": "verified",
                        "aadhaar_pin" : "1234",
                        "aadhaar_linked": 1,
                        "aadhaar_verification_with_pan_status": "verified",
                        "bvs_probe_id": "1234"
           }';

    private $stakeholderEntityJson3 = '{
                        "id": "K9UzmvitzJwyS3",
                        "audit_id": "testtesttest",
                        "merchant_id": "K4O9sCGihrL2bG",
                         "email": "123@gfmail.com",
                        "name": "test",
                        "phone_primary": "123f4567890",
                        "phone_secondary": "12f34567890",
                        "director": 1,
                        "executive": 1,
                        "percentage_ownership": 100,
                        "poi_identification_number": "1234d567890",
                        "poi_status": "verfified",
                        "poa_status": "veriffied",
                        "notes": "123d4",
                        "created_at": "12342",
                        "updated_at": "123442",
                        "deleted_at": "123242",
                        "pan_doc_status": "veriffied",
                        "aadhaar_esign_status": "vefrified",
                        "aadhaar_pin" : "12w34",
                        "aadhaar_linked": 1,
                        "aadhaar_verification_with_pan_status": "verfified",
                        "bvs_probe_id": "123f4"
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
          "country": "3424",
          "type" : "34234",
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


    public function testGetById()
    {

        $merchantStakeholder = new Stakeholder();
        $merchantStakeholderMockClient  = $this->getMockClient();
        $merchantStakeholder->getAsvSdkClient()->setStakeholder($merchantStakeholderMockClient);

        /* success */

        // prepare expected data //
        $stakeholderArray = json_decode($this->stakeholderEntityJson1, true);
        s($stakeholderArray);
        $expectedStakeholdersEntity  =  new StakeholderEntity();
        $expectedStakeholdersEntity->setRawAttributes($stakeholderArray);

        // prepare mocks //
        $stakeholderResponse = new StakeholderResponse();
        $stakeholderProto = new \Rzp\Accounts\Merchant\V1\Stakeholder();
        $stakeholderProto->mergeFromJsonString($this->stakeholderEntityJson1, false);
        $stakeholderResponse->setStakeholder($stakeholderProto);
        $merchantStakeholderMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH", $merchantStakeholder->getDefaultRequestMetaData())->willReturn([$stakeholderResponse, null]);

        // Call and Assert //
        $gotStakeholderEntity = $merchantStakeholder->getById("K4O9sCGihrL2bH");
        self::assertEquals($expectedStakeholdersEntity->toArray(), $gotStakeholderEntity->toArray());

        /* Not found Exception */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_NOT_FOUND, "new");
            $stakeholderMockClient = $this->getMockClient();
            $stakeholderMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH",  $merchantStakeholder->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantStakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

            $merchantStakeholder->getById("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID, $e->getCode());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $stakeholderMockClient = $this->getMockClient();
            $stakeholderMockClient->expects($this->exactly(1))->method("getById")->with("K4O9sCGihrL2bH",  $merchantStakeholder->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantStakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

            $merchantStakeholder->getById("K4O9sCGihrL2bH");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetByMerchantId()
    {

        $stakeholder = new Stakeholder();
        $stakeholderMockClient  = $this->getMockClient();
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        /* success */

        // prepare expected data //
        $stakeholderEntity1 = $this->getMerchantStakeholderEntityForJson($this->stakeholderEntityJson1);
        $stakeholderEntity2 = $this->getMerchantStakeholderEntityForJson($this->stakeholderEntityJson2);
        $expectedCollection = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2]);

        // prepare mocks //
        $stakeholderProto1 = $this->getStakeholderProtoForJson($this->stakeholderEntityJson1);
        $stakeholderProto2 = $this->getStakeholderProtoForJson($this->stakeholderEntityJson2);
        $stakeholderResponse = new StakeholderResponseByMerchantId();
        $stakeholderResponse->setStakeholders([$stakeholderProto1, $stakeholderProto2]);

        $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([$stakeholderResponse, null]);

        // Call and Assert //
        $gotstakeholderCollection = $stakeholder->getByMerchantId("K4O9sCGihrL2bH");
        self::assertEquals($expectedCollection->toArray(), $gotstakeholderCollection->toArray());

        /* Not found Exception */
        try {
            $stakeholderMockClient = $this->getMockClient();
            $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([ new StakeholderResponseByMerchantId(), null]);
            $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);
            $response = $stakeholder->getByMerchantId("K4O9sCGihrL2bH");
            self::assertEquals([],$response->toArray());
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Invalid Argument Exception */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "new");
            $stakeholderMockClient = $this->getMockClient();
            $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([ null, $grpcError]);
            $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);
            $response = $stakeholder->getByMerchantIdIgnoreInvalidArgument("K4O9sCGihrL2bH");
            self::assertEquals([],$response->toArray());
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $stakeholderMockClient = $this->getMockClient();
            $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

            $stakeholder->getByMerchantId("K4O9sCGihrL2bH");
            self::fail("Expected exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetAddressFromStakekholder() {
        $stakeholder = new Stakeholder();
        $addressMockClient  = $this->getMockClientForAddress();
        $stakeholder->getAsvSdkClient()->setAddress($addressMockClient);

        /* success */

        // prepare expected data //
        $addressEntity = $this->getAddressEntityForJson($this->addressEntityJson1);

        // prepare mocks //
        $addressProto = $this->getAddressProtoForJson($this->addressEntityJson1);
        $addressResponse = new AddressResponseByStakeholderId();
        $addressResponse->setAddress($addressProto);
        $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([$addressResponse, null]);

        // Call and Assert //
        $gotAddressResponse = $stakeholder->getAddressForStakeholderIgnoreInvalidArgument("K4O9sCGihrL2bH");
        self::assertEquals($addressEntity->toArray(), $gotAddressResponse->toArray());

        /* Not found Exception */
        try {
            $addressMockClient = $this->getMockClientForAddress();
            $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([ new AddressResponseByStakeholderId(), null]);
            $stakeholder->getAsvSdkClient()->setAddress($addressMockClient);
            $response = $stakeholder->getAddressForStakeholderIgnoreInvalidArgument("K4O9sCGihrL2bH");
            self::assertEquals(null,$response);
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Invalid Argument Exception */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "new");
            $addressMockClient = $this->getMockClientForAddress();
            $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $stakeholder->getAsvSdkClient()->setAddress($addressMockClient);
            $response = $stakeholder->getAddressForStakeholderIgnoreInvalidArgument("K4O9sCGihrL2bH");
            self::assertEquals(null, $response);
        } catch (\Exception $e) {
            self::fail("Expection not expected: ".$e->getMessage());
        }

        /* Other Exceptions */
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $addressMockClient = $this->getMockClientForAddress();
            $addressMockClient->expects($this->exactly(1))->method("getByStakeholderId")->with("K4O9sCGihrL2bH", $stakeholder->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $stakeholder->getAsvSdkClient()->setAddress($addressMockClient);
            $response = $stakeholder->getAddressForStakeholderIgnoreInvalidArgument("K4O9sCGihrL2bH");
            self::assertEquals(null,$response);
            self::fail("Expected exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }


    private function getStakeholderProtoForJson($json) {
        $stakeholderProto = new \Rzp\Accounts\Merchant\V1\Stakeholder();
        $stakeholderProto->mergeFromJsonString($json, false);
        return $stakeholderProto;
    }

    private function getAddressProtoForJson($json) {
        $addressProto = new \Rzp\Accounts\Merchant\V1\Address();
        $addressProto->mergeFromJsonString($json, false);
        return $addressProto;
    }


    private function getAddressEntityForJson($json) {
        $addressArray = json_decode($json, true);
        $addressEntity = new AddressEntity();
        $addressEntity->setRawAttributes($addressArray);
        return $addressEntity;
    }

    private function getMerchantStakeholderEntityForJson($json){
        $stakeholderArray = json_decode($json, true);
        $stakeholderEntity  =  new StakeholderEntity();
        $stakeholderEntity->setRawAttributes($stakeholderArray);
        return $stakeholderEntity;
    }

    private function getMockClient() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\StakeholderInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

    private function getMockClientForAddress() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\AddressInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }
}
