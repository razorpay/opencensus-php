<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use Razorpay\Trace\Logger as Trace;
use Rzp\Accounts\Account\V1 as accountV1;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Modules\Acs\Wrapper\MerchantWebsite;
use RZP\Tests\Functional\TestCase;

class MerchantWebsiteWrapperTest extends TestCase
{

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testProcessGetWebsiteDetailsForMerchantId()
    {
        MerchantWebsiteEntity::unguard();
        $data = [
            'id' => '10000000000111',
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkar',
            ],
            'deliverable_type' => '3-5 Day',
            'updated_at' => 124
        ];

        $merchantWebsiteEntity = new MerchantWebsiteEntity($data);
        MerchantWebsiteEntity::reguard();

        $exception = new IntegrationException('some error encountered');

        #----------
        #T0 Starts - Shadow on, reverse shadow off - Get same API Website as sent.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWebsiteWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->willReturn(false);
        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForMerchantId("10000000000111", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T0 end
        #----------

        #----------
        #T1 Starts - Shadow on, reverse shadow off - Get same API Website as sent, no matter what ASV Client sends.
        # No exception from client, No diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation']);

        $asvData = [
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkar',
            ],
            'deliverable_type' => '3-5 Day',
        ];

        $asvResponse = new accountV1\FetchMerchantWebsiteResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantWebsiteClient = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForMerchantId("10000000000000", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T1 end
        #----------


        #----------
        #T2 Starts - Shadow on, reverse shadow off - Get same API Website as sent, no matter what ASV Client sends.
        # No exception from client, Check Diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);

        $asvData = [
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkars',
            ],
            'deliverable_type' => '3-6 Day',
        ];

        $asvResponse = new accountV1\FetchMerchantWebsiteResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantWebsiteClient = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $difference = ['additional_data->manthan', 'deliverable_type'];
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000000');
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForMerchantId("10000000000000", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T2 end
        #----------

        #----------
        #T3 Starts - Shadow on, reverse shadow off - Get same API Website as sent, no matter what ASV Client sends.
        # throw exception, exception should not be propogated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);
        $exception                  = new IntegrationException("hello");
        $merchantWebsiteClient      = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $merchantWebsiteWrapperMock->expects($this->exactly(0))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000000');
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForMerchantId("10000000000000", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T3 end
        #----------


        #----------
        #T4 Starts - Shadow off, reverse shadow on
        # throw exception, exception should be propogated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);
        $exception                  = new IntegrationException("hello");
        $merchantWebsiteClient      = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $merchantWebsiteWrapperMock->expects($this->exactly(0))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000000');
        $merchantWebsiteWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantWebsiteWrapperMock->processGetWebsiteDetailsForMerchantId("10000000000000", $merchantWebsiteEntity);
            $this->fail('Exception was expected');
        } catch (\Throwable $ex) {
            $this->assertEquals($exception, $ex);
        }
        #T4 end
        #----------

        #----------
        #T5 Starts - Shadow off, reverse shadow on - Get updated website with response from ASV
        # do not throw exception, compare diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);
        $asvData                    = [
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkars',
            ],
            'deliverable_type' => '3-7 Day',
        ];
        $asvResponse                = new accountV1\FetchMerchantWebsiteResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantWebsiteClient = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $difference = ['additional_data->manthan', 'deliverable_type'];
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000000');
        $merchantWebsiteWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForMerchantId("10000000000000", $merchantWebsiteEntity);

