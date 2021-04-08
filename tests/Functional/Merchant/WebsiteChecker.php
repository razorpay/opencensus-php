<?php


namespace Functional\Merchant;


use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class WebsiteChecker extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WebsiteCheckerTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testLive()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testNotLive()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testManualReview()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }
}
