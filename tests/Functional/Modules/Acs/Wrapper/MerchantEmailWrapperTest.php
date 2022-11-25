<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Exception\IntegrationException;
use RZP\Modules\Acs\Wrapper\MerchantEmail;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

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

    function testSaveOrFail()
    {
        MerchantEmailEntity::unguard();
        $emailEntity = new MerchantEmailEntity(['id' => '10000000000111', 'merchant_id' => '10000000000000', 'type' => 'refund']);
        MerchantEmailEntity::reguard();

        $exception = new IntegrationException('some error encountered');

        #T1 Starts - Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity');

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantEmailWrapperMock->SaveOrFail($emailEntity);
        #T1 Ends


        #T2 Starts - Shadow on - Failure in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity')->willThrowException($exception);

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantEmailWrapperMock->SaveOrFail($emailEntity);
        #T2 Ends


        #T3 Starts - Reverse Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity');

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        $merchantEmailWrapperMock->SaveOrFail($emailEntity);
        #T3 Ends

        #T4 Starts - Reverse Shadow on - Failure in Asv - Propagate Exception
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity')->willThrowException($exception);

        $merchantEmailWrapperMock = $this->getMockedMerchantEmailWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantEmailWrapperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $merchantEmailWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantEmailWrapperMock->SaveOrFail($emailEntity);
            assertTrue(false);
        } catch (IntegrationException $e) {
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }
        #T4 Ends
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

    protected function createSaveApiAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\SaveApiAsvClient::class)
            ->getMock();
    }

    protected function createAccountAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\AccountAsvClient::class)
            ->getMock();
    }
}
