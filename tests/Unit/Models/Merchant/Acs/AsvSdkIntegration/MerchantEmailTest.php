<?php

namespace Unit\Models\Merchant\Acs\AsvSdkIntegration;

use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantEmailResponseByMerchantId;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;
use RZP\Tests\Functional\TestCase;

class MerchantEmailTest extends TestCase
{
    private  $merchantEmailEntityJson1 = ' {
            "id": "CzmiCwTPCL3t2R",
            "type": "support",
            "email": "support@rtll.com",
            "phone": "9999999999",
            "policy": "24x7 support",
            "url": "https://rtll.com/support/",
            "merchant_id": "CzmiBzNQPErfdT",
            "verified": 0,
            "created_at": 1564469734,
            "updated_at": 1564469734
        }';

    private $merchantEmailEntityJson2 = '{
            "id": "CzmiD0rBAGOort",
            "type": "refund",
            "email": "support@rtll.com",
            "phone": "9999999999",
            "policy": "24x7 support",
            "url": "https://rtll.com/support/",
            "merchant_id": "CzmiBzNQPErfdT",
            "verified": 0,
            "created_at": 1564469733,
            "updated_at": 1564469733
        }';

    private $merchantEmailEntityJson3 = ' {
            "id": "CzmiD1TWLy9PKw",
            "type": "partner_dummy",
            "email": "support@rtll.com",
            "phone": "9999999999",
            "policy": "24x7 support",
            "url": "https://rtll.com/support/",
            "merchant_id": "CzmiBzNQPErfdT",
            "verified": 0,
            "created_at": 1564469732,
            "updated_at": 1564469732
        }';


    public function testGetAllExceptPartnerDummyByMerchantId()
    {

        $merchantEmail = new MerchantEmail();
        $merchantEmailMockClient  = $this->getMockClient();
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);


        // prepare expected data //
        $merchantEmailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);
        $merchantEmailEntity2 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson2);

        // prepare mocks //
        $merchantEmailProto1 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson1);
        $merchantEmailProto2 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson2);
        $merchantEmailProto3 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson3);

        // Test Case 1 - Success
        $merchantEmailResponse = new MerchantEmailResponseByMerchantId();
        $merchantEmailResponse->setEmails([$merchantEmailProto1, $merchantEmailProto2, $merchantEmailProto3]);
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([$merchantEmailResponse, null]);

        $expectedMerchantEmailCollectionForGetAllExceptPartnerDummyByMerchantId = (new MerchantEmailEntity)->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmailCollection = $merchantEmail->getAllExceptPartnerDummyByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals($expectedMerchantEmailCollectionForGetAllExceptPartnerDummyByMerchantId->toArray(), $gotMerchantEmailCollection->toArray());

        // Test Case 2 - Merchant Email Not found
        try {
            $merchantEmailMockClient = $this->getMockClient();
            $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdK", $merchantEmail->getDefaultRequestMetaData())->willReturn([ new MerchantEmailResponseByMerchantId(), null]);
            $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

            $response = $merchantEmail->getAllExceptPartnerDummyByMerchantId("CzmiBzNQPErfdK");
            self::assertEquals([],$response->toArray());
        } catch (\Exception $e) {
            self::fail("Exception not expected: ".$e->getMessage());
        }

        // Test Case 3 - Any Other Exceptions
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $merchantEmailMockClient = $this->getMockClient();
            $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bJ", $merchantEmail->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

            $merchantEmail->getAllExceptPartnerDummyByMerchantId("K4O9sCGihrL2bJ");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    public function testGetByTypeAndMerchantId()
    {
        $merchantEmail = new MerchantEmail();
        $merchantEmailMockClient  = $this->getMockClient();
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        // prepare expected data //
        $merchantEmailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);

        // prepare mocks //
        $merchantEmailProto1 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson1);
        $merchantEmailProto2 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson2);
        $merchantEmailProto3 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson3);

        // Test Case 1 - Success
        $merchantEmailResponse = new MerchantEmailResponseByMerchantId();
        $merchantEmailResponse->setEmails([$merchantEmailProto1, $merchantEmailProto2, $merchantEmailProto3]);
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([$merchantEmailResponse, null]);

        $gotMerchantEmail= $merchantEmail->getByTypeAndMerchantId("support", "CzmiBzNQPErfdT");
        self::assertEquals($gotMerchantEmail->toArray(), $merchantEmailEntity1->toArray());

        // Test Case 2 - Merchant Email Found For Merchant id but not for type
        try {
            $merchantEmailMockClient = $this->getMockClient();
            $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([ new MerchantEmailResponseByMerchantId(), null]);
            $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

            $response = $merchantEmail->getByTypeAndMerchantId("random_type", "CzmiBzNQPErfdT");
            self::assertEquals(null, $response);
        } catch (\Exception $e) {
            self::fail("Exception not expected: ".$e->getMessage());
        }

        // Test Case 3 - Merchant Email Not found for Merchant Id
        try {
            $merchantEmailMockClient = $this->getMockClient();
            $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdK", $merchantEmail->getDefaultRequestMetaData())->willReturn([ new MerchantEmailResponseByMerchantId(), null]);
            $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

            $response = $merchantEmail->getByTypeAndMerchantId("dispute", "CzmiBzNQPErfdK");
            self::assertEquals(null, $response);
        } catch (\Exception $e) {
            self::fail("Exception not expected: ".$e->getMessage());
        }

        // Test Case 4 - Any Other Exceptions
        try {
            $grpcError = new GrpcError(\Grpc\STATUS_ABORTED, "new");
            $merchantEmailMockClient = $this->getMockClient();
            $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("K4O9sCGihrL2bJ", $merchantEmail->getDefaultRequestMetaData())->willReturn([null, $grpcError]);
            $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

            $merchantEmail->getByTypeAndMerchantId('dispute', "K4O9sCGihrL2bJ");
            self::fail("Expected not found exception");
        } catch (\Exception $e) {
            self::assertEquals(ErrorCode::ASV_SERVER_ERROR, $e->getCode());
        }
    }

    private function getMerchantEmailProtoFromJson(string $json): \Rzp\Accounts\Merchant\V1\Email
    {
        $merchantEmailProto = new \Rzp\Accounts\Merchant\V1\Email();
        $merchantEmailProto->mergeFromJsonString($json, false);
        return $merchantEmailProto;
    }

    private function getMerchantEmailEntityFromJson(string $json): MerchantEmailEntity
    {
        $merchantEmailArray = json_decode($json, true);
        $merchantEmailEntity  =  new MerchantEmailEntity();
        $merchantEmailEntity->setRawAttributes($merchantEmailArray);
        return $merchantEmailEntity;
    }

    private function getMockClient() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\EmailInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

}
