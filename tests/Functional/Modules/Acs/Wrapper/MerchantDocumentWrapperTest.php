<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Exception\IntegrationException;
use RZP\Modules\Acs\Wrapper\MerchantDocument;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;

class MerchantDocumentWrapperTest extends TestCase
{

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testDeleteOrFail()
    {
        MerchantDocumentEntity::unguard();
        $documentEntity = new MerchantDocumentEntity(['id' => '10000000000111', 'merchant_id' => '10000000000000']);
        MerchantDocumentEntity::reguard();

        $exception = new IntegrationException('some error encountered');

        #T1 Starts - Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument');

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
        #T1 Ends


        #T2 Starts - Shadow on - Failure in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument')->willThrowException($exception);

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
        #T2 Ends


        #T3 Starts - Reverse Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument');

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
        #T3 Ends

        #T4 Starts - Reverse Shadow on - Failure in Asv - Propagate Exception
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument')->willThrowException($exception);

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
            assertTrue(false);
        } catch (IntegrationException $e) {
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }
        #T4 Ends
    }

    protected function getMockedMerchantDocumentWrapper($methods = [])
    {
        return $this->getMockBuilder(MerchantDocument::class)
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

    protected function createAccountDocumentAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\AccountDocumentAsvClient::class)
            ->getMock();
    }
}
