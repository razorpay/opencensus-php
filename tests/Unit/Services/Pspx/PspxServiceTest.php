<?php

namespace Unit\Services\Pspx;

use RZP\Tests\TestCase;
use RZP\Services\Pspx\Service;

class PspxServiceTest extends TestCase
{
    /**
     * @var $pspx Service
     */
    protected $pspx;

    public function setUp(): void
    {
        parent::setUp();

        $this->pspx = $this->app['pspx'];
    }

    /**
     * Tests the mock service for test cases.
     * Ping route is available in PSPx as dummy route.
     */
    public function testPing()
    {
        $response = $this->pspx->ping();

        $this->assertArrayHasKey('message', $response);
        $this->assertSame('pong', $response['message']);
    }
}
