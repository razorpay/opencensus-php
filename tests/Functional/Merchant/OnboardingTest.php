<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OnboardingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OnboardingTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testGetQuestions()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}
