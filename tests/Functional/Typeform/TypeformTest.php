<?php
namespace RZP\Tests\Functional\Typeform;

use Request;
use ApiResponse;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TypeformTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TypeformTestData.php';

        parent::setUp();

    }

    public function testFailureTypeformWebhookConsumptionSecurity()
    {
        $this->startTest();
    }

    public function testInvalidDataTypeformWebhookConsumption()
    {
        $this->startTest();
    }

    public function testSuccessTypeformWebhookConsumptionSecurity()
    {
        $this->ba->directAuth();

        $this->startTest();
    }

}
