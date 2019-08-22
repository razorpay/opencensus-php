<?php

namespace RZP\Tests\P2p;

use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\P2p\Service\Base\Constants;
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

        $this->assertCount(6, $vpa['items']);
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
}
