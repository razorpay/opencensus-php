<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use RZP\Models\Base\PublicCollection;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Wrapper\MerchantStakeholder;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Exception\IntegrationException;
use RZP\Modules\Acs\Wrapper\MerchantWebsite;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;
use RZP\Models\Address\Entity as AddressEntity;
use Rzp\Accounts\Account\V1 as accountV1;
use RZP\Modules\Acs\Wrapper\Constant;

class MerchantStakeholderWrapperTest extends TestCase
{

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    private function getAddressForStakeholder() {
        $id = "10000000000111";
        $stakeholderEntity = "10000000000000";
        $data = [
            AddressEntity::ID => "hello123",
            AddressEntity::PRIMARY => true,
            AddressEntity::LINE1 => "123",
            AddressEntity::LINE2 => "1234",
            AddressEntity::CREATED_AT => 1234,
        ];
        AddressEntity::unguard();
        $addressEntity = new AddressEntity($data);
        AddressEntity::reguard();

        return $addressEntity;
    }

    private function getMerchantStakholderEntity() {
        $id = "10000000000111";
        $merchant_id = "10000000000000";

        MerchantStakeholderEntity::unguard();
        $data = [
            MerchantStakeholderEntity::ID => $id,
            MerchantStakeholderEntity::MERCHANT_ID => $merchant_id,
            MerchantStakeholderEntity::DIRECTOR => true,
            MerchantStakeholderEntity::BVS_PROBE_ID => "test123",
            MerchantStakeholderEntity::NOTES => [
                "ok" => "123",
            ],
            MerchantStakeholderEntity::UPDATED_AT => 124,
            MerchantStakeholderEntity::NAME => "Manthan",
            MerchantStakeholderEntity::PERCENTAGE_OWNERSHIP => 50,
        ];
        $merchantStakeholderEntity = new MerchantStakeholderEntity($data);
        MerchantStakeholderEntity::reguard();
        return $merchantStakeholderEntity;
    }

    private function getMerchantStakholderEntityCollection() {
        $merchant_id = "10000000000000";
        MerchantStakeholderEntity::unguard();
        $data = [
            MerchantStakeholderEntity::ID => "12345",
            MerchantStakeholderEntity::MERCHANT_ID => $merchant_id,
            MerchantStakeholderEntity::DIRECTOR => true,
            MerchantStakeholderEntity::BVS_PROBE_ID => "test123",
            MerchantStakeholderEntity::NOTES => [
                "ok" => "123",
            ],
            MerchantStakeholderEntity::UPDATED_AT => 124,
            MerchantStakeholderEntity::NAME => "Manthan",
            MerchantStakeholderEntity::PERCENTAGE_OWNERSHIP => 40,
        ];
        $merchantStakeholderEntity1 = new MerchantStakeholderEntity($data);

        $data = [
            MerchantStakeholderEntity::ID => "123456",
            MerchantStakeholderEntity::MERCHANT_ID => $merchant_id,
            MerchantStakeholderEntity::DIRECTOR => true,
            MerchantStakeholderEntity::BVS_PROBE_ID => "test125",
            MerchantStakeholderEntity::NOTES => [
                "ok" => "1235",
            ],
            MerchantStakeholderEntity::UPDATED_AT => 124,
            MerchantStakeholderEntity::NAME => "Manthans"
        ];

        $merchantStakeholderEntity2 = new MerchantStakeholderEntity($data);

        MerchantStakeholderEntity::reguard();
        return new PublicCollection([$merchantStakeholderEntity1, $merchantStakeholderEntity2]);
    }

