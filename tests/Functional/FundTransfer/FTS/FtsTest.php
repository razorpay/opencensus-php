<?php

namespace RZP\Tests\Functional\FundTransfer\FTS;

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FtsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testFtsTransferGet()
    {
        $this->markTestSkipped('Fts mock changes pending');

        $this->ba->adminAuth();

        $request = [
            'url'             => '/fts/dashboard/fund_transfer?count=20',
            'method'          => 'get',
            'content'         => [],
        ];

        $this->makeRequestAndGetContent($request);
    }
}