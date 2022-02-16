<?php

namespace Unit\Services\Pspx;

use Carbon\Carbon;
use RZP\Services\Pspx\Service;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Models\P2p\Mandate\UpiMandate\Entity as UpiMandateEntity;

class PspxMandateTest extends TestCase
{
    /**
     * @var $pspxMandate Service
     */
    protected $pspxMandate;

    protected $context;

    protected $upiEntitySkeleton = array(
        UpiMandateEntity::NETWORK_TRANSACTION_ID        => 'SeYMXtJ6YSym4A6RgRemZd03IXxcbfbKmwK',
        UpiMandateEntity::GATEWAY_TRANSACTION_ID        => 'SeYMXtJ6YSym4A6RgRemZd03IXxcbfbKmwK',
        UpiMandateEntity::GATEWAY_REFERENCE_ID          => '911416196085',
        UpiMandateEntity::RRN                           => '911416196085',
        UpiMandateEntity::REF_ID                        => '',
        UpiMandateEntity::REF_URL                       => '',
        UpiMandateEntity::MCC                           => '1234',
        UpiMandateEntity::GATEWAY_ERROR_CODE            => '00',
        UpiMandateEntity::GATEWAY_ERROR_DESCRIPTION     => 'Incoming mandate create request"'
    );

    protected $entitySkeleton = array(
        Entity::DEVICE_ID                     => 'Device00123456',
        Entity::MERCHANT_ID                   => 'Client00123456',
        Entity::CUSTOMER_ID                   => 'Customer001234',
        Entity::AMOUNT_RULE                   => 'EXACT',
        Entity::PAYER_ID                      => 'CustomerVpa001',
        Entity::PAYEE_ID                      => 'CustomerVpa002',
        Entity::TYPE                          => 'collect',
        Entity::FLOW                          => 'debit',
        Entity::MODE                          => 'default',
        Entity::RECURRING_TYPE                => 'WEEKLY',
        Entity::RECURRING_VALUE               => 2,
        Entity::RECURRING_RULE                => 'ON',
        Entity::UMN                           => '123456789012345678901234',
        Entity::STATUS                        => 'requested',
        Entity::INTERNAL_STATUS               => 'requested',
        Entity::GATEWAY                       => 'p2p_upi_axis',
        Entity::EXPIRE_AT                     => 0,
        Entity::START_DATE                    => 0,
        Entity::END_DATE                      => 0,
        Entity::ACTION                        => 'incomingMandate',
        Entity::GATEWAY_DATA                  => [],
        Entity::UPI                           => []
    );

    public function setUp(): void
    {
        parent::setUp();

        $this->pspxMandate = $this->app['pspx_mandate'];
    }

    public function testCreate()
    {
        $this->setContext();

        $input = array(
            Entity::AMOUNT              => '100',
            Entity::AMOUNT_RULE         => 'EXACT',
            Entity::RECURRING_VALUE     => 3,
            Entity::RECURRING_TYPE      => 'MONTHLY',
            Entity::RECURRING_RULE      => 'BEFORE',
            Entity::START_DATE          => Carbon::now()->getTimestamp(),
            Entity::END_DATE            => Carbon::now()->addYear()->getTimestamp(),
        );

        $expectedResult = array_merge($this->entitySkeleton, $input);

        // Because these fields are taken from context
        $expectedResult[Entity::CUSTOMER_ID] = $this->context->getDevice()->getCustomerId();
        $expectedResult[Entity::DEVICE_ID]   = $this->context->getDevice()->getId();
        $expectedResult[Entity::MERCHANT_ID]   = $this->context->getClient()->getId();

        $response = $this->pspxMandate->create($this->context, $input);

        ksort($expectedResult);
        ksort($response);

        $this->assertIsArray($response);

        // Assert response has id key, and it is not empty
        $this->assertArrayHasKey(Entity::ID, $response);
        $this->assertTrue(empty($response[Entity::ID]) === false);
        unset($response[Entity::ID]);

        // Assert response has created_at key and it's value is an integer
        $this->assertArrayHasKey(Entity::CREATED_AT, $response);
        $this->assertIsInt($response[Entity::CREATED_AT]);
        unset($response[Entity::CREATED_AT]);

        // Assert response has updated_at key and it's value is an integer
        $this->assertArrayHasKey(Entity::UPDATED_AT, $response);
        $this->assertIsInt($response[Entity::UPDATED_AT]);
        unset($response[Entity::UPDATED_AT]);

        unset($response[Entity::DELETED_AT]);

        $this->assertSame($expectedResult, $response);
    }

    public function testFetch()
    {
        $context = array();

        $response = $this->pspxMandate->fetch($context);

        $this->assertIsArray($response);
    }

    public function testUpdate()
    {
        $input = array(
            Entity::ID      => '12',
            Entity::AMOUNT  => '200'
        );

        $context = array();

        $response = $this->pspxMandate->update($context, $input);

        $this->assertIsArray($response);

        foreach ($response as $key=>$value)
        {
            if($input['id'] == $response[$key]['id'])
            {
                $this->assertSame($input['amount'], $response[$key]['amount']);
            }
        }
    }

    public function testDelete()
    {
        $this->setContext();

        $mandate = $this->pspxMandate->create($this->context, []);

        $response = $this->pspxMandate->delete($this->context, array_only($mandate, Entity::ID));

        $this->assertIsArray($response);

        $this->assertSame($mandate[Entity::ID], $response[Entity::ID]);
    }

    /**
     * Set Sharp gateway context to $context property
     *
     * @throws \RZP\Exception\P2p\BadRequestException
     */
    protected function setContext()
    {
        $context = new Context();

        $context->setHandle($this->fixtures->handle(self::DEVICE_1));

        $context->setMerchant($this->fixtures->merchant(self::DEVICE_1));

        $context->setDevice($this->fixtures->device(self::DEVICE_1));

        $context->setDeviceToken($this->fixtures->deviceToken(self::DEVICE_1));

        $context->registerServices();

        $this->context = $context;
    }
}