    function testProcessGetAddressBYIdShadow()
    {
        $id = "10000000000111";
        $merchant_id = "10000000000000";
        $merchantStakeholderEntity = $this->getMerchantStakholderEntity();
        $apiAddressEntity = $this->getAddressForStakeholder();

        #----------
        #T0 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, false);
        $gotAddress = $merchantStakeholderWrapperMock->processFetchPrimaryResidentialAddressForStakeholder($merchantStakeholderEntity, $apiAddressEntity);
        self::assertEquals($apiAddressEntity, $gotAddress);
        #T0 end
        #----------

        #----------
        #T1 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # No exception from client, No diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "addresses" => [
                $apiAddressEntity->toArray()
            ]
        ];

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('address', [], $id, '');
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotAddress = $merchantStakeholderWrapperMock->processFetchPrimaryResidentialAddressForStakeholder($merchantStakeholderEntity, $apiAddressEntity);
        self::assertEquals($apiAddressEntity, $gotAddress);
        #T1 end
        #----------

        #----------
        #T2 Starts - Shadow on, reverse shadow off - Get same API address as sent, no matter what ASV Client sends.
        # No exception from client, diff present.
        #----------

        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "addresses" => [
                $apiAddressEntity->toArray()
            ]
        ];

        $asvData["addresses"][0][AddressEntity::LINE1] = "manthan";

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('address', ['line1'], $id, '');
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotAddress = $merchantStakeholderWrapperMock->processFetchPrimaryResidentialAddressForStakeholder($merchantStakeholderEntity, $apiAddressEntity);
        self::assertEquals($apiAddressEntity, $gotAddress);
        ### T2 end

        #----------
        #T3 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # exception from client, exception should not be propogaed
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $exception = new IntegrationException("Test");

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(0))->method('logDifferenceIfNotNilAndPushMetrics');
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotAddress = $merchantStakeholderWrapperMock->processFetchPrimaryResidentialAddressForStakeholder($merchantStakeholderEntity, $apiAddressEntity);
        self::assertEquals($apiAddressEntity, $gotAddress);
        #T3 end
        #----------
    }

    function testProcessGetAddressBYIdReverseShadow(){

        $id = "10000000000111";
        $merchant_id = "10000000000000";
        $merchantStakeholderEntity = $this->getMerchantStakholderEntity();
        $apiAddressEntity = $this->getAddressForStakeholder();
        #----------
        #T4 Starts - Shadow off, reverse shadow on - Overwrite with API response
        # throw exception, exception should be propogated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $exception = new IntegrationException("Test");

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(0))->method('logDifferenceIfNotNilAndPushMetrics');
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantStakeholderWrapperMock->processFetchPrimaryResidentialAddressForStakeholder($merchantStakeholderEntity, $apiAddressEntity);
            $this->fail("Exception is expected");
        }catch (\Throwable $ex){
            $this->assertEquals($exception, $ex);
        }


        #----------
        #T5 Starts - Shadow off, reverse shadow on - Overwrite with API response
        # no exception, returned Entity should be updated
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "addresses" => [
                $apiAddressEntity->toArray()
            ]
        ];
        $asvData["addresses"][0][AddressEntity::LINE1] = "manthan";

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('address', ['line1'], $id, '');
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $gotAsvAddressEntity = $merchantStakeholderWrapperMock->processFetchPrimaryResidentialAddressForStakeholder($merchantStakeholderEntity, $apiAddressEntity);
            $apiAddressEntity[AddressEntity::LINE1  ] = "manthan";
            self::assertEquals($apiAddressEntity, $gotAsvAddressEntity);
        }catch (\Throwable $ex){
            $this->fail("Exception is not expected");
        }
    }

    function testProcessGetStakeholderBYIdShadow()
    {
        $id = "10000000000111";
        $merchant_id = "10000000000000";
        $merchantStakeholderEntity = $this->getMerchantStakholderEntity();

        #----------
        #T0 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, false);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholderById($id, $merchantStakeholderEntity);
        self::assertEquals($merchantStakeholderEntity, $gotMerchantStakeholderEntity);
        #T0 end
        #----------

        #----------
        #T1 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # No exception from client, No diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "stakeholders" => [
                [
                    MerchantStakeholderEntity::ID => $id,
                    MerchantStakeholderEntity::MERCHANT_ID => $merchant_id,
                    MerchantStakeholderEntity::DIRECTOR => true,
                    MerchantStakeholderEntity::NOTES => [
                        "ok" => "123",
                    ],
                    MerchantStakeholderEntity::UPDATED_AT => 124,
                    MerchantStakeholderEntity::NAME => "Manthan",
                    MerchantStakeholderEntity::PERCENTAGE_OWNERSHIP => 50,
                ]
            ]
        ];

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', [], $id, "");
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholderById("10000000000111", $merchantStakeholderEntity);
        self::assertEquals($merchantStakeholderEntity, $gotMerchantStakeholderEntity);
        #T1 end
        #----------

        #----------
        #T2 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # No exception from client, diff present.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "stakeholders" => [
                [
                    MerchantStakeholderEntity::ID => $id,
                    MerchantStakeholderEntity::MERCHANT_ID => $merchant_id,
                    MerchantStakeholderEntity::DIRECTOR => true,
                    MerchantStakeholderEntity::NOTES => [
                        "ok" => "1235",
                    ],
                    MerchantStakeholderEntity::NAME => "Manthans",
                    MerchantStakeholderEntity::PERCENTAGE_OWNERSHIP => 50
                ]
            ]
        ];


        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);
        $difference = ["notes->ok", "name"];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, $id, "");
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholderById($id, $merchantStakeholderEntity);
        self::assertEquals($merchantStakeholderEntity, $gotMerchantStakeholderEntity);
        #T2 end
        #----------


        #----------
        #T3 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # exception from client, exception should not be propogaed
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $exception = new IntegrationException("Test");

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(0))->method('logDifferenceIfNotNilAndPushMetrics');
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholderById($id, $merchantStakeholderEntity);
        self::assertEquals($merchantStakeholderEntity, $gotMerchantStakeholderEntity);
        #T3 end
        #----------
    }

    function testProcessGetStakeholderBYMerchantIdShadow()
    {
        $id = "10000000000111";
        $merchant_id = "10000000000000";
        $merchantStakeholderCollection = $this->getMerchantStakholderEntityCollection();

        #----------
        #T0 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, false);
        $gotMerchantStakeholderCollection = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, $merchantStakeholderCollection);
        self::assertEquals($merchantStakeholderCollection, $gotMerchantStakeholderCollection);
        #T0 end
        #----------

        #----------
        #T1 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # No exception from client, No diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "stakeholders" => ASVEntityMapper::EntitiesToArrayWithRawValues($merchantStakeholderCollection)
        ];

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);
        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', [], "", $merchant_id);
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId("10000000000000", $merchantStakeholderCollection);
        self::assertEquals($merchantStakeholderCollection, $gotMerchantStakeholderEntity);
        #T1 end
        #----------

        #----------
        #T2 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # No exception from client, diff present.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvData = [
            "stakeholders" =>  ASVEntityMapper::EntitiesToArrayWithRawValues($merchantStakeholderCollection)
        ];

        $asvData["stakeholders"][0]['name'] = "change";
        $asvData["stakeholders"][1]['notes'] = [
            "ok" => "x"
        ];

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);
        $difference = [
            "12345" => ["name"],
            "123456" => ["notes->ok"]
        ];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, "",$merchant_id);
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, $merchantStakeholderCollection);
        self::assertEquals($merchantStakeholderCollection, $gotMerchantStakeholderEntity);
        #T2 end
        #----------


        #----------
        #T3 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent, no matter what ASV Client sends.
        # exception from client, exception should not be propogaed
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $exception = new IntegrationException("Test");

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(0))->method('logDifferenceIfNotNilAndPushMetrics');
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($id, $merchantStakeholderCollection);
        self::assertEquals($merchantStakeholderCollection, $gotMerchantStakeholderEntity);
        #T3 end
        #----------

        #T4 Starts - Shadow on, reverse shadow off - Get same API Stakeholder as sent and log diff, in case of empty response from ASV.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);
        $difference = [
            "12345" => "Entity Present in only one of ASV/API",
            "123456" => "Entity Present in only one of ASV/API",
        ];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, "",$merchant_id);
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, $merchantStakeholderCollection);
        self::assertEquals($merchantStakeholderCollection, $gotMerchantStakeholderEntity);
        #T4 end
        #----------

    }

    function testProcessGetStakeholderBYMerchantIdReverseShadow(){

        $id = "10000000000111";
        $merchant_id = "10000000000000";
        $merchantStakeholderEntity = $this->getMerchantStakholderEntityCollection();
        #----------
        #T5 Starts - Shadow off, reverse shadow on - Overwrite with API response
        # throw exception, exception should be propogated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $exception = new IntegrationException("Test");

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(0))->method('logDifferenceIfNotNilAndPushMetrics');
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, $merchantStakeholderEntity);
            $this->fail("Exception is expected");
        }catch (\Throwable $ex){
            $this->assertEquals($exception, $ex);
        }


        #----------
        #T6 Starts - Shadow off, reverse shadow on - Overwrite with API response
        # no exception, returned Entity should be updated
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $asvData = [
            "stakeholders" =>  ASVEntityMapper::EntitiesToArrayWithRawValues($merchantStakeholderEntity),
        ];

        $asvData["stakeholders"][0]['name'] = "change";
        $asvData["stakeholders"][1]['notes'] = [
            "ok" => "x"
        ];

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $difference = [
            "12345" => ["name"],
            "123456" => ["notes->ok"]
        ];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, "", $merchant_id);
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $gotStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, $merchantStakeholderEntity);
            $merchantStakeholderEntity[0][MerchantStakeholderEntity::NAME] = "change";
            $merchantStakeholderEntity[1][MerchantStakeholderEntity::NOTES] = [
                "ok" => "x",
            ];
            $this->assertEquals($merchantStakeholderEntity, $gotStakeholderEntity);
        }catch (\Throwable $ex){
            $this->fail("Exception not expected");
        }

        #----------
        #T7 Starts - Shadow off, reverse shadow on - Empty response from ASV, Returns empty collection and logs diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);
        $difference = [
            "12345" => "Entity Present in only one of ASV/API",
            "123456" => "Entity Present in only one of ASV/API",
        ];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, "",$merchant_id);
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive([$merchant_id, CONSTANT::SHADOW, CONSTANT::READ], [$merchant_id, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, $merchantStakeholderEntity);
        self::assertEquals(new PublicCollection([]), $gotMerchantStakeholderEntity);
        #T7 end
        #----------

        #----------
        #T8 Starts - Shadow off, reverse shadow on - Empty response from ASV, and empty collection from API: Returns empty collection and logs no diff
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);
        $difference = [];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, "",$merchant_id);
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive([$merchant_id, CONSTANT::SHADOW, CONSTANT::READ], [$merchant_id, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $gotMerchantStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholdersByMerchantId($merchant_id, new PublicCollection([]));
        self::assertEquals(new PublicCollection([]), $gotMerchantStakeholderEntity);
        #T8 end
        #----------
    }

    function testProcessGetStakeholderBYIdReverseShadow(){

        $id = "10000000000111";
        $merchant_id = "10000000000000";
        $merchantStakeholderEntity = $this->getMerchantStakholderEntity();
        #----------
        #T4 Starts - Shadow off, reverse shadow on - Overwrite with API response
        # throw exception, exception should be propogated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $exception = new IntegrationException("Test");

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $merchantStakeholderWrapperMock->expects($this->exactly(0))->method('logDifferenceIfNotNilAndPushMetrics');
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantStakeholderWrapperMock->processFetchStakeholderById($id, $merchantStakeholderEntity);
            $this->fail("Exception is expected");
        }catch (\Throwable $ex){
            $this->assertEquals($exception, $ex);
        }


        #----------
        #T5 Starts - Shadow off, reverse shadow on - Overwrite with API response
        # no exception, returned Entity should be updated
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('traceException');
        $merchantStakeholderWrapperMock = $this->getMockedMerchantStakeholderWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfNotNilAndPushMetrics']);
        $asvData = [
            "stakeholders" => [
                [
                    MerchantStakeholderEntity::ID => $id,
                    MerchantStakeholderEntity::MERCHANT_ID => $merchant_id,
                    MerchantStakeholderEntity::DIRECTOR => true,
                    MerchantStakeholderEntity::NOTES => [
                        "ok" => "1235",
                    ],
                    MerchantStakeholderEntity::NAME => "Manthans",
                    MerchantStakeholderEntity::PERCENTAGE_OWNERSHIP => 50,
                ]
            ]
        ];

        $asvResponse = new accountV1\FetchMerchantStakeholdersResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantStakeholderClient = $this->createStakeholderAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantStakeholderWrapperMock->setStakeholderAsvClient($merchantStakeholderClient);

        $difference = ["notes->ok", "name"];
        $merchantStakeholderWrapperMock->expects($this->exactly(1))->method('logDifferenceIfNotNilAndPushMetrics')->with('stakeholder', $difference, $id, "");
        $merchantStakeholderWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $gotStakeholderEntity = $merchantStakeholderWrapperMock->processFetchStakeholderById($id, $merchantStakeholderEntity);

            $merchantStakeholderEntity[MerchantStakeholderEntity::NAME] = "Manthans";
            $merchantStakeholderEntity[MerchantStakeholderEntity::NOTES] = [
                "ok" => "1235",
            ];
            $this->assertEquals($merchantStakeholderEntity, $gotStakeholderEntity);
        }catch (\Throwable $ex){
            $this->fail("Exception not expected");
        }

    }

    protected function getMockedMerchantStakeholderWrapper($methods = [])
    {
        return $this->getMockBuilder(MerchantStakeholder::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    protected function createStakeholderAsvClientWithProtoClientMockWithFetchResponse(?accountV1\FetchMerchantStakeholdersResponse $response, $exception = null): AsvClient\StakeholderAsvClient
    {

        $stakeholderAsvClient = new AsvClient\StakeholderAsvClient();
        $mockStakeholderApiClient =  $this->getMockBuilder(accountv1\StakeholderAPIClient::class)
            ->setConstructorArgs([$stakeholderAsvClient->getHost(), $stakeholderAsvClient->getHttpClient()])
            ->getMock();

        if (isset($exception)) {
            $mockStakeholderApiClient->expects($this->exactly(1))->method('FetchMerchantStakeholders')->willThrowException($exception);
        } else {
            $mockStakeholderApiClient->expects($this->exactly(1))->method('FetchMerchantStakeholders')->willReturn($response);
        }

        $stakeholderAsvClient->setStakeholderAsvClient($mockStakeholderApiClient);
        return $stakeholderAsvClient;
    }

    protected function createSaveApiAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\SaveApiAsvClient::class)
            ->getMock();
    }

}
