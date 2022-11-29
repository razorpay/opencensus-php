<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Exception\IntegrationException;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Modules\Acs\Wrapper\SaveApiHelper;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;


class SaveApiHelperTest extends TestCase
{

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testSaveOrFailForStakeholder()
    {
        MerchantStakeholderEntity::unguard();
        $stakeholderEntity = new MerchantStakeholderEntity(['id' => '10000000000111', 'merchant_id' => '10000000000000']);
        MerchantStakeholderEntity::reguard();
        $this->performTestOnSaveOrFail($stakeholderEntity->getMerchantId(), $stakeholderEntity->getEntityName(), $stakeholderEntity->getEntityName(), $stakeholderEntity->toArray());
    }

    function testSaveOrFailForAddress()
    {
        MerchantStakeholderEntity::unguard();
        $stakeholderEntity = new MerchantStakeholderEntity(['id' => '10000000000111', 'merchant_id' => '10000000000000']);
        MerchantStakeholderEntity::reguard();

        AddressEntity::unguard();
        $addressEntity = new AddressEntity(['id' => '10000000000112', 'entity_id' => '10000000000111', 'entity_type' => 'stakeholder']);
        AddressEntity::reguard();

        $entityArray = $stakeholderEntity->toArray();
        $entityArray['addresses']['residential'] = $addressEntity->toArray();

        $this->performTestOnSaveOrFail($stakeholderEntity->getMerchantId(), $stakeholderEntity->getEntityName(), $addressEntity->getEntityName(), $entityArray);
    }

    function performTestOnSaveOrFail(string $merchantId, string $entityName, string $childEntityName, array $entity = [])
    {

        $exception = new IntegrationException('some error encountered');

        #T1 Starts - Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity');

        $saveApiHelperMock = $this->getMockedSaveApiHelper(['isShadowOrReverseShadowOnForOperation']);
        $saveApiHelperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $saveApiHelperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $saveApiHelperMock->saveOrFail($merchantId, $entityName, $childEntityName, $entity);
        #T1 Ends


        #T2 Starts - Shadow on - Failure in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity')->willThrowException($exception);

        $saveApiHelperMock = $this->getMockedSaveApiHelper(['isShadowOrReverseShadowOnForOperation']);
        $saveApiHelperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $saveApiHelperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $saveApiHelperMock->saveOrFail($merchantId, $entityName, $childEntityName, $entity);
        #T2 Ends


        #T3 Starts - Reverse Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity');

        $saveApiHelperMock = $this->getMockedSaveApiHelper(['isShadowOrReverseShadowOnForOperation']);
        $saveApiHelperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $saveApiHelperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        $saveApiHelperMock->saveOrFail($merchantId, $entityName, $childEntityName, $entity);
        #T3 Ends

        #T4 Starts - Reverse Shadow on - Failure in Asv - Propagate Exception
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $saveApiAsvClientMock = $this->createSaveApiAsvClientMock();
        $saveApiAsvClientMock->expects($this->exactly(1))->method('SaveEntity')->willThrowException($exception);

        $saveApiHelperMock = $this->getMockedSaveApiHelper(['isShadowOrReverseShadowOnForOperation']);
        $saveApiHelperMock->saveApiAsvClient = $saveApiAsvClientMock;
        $saveApiHelperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        try {
            $saveApiHelperMock->saveOrFail($merchantId, $entityName, $childEntityName, $entity);
            assertTrue(false);
        } catch (IntegrationException $e) {
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }
        #T4 Ends
    }

    protected function getMockedSaveApiHelper($methods = [])
    {
        return $this->getMockBuilder(SaveApiHelper::class)
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
}
