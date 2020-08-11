<?php

namespace RZP\Tests\Unit\Entity;

use RZP\Models\P2p\Client\Core;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\P2p\Client\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Tests\P2p\Service\Base\Traits\DbEntityFetchTrait;

class P2pClientUnitTest extends TestCase
{
    use DbEntityFetchTrait;
    use MocksAppServices;

    public function testEntityCreate()
    {
        $this->createClient([]);

        /**
         * @var $client Entity
         */
        $client = $this->getDbLastEntity('p2p_client');

        $result = $client->toArray();

        $expectedClient = $this->getDefaultClientProperties();

        unset($expectedClient['secrets']);

        $this->assertArraySubset($expectedClient, $result);

        $this->assertArrayNotHasKey('secrets', $result);

        $clientWithSecrets = $client->toArrayWithSecrets();

        $this->assertArraySubset($this->getDefaultClientProperties(), $clientWithSecrets);
    }

    public function testEntityRelations()
    {
        $handle = $this->createHandle();

        $this->createClient();

        $savedClient = $this->getDbLastEntity('p2p_client');

        $client = $handle->merchant($savedClient->getClientId())->first();

        $handle->setClient($client);

        $expectedClient = $this->getDefaultClientProperties();

        unset($expectedClient['secrets']);

        $this->assertArraySubset($expectedClient, $handle->getClient()->toArray());

        $clientWithSecrets = $handle->getClient()->toArrayWithSecrets();

        $this->assertArraySubset($this->getDefaultClientProperties(), $clientWithSecrets);
    }

    public function testEntityLiveConnection()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        $client = $this->createClient();

        $this->assertSame('live', $client->getConnectionName());

        Entity::findOrFail($client->getId());
    }

    public function testEntityTestConnection()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $p2pClient = $this->createClient();

        $p2pClient->save();

        $this->assertSame('test', $p2pClient->getConnectionName());

        Entity::findOrFail($p2pClient->getId());
    }

    protected function createClient($attributes = [])
    {

        $input = array_merge($this->getDefaultClientProperties(), $attributes);

        $client = (new Core())->create($input);

        return $client;
    }

    protected function createHandle($attributes = [])
    {
        $handle = new Handle\Entity();

        $input = array_merge($this->getDefaultHandleProperties(), $attributes);

        $handle->forceFill($input);

        $handle->save();

        return $handle;
    }

    protected function getDefaultHandleProperties()
    {
        return [
            Handle\Entity::MERCHANT_ID => 'merchant_id',
            Handle\Entity::CODE        => 'somehandle',
            Handle\Entity::ACQUIRER    => 'p2p_upi_axis',
            Handle\Entity::BANK        => 'AXIS',
            Handle\Entity::ACTIVE      => true
        ];
    }

    protected function getDefaultClientProperties()
    {
        $secrets = [
            'public_key'  => 'some_key',
            'private_key' => 'some_private_key'
        ];

        $gatewayData = [
            'gateway_merchant_id' => 'gateway_merchant_id'
        ];

        $config = [
            'max_vpas'  => 4,
        ];

        return [
            Entity::CLIENT_ID    => 'someid',
            Entity::CLIENT_TYPE  => 'merchant',
            Entity::HANDLE       => 'somehandle',
            Entity::SECRETS      => $secrets,
            Entity::GATEWAY_DATA => $gatewayData,
            Entity::CONFIG       => $config,
        ];
    }
}
