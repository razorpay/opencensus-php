<?php

namespace Functional\Modules\Acs\Wrapper;

use Rzp\Accounts\Account\V1\FetchMerchantResponse;
use Rzp\Accounts\Account\V1\MerchantBusinessDetail;
use RZP\Constants\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\BusinessDetail\Entity as MerchantBusinessDetailEntity;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Modules\Acs\Wrapper\MerchantBusinessDetail as MerchantBusinessDetailWrapper;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Trace\TraceCode;

class MerchantBusinessDetailWrapperTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testMerchantBusinessDetailWrapperGetByMerchantId() {

        MerchantBusinessDetailEntity::unguard();
        $merchantId = "10000000000000";
        $merchantBusinessDetailEntityForApi = new MerchantBusinessDetailEntity(['merchant_id' => $merchantId]);
        $merchantBusinessDetailEntityOverWritten = new MerchantBusinessDetailEntity(['merchant_id' => $merchantId, 'business_parent_category' => 'online_store_marketplace']);
        MerchantBusinessDetailEntity::reguard();
        $merchantBusinessDetailProto = new MerchantBusinessDetail(['merchant_id' => $merchantId]);
        $merchantBusinessDetailProtoMismatch = new MerchantBusinessDetail(['merchant_id' => $merchantId, 'business_parent_category' => 'online_store_marketplace']);
        $fetchMerchantBusinessDetailResponse = new FetchMerchantResponse(['merchant_business_detail'=> $merchantBusinessDetailProto]);
        $fetchMerchantResponseMismatch = new FetchMerchantResponse(['merchant_business_detail'=> $merchantBusinessDetailProtoMismatch]);

        //Shadow mode returns values for api entity
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->never())->method('info');
        $traceMock->expects($this->never())->method('count');
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantBusinessDetailResponse);

        $merchantBusinessDetailWrapperMock = $this->getMockedMerchantBusinessDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantBusinessDetailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantBusinessDetailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedEntity = $merchantBusinessDetailWrapperMock->GetMerchantBusinessDetailForMerchantId($merchantId, $merchantBusinessDetailEntityForApi);
        self::assertEquals($merchantBusinessDetailEntityForApi, $returnedEntity);


        //ReverseShadow overrides common fields with values from asv and logs difference
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity" => "merchant_business_detail", "id" => $merchantId, "difference" => ["business_parent_category"]]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_business_detail"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantResponseMismatch);

        $merchantBusinessDetailWrapperMock = $this->getMockedMerchantBusinessDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantBusinessDetailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantBusinessDetailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
            withConsecutive([$merchantId, Constant::SHADOW, Constant::READ], [$merchantId, Constant::REVERSE_SHADOW, Constant::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedEntity = $merchantBusinessDetailWrapperMock->GetMerchantBusinessDetailForMerchantId($merchantId, $merchantBusinessDetailEntityForApi);
        self::assertEquals($merchantBusinessDetailEntityOverWritten, $returnedEntity);

        //Exception in ReadShadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $exception = new IntegrationException('error in FetchMerchant');
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::ERROR, TraceCode::ASV_READ_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant_business_detail"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantBusinessDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        try {
            $merchantWrapperMock->GetMerchantBusinessDetailForMerchantId($merchantId, $merchantBusinessDetailEntityForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception is not raised in shadow if asv call fails
            assertTrue(false);
        }

        //Exception in Reverse Shadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::CRITICAL, TraceCode::ASV_REVERSE_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant_business_detail"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantBusinessDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, Constant::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, Constant::READ])->willReturnOnConsecutiveCalls(false, true);
        try {
            $merchantWrapperMock->GetMerchantBusinessDetailForMerchantId($merchantId, $merchantBusinessDetailEntityForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception will be raised in reverse_shadow if asv call fails
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }
    }

    protected function createAccountAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\AccountAsvClient::class)
            ->getMock();
    }

    protected function getMockedMerchantBusinessDetailWrapper($methods = [])
    {
            return $this->getMockBuilder(MerchantBusinessDetailWrapper::class)
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
}
