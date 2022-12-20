<?php

namespace RZP\Tests\P2p;

use RZP\Models\P2p\Client;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\P2p\Service\Base\Constants;
use RZP\Models\P2p\Base\Libraries\ContextMap;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\P2p\Service\Base\Traits\DbEntityFetchTrait;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function testAdminAuthHandle()
    {
        $vpa = $this->getEntities('p2p_handle', [], true);

        $this->assertCount(9, $vpa['items']);
    }

    public function testAdminAuthUpiTransactions()
    {
        $vpa = $this->getEntities('p2p_upi_transaction', [], true);

        $this->assertCount(0, $vpa['items']);
    }

    public function testAdminP2pHandleCreate()
    {
        $handle = [
            'code'          => 'random',
            'bank'          => 'UTIB',
            'active'        => 1,
        ];

        $request = [
            'url'       => '/p2p/handles',
            'method'    => 'POST',
            'content'   => array_merge($handle, [
                'merchant_id'   => Account::TEST_ACCOUNT,
                'acquirer'      => Constants::P2P_UPI_AXIS,
            ]),
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySubset(array_merge($handle, [
            'entity'    => 'handle',
        ]), $response);

        $entity = $this->getDbHandleById('random');

        $this->assertArraySubset(array_merge($handle, [
            'merchant_id'   => Account::TEST_ACCOUNT,
            'acquirer'      => Constants::P2P_UPI_AXIS,
        ]), $entity->toArrayAdmin());
    }

    public function testAdminP2pHandleUpdate()
    {
        $entity = $this->getDbLastEntity('p2p_handle');

        $this->assertArraySubset([
            'merchant_id'   => Account::SHARED_ACCOUNT,
            'acquirer'      => Constants::P2P_UPI_SHARP,
            'bank'          => 'BRZP',
            'active'        => 1,
        ], $entity->toArrayAdmin());

        $request = [
            'url'       => '/p2p/handles/' . $entity->getCode(),
            'method'    => 'put',
            'content'   => [
                'merchant_id'   => Account::TEST_ACCOUNT,
                'acquirer'      => Constants::P2P_UPI_AXIS,
                'bank'          => 'CRZP',
                'active'        => 0,
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySubset([
            'code'      => $entity->getCode(),
            'bank'      => 'CRZP',
            'active'    => false,
        ], $response);

        $entity->refresh();

        $this->assertArraySubset([
            'merchant_id'   => Account::TEST_ACCOUNT,
            'acquirer'      => Constants::P2P_UPI_AXIS,
        ], $entity->toArrayAdmin());
    }

    public function testAdminP2pBanksBulkManage()
    {
        $bank = $this->getDbLastEntity('p2p_bank');

        $content = [
            [
                'name'          => 'Bank 1',
                'handle'        => Constants::RAZOR_AXIS,
                'gateway_data'  => [
                    'id' => 'bank_1_gateway_id',
                ],
                'upi_iin'       => '123345',
                'active'        => 1
            ],
            [
                'name'          => 'Bank 2',
                'handle'        => Constants::RAZOR_AXIS,
                'gateway_data'  => [
                    'id' => 'bank_2_gateway_id',
                ],
                'upi_iin'       => '123456',
                'active'        => 1
            ],
            [
                'handle'        => $bank->getHandle(),
                'upi_iin'       => $bank->getUpiIin(),
                'ifsc'          => 'RSRT',
                'active'        => 0
            ],
        ];

        $request = [
            'url'       => '/p2p/banks/bulk/manage',
            'method'    => 'POST',
            'content'   => $content,
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertCount(3, $response['items']);

        $banks = $this->getDbEntities('p2p_bank');

        $this->assertSame('123456', $banks->pop()->getUpiIin());
        $this->assertSame('123345', $banks->pop()->getUpiIin());

        $bank->refresh();

        $this->assertSame('RSRT', $bank->getIfsc());
    }

    public function testAdminP2pHandleUpdateWithClient()
    {
        $handle = $this->createHandle();

        $client = $this->getClientInput($handle['code']);

        // Updating the handle, Creating the client
        $updateHandle = [
            Handle\Entity::CLIENT => $client
        ];

        $handle = $this->updateHandle($handle['code'], $updateHandle);

        $client = $this->getDbLastEntity('p2p_client');
        $clientCount = $this->getDbEntities('p2p_client')->count();

        // Assert that the new client is created
        $this->assertArraySubset($this->getClientInput($handle['code']), $client->toArrayWithSecrets());

        // Now update the client
        $clientInput = $this->getClientInput($handle['code'], [
            Client\Entity::GATEWAY_DATA  => [
                'updated_key'  => 'updated_value',
            ],
        ]);

        $updateHandle = [
            Handle\Entity::CLIENT => $clientInput
        ];

        $this->updateHandle($handle['code'], $updateHandle);

        $client = $this->getDbLastEntity('p2p_client');

        $this->assertArraySubset($clientInput, $client->toArrayWithSecrets());

        // Update the client config with app_collect_link
        $clientInput = [
            Client\Entity::CLIENT_TYPE  => $clientInput[ Client\Entity::CLIENT_TYPE],
            Client\Entity::CLIENT_ID    => $clientInput[Client\Entity::CLIENT_ID],

            Client\Entity::CONFIG       => [
                'app_collect_link' => 'someCollect.link',
            ],
        ];

        $updateHandle = [
            Handle\Entity::CLIENT => $clientInput
        ];

        $this->updateHandle($handle['code'], $updateHandle);

        $clientWithLink = $this->getDbLastEntity('p2p_client');

        // Updated config will have old keys, with updated keys from $clientInput
        $expectedConfig = array_merge($client->getConfig()->toArray(), $clientInput[Client\Entity::CONFIG]);

        $this->assertEquals($expectedConfig, $clientWithLink->getConfig()->toArray());

        // Asserting other attributes, which should not be updated.
        $this->assertEquals($client->getGatewayData()->toArray(), $clientWithLink->getGatewayData()->toArray());
        $this->assertEquals($client->getSecrets()->toArray(), $clientWithLink->getSecrets()->toArray());

        // Setting property value as null
        $clientInput = [
            Client\Entity::CLIENT_TYPE  => $clientInput[ Client\Entity::CLIENT_TYPE],
            Client\Entity::CLIENT_ID    => $clientInput[Client\Entity::CLIENT_ID],

            Client\Entity::CONFIG       => [
                'app_collect_link' => null,
            ],
        ];

        $updateHandle = [
            Handle\Entity::CLIENT => $clientInput
        ];

        $this->updateHandle($handle['code'], $updateHandle);

        $clientWithLinkNull = $this->getDbLastEntity('p2p_client');

        // Updated config will have old keys, with updated keys from $clientInput
        $expectedConfig = array_merge($client->getConfig()->toArray(), $clientInput[Client\Entity::CONFIG]);

        $this->assertEquals($expectedConfig, $clientWithLinkNull->getConfig()->toArray());

        // Asserting other attributes, which should not be updated.
        $this->assertEquals($client->getGatewayData()->toArray(), $clientWithLinkNull->getGatewayData()->toArray());
        $this->assertEquals($client->getSecrets()->toArray(), $clientWithLinkNull->getSecrets()->toArray());
    }

    protected function createHandle($attributes = [])
    {
        $handle = [
            'code'          => 'random',
            'bank'          => 'UTIB',
            'active'        => 1,
            'merchant_id'   => Account::TEST_ACCOUNT,
            'acquirer'      => Constants::P2P_UPI_SHARP,
        ];

        $request = [
            'url'       => '/p2p/handles',
            'method'    => 'POST',
            'content'   => array_merge($handle, $attributes),
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function updateHandle($code, $attributes)
    {
        $request = [
            'url'       => '/p2p/handles/' . $code,
            'method'    => 'put',
            'content'   => $attributes,
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getClientInput($code, $attributes = [])
    {
        $client = [
            Client\Entity::SECRETS      => [ 'private_key' => 'data',],
            Client\Entity::CONFIG       => [ 'vpa_suffix' => 'suffix',],
            Client\Entity::GATEWAY_DATA => [ 'some' => 'data'],
            Client\Entity::HANDLE       => $code,
            Client\Entity::CLIENT_TYPE  => Client\Type::MERCHANT,
            Client\Entity::CLIENT_ID    => Account::SHARED_ACCOUNT,
        ];

        return array_merge($client, $attributes);
    }

    public function testRetrieveBanks()
    {
        $this->ba->cronAuth();

        $existingBanks = $this->getDbEntities('p2p_bank',['handle' => 'razoraxis']);

        $this->assertTrue($existingBanks->first()->isActive());

        $content = [
            'handle' => Constants::RAZOR_AXIS
        ];
        $request = [
            'url'       => '/p2p/bank/retrieve',
            'method'    => 'POST',
            'content'   => $content,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($existingBanks->first()->refresh()->isActive());

        $this->assertCount(3, $response['items']);
    }
}
