<?php

namespace Functional\Modules\Acs\Wrapper;

use Rzp\Accounts\Account\V1\FetchMerchantResponse;
use Rzp\Accounts\Account\V1\Merchant;
use RZP\Constants\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Modules\Acs\Wrapper\Merchant as MerchantWrapper;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Trace\TraceCode;
use RZP\Modules\Acs\Wrapper\Constant;


class MerchantWrapperTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testMerchantWrapperFindOrFail()
    {
        $merchantId = "10000000000000";
        MerchantEntity::unguard();
        $merchantEntityForApi = new MerchantEntity(['id' => $merchantId, "live"=> true, 'org_id' => "100000razorpay", "signup_via_email" => false]);
        $merchantEntityForAsvWithMismatch = new MerchantEntity(['id' => $merchantId, 'org_id' => "100001razorpay", "live" => true, "signup_via_email" => null]);
        MerchantEntity::reguard();
        $merchantProto = new Merchant(['id' => $merchantId, 'org_id' => '100000razorpay']);
        $fetchMerchantResponse = new FetchMerchantResponse(['merchant'=> $merchantProto]);
        $merchantProtoMismatch = new Merchant(['id' => $merchantId, 'org_id' => '100001razorpay']);
        $fetchMerchantResponseMismatch = new FetchMerchantResponse(['merchant'=> $merchantProtoMismatch]);

        //Shadow mode returns values from api entity
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->never())->method('info');
        $traceMock->expects($this->never())->method('count');
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantResponse);

        $merchantWrapperMock = $this->getMockedMerchantWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedEntity = $merchantWrapperMock->FindOrFail($merchantId, $merchantEntityForApi);
        self::assertEquals($merchantEntityForApi, $returnedEntity);

        //ReverseShadow overrides common fields with values from asv and logs difference
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity" => "merchant", "id" => $merchantId, "difference" => ["org_id"]]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant"]]);
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantResponseMismatch);

        $merchantWrapperMock = $this->getMockedMerchantWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
            withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedEntity = $merchantWrapperMock->FindOrFail($merchantId, $merchantEntityForApi);
        self::assertEquals($merchantEntityForAsvWithMismatch, $returnedEntity);

        //Exception in ReadShadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $exception = new IntegrationException('error in FetchMerchant');
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::ERROR, TraceCode::ASV_READ_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        try {
            $merchantWrapperMock->FindOrFail($merchantId, $merchantEntityForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception is not raised in shadow if asv call fails
            assertTrue(false);
        }

        //Exception in Reverse Shadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::CRITICAL, TraceCode::ASV_REVERSE_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        try {
            $merchantWrapperMock->FindOrFail($merchantId, $merchantEntityForApi);
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

    protected function getMockedMerchantWrapper($methods = [])
    {
        return $this->getMockBuilder(MerchantWrapper::class)
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
