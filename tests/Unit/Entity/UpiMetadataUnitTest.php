<?php

namespace RZP\Tests\Unit\Entity;

use Carbon\Carbon;
use RZP\Exception;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Models\Payment\UpiMetadata\Type;
use RZP\Models\Payment\UpiMetadata\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class UpiMetadataUnitTest extends TestCase
{
    use DbEntityFetchTrait;
    use MocksAppServices;

    protected $demoPaymentId = 'fourteendigit1';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testEntityCreate()
    {
        $upiMetadata = $this->createUpiMetadata([
            Entity::TYPE        => 'otm',
            Entity::FLOW        => 'collect',
            Entity::START_TIME  => Carbon::now()->getTimestamp(),
            Entity::END_TIME    => Carbon::now()->addDays(2)->getTimestamp(),
            Entity::EXPIRY_TIME => 5,
            Entity::APP         => 'some.test.app',
            Entity::ORIGIN      => 'callback',
            Entity::FLAG        => '123',
        ]);

        $upiMetadata->forceFill([
           Entity::PAYMENT_ID => $this->demoPaymentId,
        ]);

        $upiMetadata->save();

        $lastUpiMetadata = $this->getDbLastEntity('upi_metadata');
        $this->assertSame($this->demoPaymentId, $lastUpiMetadata[Entity::PAYMENT_ID]);
        $this->assertSame('otm', $lastUpiMetadata[Entity::TYPE]);
        $this->assertSame('collect', $lastUpiMetadata[Entity::FLOW]);
        $this->assertSame('some.test.app', $lastUpiMetadata[Entity::APP]);
        $this->assertSame('callback', $lastUpiMetadata[Entity::ORIGIN]);
        $this->assertSame('123', $lastUpiMetadata[Entity::FLAG]);
    }

    public function testCreateForBaseProperties()
    {
        $upiMetadata = $this->createUpiMetadata([
            Entity::FLOW        => Flow::COLLECT,
            Entity::TYPE        => Type::OTM,
            Entity::START_TIME  => Carbon::now()->getTimestamp(),
            Entity::END_TIME    => Carbon::now()->addDays(2)->getTimestamp(),
        ]);

        $upiMetadata->forceFill([
           Entity::PAYMENT_ID => $this->demoPaymentId,
        ]);

        $upiMetadata->save();

        $lastUpiMetadata = $this->getDbLastEntity('upi_metadata');
        $this->assertSame($this->demoPaymentId, $lastUpiMetadata[Entity::PAYMENT_ID]);
        $this->assertSame('otm', $lastUpiMetadata[Entity::TYPE]);
        $this->assertSame('collect', $lastUpiMetadata[Entity::FLOW]);
    }

    public function testCreateFailed()
    {
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The flow field is required');

        (new Entity)->build([
            Entity::TYPE => Type::OTM,
        ]);
    }

    public function testEntityLiveConnection()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        $upiMetadata = $this->createUpiMetadata();

        $upiMetadata->forceFill([
            Entity::PAYMENT_ID => $this->demoPaymentId,
        ]);

        $upiMetadata->save();

        $this->assertSame('live', $upiMetadata->getConnectionName());

        Entity::findOrFail($upiMetadata->getPaymentId());
    }

    public function testEntityTestConnection()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $upiMetadata = $this->createUpiMetadata();

        $upiMetadata->forceFill([
            Entity::PAYMENT_ID => $this->demoPaymentId,
        ]);

        $upiMetadata->save();

        $this->assertSame('test', $upiMetadata->getConnectionName());

        Entity::findOrFail($upiMetadata->getPaymentId());
    }

    protected function createUpiMetadata($attributes = [])
    {
        $upiMetadata = new Entity();

        $upiMetadata->build(array_merge([
            Entity::FLOW => 'collect',
            Entity::TYPE => 'default'
        ], $attributes));

        return $upiMetadata;
    }
}
