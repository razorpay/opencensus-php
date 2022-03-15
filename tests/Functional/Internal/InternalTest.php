<?php

namespace RZP\Test\Functional\Internal;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class InternalTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->appAuth();

        $this->app['config']->set('applications.ledger.enabled', false);
    }

    public function testInternalEntityCreate()
    {
        $requestData = [
            'request' => [
                'content' => [
                    'utr'                => '999999999',
                    'amount'             => '1',
                    'base_amount'        => '1',
                    'transaction_date'   => 1611132045,
                    'currency'           => 'INR',
                    'type'               => 'credit',
                    'merchant_id'        => 'sampleMerchant',
                    'source_entity_id'   => 'sampleEntityId',
                    'source_entity_type' => 'payout',
                    'mode'               => 'IMPS',
                    'bank_name'          => 'HDFC Bank',
                ],
                'url'     => '/internal',
                'method'  => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'        => 'sampleMerchant',
                    'status'             => 'expected',
                    'type'               => 'credit',
                    'currency'           => 'INR',
                    'amount'             => 1,
                    'base_amount'        => 1,
                    'utr'                => '999999999',
                    'source_entity_id'   => 'sampleEntityId',
                    'source_entity_type' => 'payout',
                    'mode'               => 'IMPS',
                    'bank_name'          => 'HDFC Bank',
                ],
            ],
        ];

        $this->runRequestResponseFlow($requestData);
    }

    public function testInternalEntityFail()
    {
        $defaultValues = [
            'status'           => 'expected',
            'type'             => 'credit',
        ];

        // create internal entity
        $internal = $this->fixtures->create('internal', $defaultValues);

        $requestData = [
            'request' => [
                'content' => [],
                'url'     => '/internal/'.$internal->getId().'/fail',
                'method'  => 'POST',
            ],
            'response' => [
                'content' => [
                    'id'               => $internal->getId(),
                    'merchant_id'      => $internal->getMerchantId(),
                    'status'           => 'failed',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'amount'           => 1,
                    'base_amount'      => 1,
                    'utr'              => '999999999',
                ],
            ],
        ];

        $this->runRequestResponseFlow($requestData);
    }

    public function testInternalEntityReconcile()
    {
        $defaultValues = [
            'status'           => 'expected',
            'type'             => 'credit',
        ];

        // create internal entity
        $internal = $this->fixtures->create('internal', $defaultValues);

        $requestData = [
            'request' => [
                'content' => [
                    "status"        => "received",
                ],
                'url'    => '/internal/'.$internal->getId().'/reconcile',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'id'               => $internal->getId(),
                    'merchant_id'      => $internal->getMerchantId(),
                    'status'           => 'received',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'amount'           => 1,
                    'base_amount'      => 1,
                    'utr'              => '999999999',
                ],
            ],
        ];

        $this->runRequestResponseFlow($requestData);
    }
}
