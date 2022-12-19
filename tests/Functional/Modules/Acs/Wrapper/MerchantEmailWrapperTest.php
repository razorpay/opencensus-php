<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use Rzp\Accounts\Account\V1\FetchMerchantResponse;
use RZP\Models\Base\PublicCollection;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Exception\IntegrationException;
use RZP\Modules\Acs\Wrapper\MerchantEmail;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;
use Rzp\Accounts\Account\V1\MerchantEmail as MerchantEmailProto;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Modules\Acs\Wrapper\Constant;

class MerchantEmailWrapperTest extends TestCase
{

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testDelete()
    {
        MerchantEmailEntity::unguard();
        $emailEntity = new MerchantEmailEntity(['id' => '10000000000111', 'merchant_id' => '10000000000000', 'type' => 'refund']);
        MerchantEmailEntity::reguard();

        $exception = new IntegrationException('some error encountered');

        #T1 Starts - Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('DeleteAccountContact');

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantEmailWrapperMock->Delete($emailEntity);
        #T1 Ends


        #T2 Starts - Shadow on - Failure in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('DeleteAccountContact')->willThrowException($exception);

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantEmailWrapperMock->Delete($emailEntity);
        #T2 Ends


        #T3 Starts - Reverse Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('DeleteAccountContact');

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        $merchantEmailWrapperMock->Delete($emailEntity);
        #T3 Ends

        #T4 Starts - Reverse Shadow on - Failure in Asv - Propagate Exception
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('DeleteAccountContact')->willThrowException($exception);

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantEmailWrapperMock->Delete($emailEntity);
            assertTrue(false);
        } catch (IntegrationException $e) {
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }
        #T4 Ends
    }

    function testFetchAndCompareMerchantEmailsFromMerchantId() {
        MerchantEmailEntity::unguard();
        $merchantId = "10000000000000";
        $id = "10000000000001";
        $merchantEmailCollectionForApi = new PublicCollection([new MerchantEmailEntity(['id' => $id, 'merchant_id' => $merchantId, 'type' => 'refund'])]);
        $merchantEmailCollectionOverWritten = new PublicCollection([new MerchantEmailEntity(['id' => $id, 'merchant_id' => $merchantId, 'type' => 'support'])]);
        MerchantEmailEntity::reguard();
        $merchantEmailProto = new MerchantEmailProto(['merchant_id' => $merchantId, 'type' => 'refund', 'id' => $id]);
        $merchantEmailProtoMismatch = new MerchantEmailProto(['merchant_id' => $merchantId, 'type' => 'support', 'id' => $id]);
        $fetchMerchantEmailResponse = new FetchMerchantResponse(['merchant_emails'=> [$merchantEmailProto]]);
        $fetchMerchantResponseMismatch = new FetchMerchantResponse(['merchant_emails'=> [$merchantEmailProtoMismatch]]);

        //Shadow mode returns values for api entity
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->never())->method('info');
        $traceMock->expects($this->never())->method('count');
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantEmailResponse);

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedEntity = $merchantEmailWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmailCollectionForApi);
        self::assertEquals($merchantEmailCollectionForApi, $returnedEntity);


        //ReverseShadow overrides common fields with values from asv and logs difference
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity_name" => "merchant_email", "merchant_id" => $merchantId, 'email_id' => '', "difference" => [$id => ["type"]]]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_email"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantResponseMismatch);

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, Constant::SHADOW, Constant::READ], [$merchantId, Constant::REVERSE_SHADOW, Constant::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedEntity = $merchantEmailWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmailCollectionForApi);
        self::assertEquals($merchantEmailCollectionOverWritten, $returnedEntity);

        //Exception in ReadShadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $exception = new IntegrationException('error in FetchMerchant');
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::ERROR, TraceCode::ASV_READ_SHADOW_EXCEPTION, ["merchant_id" => $merchantId, "entity" => "merchant_email"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        try {
            $merchantWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmailCollectionForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception is not raised in shadow if asv call fails
            assertTrue(false);
        }

        //Exception in Reverse Shadow From FetchMerchant
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::CRITICAL, TraceCode::ASV_REVERSE_SHADOW_EXCEPTION, ["merchant_id" => $merchantId, "entity" => "merchant_email"]]);

        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willThrowException($exception);
        $merchantWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, Constant::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, Constant::READ])->willReturnOnConsecutiveCalls(false, true);
        try {
            $merchantWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmailCollectionForApi);
            assertTrue(false);
        } catch (IntegrationException $e) {
            //Exception will be raised in reverse_shadow if asv call fails
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }

        //Empty Response from ASV in Shadow logged and returns api entities
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity_name" => "merchant_email", "email_id" => "", "difference" =>[$id =>"Entity Present in only one of ASV/API"], 'merchant_id' => $merchantId]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_email"]]);
        $fetchMerchantEmailsEmptyResponse = new FetchMerchantResponse();
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantEmailsEmptyResponse);
        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedCollection = $merchantEmailWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmailCollectionForApi);
        self::assertEquals($merchantEmailCollectionForApi, $returnedCollection);

        //Empty Response from ASV in ReverseShadow logged and returns empty collection
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity_name" => "merchant_email", "email_id" => "", "difference" =>[$id=>"Entity Present in only one of ASV/API"], 'merchant_id' => $merchantId]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_email"]]);
        $fetchMerchantEmailsEmptyResponse = new FetchMerchantResponse();
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantEmailsEmptyResponse);
        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedCollection = $merchantEmailWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmailCollectionForApi);
        self::assertEquals(new PublicCollection([]), $returnedCollection);

        //Empty Response from ASV in ReverseShadow with empty API collection: no diff logged and returns empty collection
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('info');
        $traceMock->expects($this->exactly(0))->method('count');
        $fetchMerchantEmailsEmptyResponse = new FetchMerchantResponse();
        $accountAsvClientMock = $this->createAccountAsvClientMock();
        $accountAsvClientMock->expects($this->exactly(1))->method('FetchMerchant')->willReturn($fetchMerchantEmailsEmptyResponse);
        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->accountAsvClient = $accountAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedCollection = $merchantEmailWrapperMock->FetchMerchantEmailsFromMerchantId($merchantId, new PublicCollection([]));
        self::assertEquals(new PublicCollection([]), $returnedCollection);
    }

    protected function getMockedMerchantEmailWrapper($methods = [])
    {
        return $this->getMockBuilder(MerchantEmail::class)
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

    protected function createAccountAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\AccountAsvClient::class)
            ->getMock();
    }
}
