<?php

namespace Functional\Edge;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class EdgeAuthenticateTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->appAuth(pwd: env('APP_EDGE_SECRET'));
    }

    public function testAuthenticate()
    {
        $response = $this->sendRequest([
            'url' => 'edge/internal/authenticate',
            'method' => 'POST'
        ]);

        $response->assertOk();
        $response->assertExactJson([
            [ 'message' => 'Work in progress']
        ]);
    }


}
