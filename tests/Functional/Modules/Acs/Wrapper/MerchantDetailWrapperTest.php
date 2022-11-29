<?php

namespace Functional\Modules\Acs\Wrapper;

use Rzp\Accounts\Account\V1\FetchMerchantResponse;
use Rzp\Accounts\Account\V1\MerchantDetail;
use RZP\Constants\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Modules\Acs\Wrapper\MerchantDetail as MerchantDetailWrapper;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Trace\TraceCode;

class MerchantDetailWrapperTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testMerchantDetailWrapperGetByMerchantId() {

        MerchantDetailEntity::unguard();
        $merchantId = "10000000000000";
        $merchantDetailEntityForApi = new MerchantDetailEntity(['merchant_id' => $merchantId, 'contact_name' => 'name1', 'promoter_pan_name' => 'pan_name']);
        $merchantDetailEntityOverWritten = new MerchantDetailEntity(['merchant_id' => $merchantId, 'contact_name' => 'name2', 'promoter_pan_name' => 'pan_name']);
        MerchantDetailEntity::reguard();
        $merchantDetailProto = new MerchantDetail(['merchant_id' => $merchantId, 'contact_name' => 'name1']);
        $merchantDetailProtoMismatch = new MerchantDetail(['merchant_id' => $merchantId, 'contact_name' => 'name2']);
        $fetchMerchantDetailResponse = new FetchMerchantResponse(['merchant_detail'=> $merchantDetailProto]);
        $fetchMerchantResponseMismatch = new FetchMerchantResponse(['merchant_detail'=> $merchantDetailProtoMismatch]);

        //Shadow mode returns values for api entity
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->never())->method('info');
        $traceMock->expects($this->never())->method('count');
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantDetailResponse);

        $merchantDetailWrapperMock = $this->getMockedMerchantDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDetailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantDetailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedEntity = $merchantDetailWrapperMock->getByMerchantId($merchantId, $merchantDetailEntityForApi);
        self::assertEquals($merchantDetailEntityForApi, $returnedEntity);


        //ReverseShadow overrides common fields with values from asv and logs difference
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity" => "merchant_detail", "id" => $merchantId, "difference" => ["contact_name"]]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_detail"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantResponseMismatch);

        $merchantDetailWrapperMock = $this->getMockedMerchantDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDetailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantDetailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
            withConsecutive([$merchantId, Constant::SHADOW, Constant::READ], [$merchantId, Constant::REVERSE_SHADOW, Constant::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedEntity = $merchantDetailWrapperMock->getByMerchantId($merchantId, $merchantDetailEntityForApi);
        self::assertEquals($merchantDetailEntityOverWritten, $returnedEntity);

        //Exception in ReadShadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $exception = new IntegrationException('error in FetchMerchant');
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::ERROR, TraceCode::ASV_READ_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant_detail"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        try {
            $merchantWrapperMock->getByMerchantId($merchantId, $merchantDetailEntityForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception is not raised in shadow if asv call fails
            assertTrue(false);
        }

        //Exception in Reverse Shadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::CRITICAL, TraceCode::ASV_REVERSE_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant_detail"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantDetailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, Constant::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, Constant::READ])->willReturnOnConsecutiveCalls(false, true);
        try {
            $merchantWrapperMock->getByMerchantId($merchantId, $merchantDetailEntityForApi);
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

    protected function getMockedMerchantDetailWrapper($methods = [])
    {
            return $this->getMockBuilder(MerchantDetailWrapper::class)
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
