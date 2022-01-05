<?php

namespace RZP\Tests\Functional\Merchant\Tnc;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Schedule\Period;
use RZP\Models\Merchant\Product\TncMap\Entity as Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TncMapTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use OAuthTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/TncMapTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateTnC()
    {
        $this->startTest();
    }

    public function testUpdateTnC()
    {
        $testData = $this->testData['testCreateTnC'];

        $tnc = $this->runRequestResponseFlow($testData);

        $tncId = $tnc[Entity::ID];

        $testData = $this->testData['testUpdateTnC'];

        $testData['request']['url'] = '/products/tnc/'. $tncId;

        $tnc = $this->runRequestResponseFlow($testData);
    }

    public function testUpdateTnCWrongBU()
    {
        $testData = $this->testData['testCreateTnC'];

        $tnc = $this->runRequestResponseFlow($testData);

        $tncId = $tnc[Entity::ID];

        $testData = $this->testData['testUpdateTnCWrongBU'];

        $testData['request']['url'] = '/products/tnc/'. $tncId;

        $tnc = $this->runRequestResponseFlow($testData);
    }

    public function testGetTncById()
    {
        $testData = $this->testData['testCreateTnC'];

        $tnc = $this->runRequestResponseFlow($testData);

        $tncId = $tnc[Entity::ID];

        $testData = $this->testData['testGetTncById'];

        $testData['request']['url'] = '/products/tnc/'. $tncId;

        $tnc = $this->runRequestResponseFlow($testData);
    }
}
