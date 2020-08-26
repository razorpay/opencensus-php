<?php

namespace RZP\Tests\Unit\P2p\Entity;

use RZP\Models\Merchant\Account;
use RZP\Models\P2p\Client\Core;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\P2p\Client\Type;
use RZP\Models\P2p\Client\Entity;
use RZP\Models\P2p\Client\Config;
use RZP\Models\P2p\Client\Secrets;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Tests\P2p\Service\Base\Traits\DbEntityFetchTrait;

class ClientUnitTest extends TestCase
{
    use DbEntityFetchTrait;
    use MocksAppServices;

    const GATEWAY_MERCHANT_ID = 'gateway_merchant_id';
    const GATEWAY_VERSION     = 'gateway_version';

    public function testEntityCreate()
    {
        $this->createDefaultClient();
        /**
         * @var $client Entity
         */
        $client = $this->getDbLastEntity('p2p_client');

        $result = $client->toArray();

        $expectedClient = $this->getClientProperties();

        unset($expectedClient['secrets']);

        $this->assertArraySubset($expectedClient, $result);

        $this->assertArrayNotHasKey('secrets', $result);

        $clientWithSecrets = $client->toArrayWithSecrets();

        $this->assertArraySubset($this->getClientProperties(), $clientWithSecrets);
    }

    public function testEntityRelations()
    {
        $client = $this->createDefaultClient();

        $handle = $this->getDbHandleById($client->getHandle());

        $savedClient = $this->getDbLastEntity('p2p_client');

        $client = $handle->client(Type::MERCHANT, $savedClient->getClientId());

        $handle->setClient($client);

        $expectedClient = $this->getClientProperties();

        unset($expectedClient['secrets']);

        $this->assertArraySubset($expectedClient, $handle->getClient()->toArray());

        $clientWithSecrets = $handle->getClient()->toArrayWithSecrets();

        $this->assertArraySubset($this->getClientProperties(), $clientWithSecrets);
    }

    public function testClientArrayProperties()
    {
        $this->createDefaultClient();

        /**
         * @var $client Entity
         */
        $client = $this->getDbLastClient();

        /**** Assert basic properties *****/
        $this->assertArraySubset($this->getClientPropertiesWithoutSecret(), $client->toArray());
        $this->assertArraySubset($this->getClientProperties(), $client->toArrayWithSecrets());

        /**** Assert ArrayProperties Getters ****/
        // Gateway Data
        $gatewayData = $client->getGatewayData();
        $this->assertSame('gateway_merchant_id', $gatewayData->get(self::GATEWAY_MERCHANT_ID));
        $this->assertSame('v1', $gatewayData->get(self::GATEWAY_VERSION));

        // Secrets
        $secrets     = $client->getSecrets();
        $this->assertNotSame('some_private_key', $secrets->get(Secrets::PRIVATE_KEY));
        $this->assertSame('some_private_key', $secrets->decrypted(Secrets::PRIVATE_KEY));
        $this->assertNull($secrets->decrypted('NON_EXISTENT_KEY'));

        // Config
        $config      = $client->getConfig();
        $this->assertSame('suf', $config->get(Config::VPA_SUFFIX));
    }

    public function testClientWithEmptyGatewayData()
    {
        $client =  $this->createDefaultClient([
            Entity::GATEWAY_DATA => [],
        ]);

        $this->assertNull($client->getGatewayData()->get('some_key'));

        $data = $this->getClientPropertiesWithoutSecret();

        $data[Entity::GATEWAY_DATA] = [];

        $this->assertArraySubset($data, $client->toArray());
    }

    public function testClientWithEmptySecrets()
    {
        $client = $this->createDefaultClient([
           Entity::SECRETS => []
        ]);

        $this->assertNull($client->getSecrets()->get(Secrets::PRIVATE_KEY));

        $data = $this->getClientProperties();

        $data[Entity::SECRETS] = [];

        $this->assertArraySubset($data, $client->toArrayWithSecrets());
    }

    public function testEntityLiveConnection()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        $handle = $this->getDbLastEntity('p2p_handle');

        $client = $this->createClient($handle);

        $this->assertSame('live', $client->getConnectionName());

        Entity::findOrFail($client->getId());
    }

    public function testEntityTestConnection()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $handle = $this->getDbLastEntity('p2p_handle');

        $client = $this->createClient($handle);

        $client->save();

        $this->assertSame('test', $client->getConnectionName());

        Entity::findOrFail($client->getId());
    }

    protected function createDefaultClient($attributes = [])
    {
        $handle = $this->createHandle('handle');

        $client = $this->createClient($handle, $attributes);

        return $client;
    }

    protected function createHandle($code)
    {
        $handle = (new Handle\Core())->add($this->getDefaultHandleProperties());

        return $handle;
    }

    protected function createClient(Handle\Entity $handle, $attributes = [])
    {
        $input = array_merge($this->getClientProperties(), $attributes);

        $input['handle'] = $handle->getCode();

        $handle = $this->getDbLastEntity('p2p_handle');

        $client = (new Core())->create($handle, $input);

        return $client;
    }

    protected function getDefaultHandleProperties()
    {
        return [
            Handle\Entity::MERCHANT_ID => Account::SHARED_ACCOUNT,
            Handle\Entity::CODE        => 'handle',
            Handle\Entity::ACQUIRER    => 'p2p_upi_axis',
            Handle\Entity::BANK        => 'AXIS',
            Handle\Entity::ACTIVE      => true
        ];
    }

    protected function getClientPropertiesWithoutSecret()
    {
        $properties = $this->getClientProperties();

        unset($properties['secrets']);

        return $properties;
    }

    protected function getClientProperties()
    {
        $secrets = [
            'private_key' => 'some_private_key'
        ];

        $gatewayData = [
            self::GATEWAY_MERCHANT_ID  => 'gateway_merchant_id',
            self::GATEWAY_VERSION      => 'v1'
        ];

        $config = [
            Config::VPA_SUFFIX  => 'suf',
        ];

        return [
            Entity::CLIENT_ID    => 'someid',
            Entity::CLIENT_TYPE  => 'merchant',
            Entity::HANDLE       => 'handle',
            Entity::SECRETS      => $secrets,
            Entity::GATEWAY_DATA => $gatewayData,
            Entity::CONFIG       => $config,
        ];
    }
}
