<?php

namespace RZP\Tests\Functional\Governor;

use Carbon\Carbon;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class SmartRoutingProxyTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateOrUpdateGatewayDowntime()
    {
        $begin = Carbon::now()->subMinutes(60)->timestamp;
        $end   = Carbon::now()->addMinutes(60)->timestamp;

        $request = [
            'content' => [
                'id'          => 'downtime123',
                'begin'       => $begin,
                'end'         => $end,
                'gateway'     => 'axis_migs',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'source'      => 'other',
                'acquirer'    => 'axis',
                'network'     => 'VISA',
            ],
            'method' => 'POST',
            'url' => '/create_update_downtime'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['network'], 'VISA');
        $this->assertEquals($response['begin'], $begin);
        $this->assertEquals($response['end'], $end);

        $request['content']['network'] = 'MC';
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['network'], 'MC');
        $this->assertEquals($response['begin'], $begin);
        $this->assertEquals($response['end'], $end);
    }

    public function testGetGatewayDowntimeData(){

        $request = [
            'content' => [
                'id'          => 'downtime123',
                'method'      => 'card',
            ],
            'method' => 'POST',
            'url' => '/fetch_downtime'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['id'], 'downtime123');
        $this->assertEquals($response['network'],'VISA');

    }

    public function testResolveGatewayDowntimeData(){

        $request = [
            'content' => [
                'id'          => 'downtime123',
                'method'      => 'card',
            ],
            'method' => 'POST',
            'url' => '/resolve_downtime'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNull($response);

    }

    public function testRefreshDowntimeCache()
    {
        $request = [
            'content' => [
            ],
            'method' => 'POST',
            'url' => '/refresh_cache'
        ];

        $expectedResponse = ['response'=>['status_code'=>200]];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($expectedResponse['response']['status_code'],$response['response']['status_code']);

    }

}
