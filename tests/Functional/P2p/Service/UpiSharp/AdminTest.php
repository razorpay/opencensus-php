<?php

namespace RZP\Tests\P2p\Service\UpiSharp;

use RZP\Tests\P2p\Service\Base\Constants;
use RZP\Models\P2p\Base\Libraries\ContextMap;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\P2p\Service\Base\Scenario as S;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\DbEntityFetchTrait;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;


    public function retrieveBanks()
    {
        // Scenarios, Sub Scenarios, Count based on scenarios, items base on scenarios
        $cases = [];

        $cases['scenario#BB101'] = [[S::BB101]];

        $cases['scenario#BB102'] = [[S::BB102]];

        $items = [
            [
                $this->expectedBank( '1010'),
                $this->expectedBank( '1020'),
                $this->expectedBank( '1030'),
                $this->expectedBank( '1040'),
            ],
            [
                $this->expectedBank( '1010'),
                $this->expectedBank( '1020'),
                $this->expectedBank( '1030'),
            ]
        ];
        // First 4 banks and then only 3, testing that 3 are updated and 1 is disabled successfully
        $cases['scenario#BB103#004#003'] = [[S::BB103, S::BB103], ['004', '003'], [4, 3], json_encode($items)];

        $items = [
            [
                $this->expectedBank( '1010'),
                $this->expectedBank( '1020'),
            ],
            [
                $this->expectedBank( '1010'),
                $this->expectedBank( '1020'),
                $this->expectedBank( '1030'),
            ]
        ];
        // First 2 banks and then 3, testing that 2 are updated and 1 is added successfully
        $cases['scenario#BB103#002#003'] = [[S::BB103, S::BB103], ['002', '003'], [2, 3], json_encode($items)];

        return $cases;
    }

    /**
     * @dataProvider retrieveBanks
     */
    public function testRetrieveBanks($scenarios, $subs = ['000'], $counts = [], $json = '[]')
    {
        $helper = $this->getBankAccountHelper();

        $helper->setScenarioInContext($scenarios[0], $subs[0]);

        $this->ba->cronAuth();

        $request = [
            'url'       => '/p2p/bank/retrieve',
            'method'    => 'POST',
            'content'   => [
                'handle'        => Constants::RAZOR_SHARP,
                'request_id'    => $helper->getScenarioInContext()->toRequestId(),
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $items = json_decode($json, true);

        $this->assertCollection($response, $counts[0], $items[0]);

        $helper->setScenarioInContext($scenarios[1], $subs[1]);

        $request['content']['request_id'] = $helper->getScenarioInContext()->toRequestId();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertCollection($response, $counts[1], $items[1]);
    }


    private function expectedBank($masked = '1010')
    {
        return [
            'entity'                => 'bank',
            'name'                  => 'Sharp bank'.$masked,
            'active'                => true
        ];
    }
}