        // reverse shadow should update this values.
        $merchantWebsiteEntity['deliverable_type'] = "3-7 Day";
        $merchantWebsiteEntity['additional_data']  = [
            'manthan' => 'surkars',
        ];
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);

        #T5 end
        #----------
    }

    function testProcessGetWebsiteDetailsForId()
    {
        MerchantWebsiteEntity::unguard();
        $data = [
            'id' => '10000000000111',
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkar',
            ],
            'deliverable_type' => '3-5 Day',
            'updated_at' => 124
        ];

        $merchantWebsiteEntity = new MerchantWebsiteEntity($data);
        MerchantWebsiteEntity::reguard();

        #----------
        #T0 Starts - Shadow on, reverse shadow off - Get same API Website as sent.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWebsiteWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->willReturn(false);
        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForId("10000000000111", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T0 end
        #----------

        #----------
        #T1 Starts - Shadow on, reverse shadow off - Get same API Website as sent, no matter what ASV Client sends.
        # No exception from client, No diff.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation']);

        $asvData = [
            'id' => '10000000000111',
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkar',
            ],
            'deliverable_type' => '3-5 Day',
        ];

        $asvResponse = new accountV1\FetchMerchantWebsiteResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantWebsiteClient = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForId("10000000000111", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T1 end
        #----------


        #----------
        #T2 Starts - Shadow on, reverse shadow off - Get same API Website as sent, no matter what ASV Client sends.
        # No exception from client, Check Diff.

        $asvData   = [
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkars',
            ],
            'deliverable_type' => '3-6 Day',
        ];
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);


        $asvResponse = new accountV1\FetchMerchantWebsiteResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantWebsiteClient = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $difference = ['additional_data->manthan', 'deliverable_type'];
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000111');
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForId("10000000000111", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T2 end
        #----------

        #----------
        #T3 Starts - Shadow on, reverse shadow off - Get same API Website as sent, no matter what ASV Client sends.
        # throw exception, exception should not be propogated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);
        $exception                  = new IntegrationException("hello");
        $merchantWebsiteClient      = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $merchantWebsiteWrapperMock->expects($this->exactly(0))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000111');
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForId("10000000000111", $merchantWebsiteEntity);
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);
        #T3 end
        #----------


        #----------
        #T4 Starts - Shadow off, reverse shadow on
        # throw exception, exception should be propagated.
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);
        $exception                  = new IntegrationException("hello");
        $merchantWebsiteClient      = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse(null, $exception);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $merchantWebsiteWrapperMock->expects($this->exactly(0))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000111');
        $merchantWebsiteWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantWebsiteWrapperMock->processGetWebsiteDetailsForId("10000000000111", $merchantWebsiteEntity);
            $this->fail('Exception was expected');
        } catch (\Throwable $ex) {
            $this->assertEquals($exception, $ex);
        }
        #T4 end
        #----------

        #----------
        #T5 Starts - Shadow off, reverse shadow on - Get updated website with response from ASV
        # do not throw exception, compare diff.
        $asvData = [
            'merchant_id' => '10000000000000',
            'merchant_website_details' => [
                'dilip' => 'chauhan',
            ],
            'admin_website_details' => [
                'pankaj' => 'kumar',
            ],
            'additional_data' => [
                'manthan' => 'surkars',
            ],
            'deliverable_type' => '3-7 Day',
        ];

        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $merchantWebsiteWrapperMock = $this->getMockedMerchantWebsiteWrapper(['isShadowOrReverseShadowOnForOperation', 'logDifferenceIfRequiredAndPushMetrics']);
        $asvResponse                = new accountV1\FetchMerchantWebsiteResponse();
        $asvResponse->mergeFromJsonString(json_encode($asvData), true);

        $merchantWebsiteClient = $this->createWebsiteAsvClientWithProtoClientMockWithFetchResponse($asvResponse);
        $merchantWebsiteWrapperMock->setMerchantWebsiteClient($merchantWebsiteClient);

        $difference = ['additional_data->manthan', 'deliverable_type'];
        $merchantWebsiteWrapperMock->expects($this->exactly(1))->method('logDifferenceIfRequiredAndPushMetrics')->with($difference, '10000000000111');
        $merchantWebsiteWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', CONSTANT::SHADOW, CONSTANT::READ], ['10000000000000', CONSTANT::REVERSE_SHADOW, CONSTANT::READ])
            ->willReturnOnConsecutiveCalls(false, true);
        $gotMerchantWebsiteEntity = $merchantWebsiteWrapperMock->processGetWebsiteDetailsForId("10000000000111", $merchantWebsiteEntity);

        // reverse shadow should update this values.
        $merchantWebsiteEntity['deliverable_type'] = "3-7 Day";
        $merchantWebsiteEntity['additional_data']  = [
            'manthan' => 'surkars',
        ];
        self::assertEquals($merchantWebsiteEntity, $gotMerchantWebsiteEntity);

        #T5 end
        #----------
    }

    protected function getMockedMerchantWebsiteWrapper($methods = [])
    {
        return $this->getMockBuilder(MerchantWebsite::class)
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

    protected function createWebsiteAsvClientWithProtoClientMockWithFetchResponse(?accountV1\FetchMerchantWebsiteResponse $response, $exception = null): AsvClient\WebsiteAsvClient
    {

        $websiteAsvClient     = new AsvClient\WebsiteAsvClient();
        $mockWebsiteApiClient = $this->getMockBuilder(accountv1\WebsiteAPIClient::class)
            ->setConstructorArgs([$websiteAsvClient->getHost(), $websiteAsvClient->getHttpClient()])
            ->getMock();

        if (isset($exception)) {
            $mockWebsiteApiClient->expects($this->exactly(1))->method('FetchMerchantWebsite')->willThrowException($exception);
        } else {
            $mockWebsiteApiClient->expects($this->exactly(1))->method('FetchMerchantWebsite')->willReturn($response);
        }

        $websiteAsvClient->merchantWebsiteClient = $mockWebsiteApiClient;
        return $websiteAsvClient;
    }

    protected function createSaveApiAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\SaveApiAsvClient::class)
            ->getMock();
    }

}
