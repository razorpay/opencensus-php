<?php


namespace RZP\Tests\Functional\VirtualVpaPrefix;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class VirtualVpaPrefixTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VirtualVpaPrefixTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->fixtures->create('virtual_vpa_prefix', ['merchant_id' => '100000Razorpay', 'prefix' => 'payto00000']);

        $this->ba->proxyAuth();
    }

    public function testValidatePrefix()
    {
        $this->startTest();
    }

    public function testValidatePrefixNumeric()
    {
        $this->startTest();
    }

    public function testValidatePrefixInvalidLength()
    {
        $this->startTest();
    }

    public function testValidatePrefixNotAlphanumeric()
    {
        $this->startTest();
    }

    public function testValidatePrefixDefault()
    {
        $this->startTest();
    }

    public function testValidatePrefixAlreadyExists()
    {
        $createData = $this->testData['testCreatePrefix']['request'];

        $response = $this->makeRequestAndGetContent($createData);

        $this->assertEquals('paytorzp', $response['prefix']);

        $this->startTest();
    }

    public function testCreatePrefix()
    {
        $this->startTest();

        $virtualVpaPrefix = $this->getDbLastEntity('virtual_vpa_prefix');

        $virtualVpaPrefixHistory = $this->getDbLastEntity('virtual_vpa_prefix_history');

        $virtualVpaPrefixArray = [
            'merchant_id'   => '10000000000000',
            'prefix'        => 'paytorzp',
            'terminal_id'   => 'VaVpaShrdicici',
        ];

        $virtualVpaPrefixHistoryArray = [
            'virtual_vpa_prefix_id' => $virtualVpaPrefix->getId(),
            'merchant_id'           => '10000000000000',
            'current_prefix'        => 'paytorzp',
            'previous_prefix'       => null,
            'terminal_id'           => 'VaVpaShrdicici',
            'is_active'             => 1,
            'deactivated_at'        => null,
        ];

        $this->assertArraySelectiveEquals($virtualVpaPrefixArray, $virtualVpaPrefix->toArray());

        $this->assertArraySelectiveEquals($virtualVpaPrefixHistoryArray, $virtualVpaPrefixHistory->toArray());
    }

    public function testCreatePrefixNumeric()
    {
        $this->startTest();

        $virtualVpaPrefix = $this->getDbLastEntity('virtual_vpa_prefix');

        $virtualVpaPrefixHistory = $this->getDbLastEntity('virtual_vpa_prefix_history');

        $virtualVpaPrefixArray = [
            'merchant_id'   => '10000000000000',
            'prefix'        => '12345',
            'terminal_id'   => 'VaVpaShrdicici',
        ];

        $virtualVpaPrefixHistoryArray = [
            'virtual_vpa_prefix_id' => $virtualVpaPrefix->getId(),
            'merchant_id'           => '10000000000000',
            'current_prefix'        => '12345',
            'previous_prefix'       => null,
            'terminal_id'           => 'VaVpaShrdicici',
            'is_active'             => 1,
            'deactivated_at'        => null,
        ];

        $this->assertArraySelectiveEquals($virtualVpaPrefixArray, $virtualVpaPrefix->toArray());

        $this->assertArraySelectiveEquals($virtualVpaPrefixHistoryArray, $virtualVpaPrefixHistory->toArray());
    }

    public function testCreatePrefixInvalidLength()
    {
        $this->startTest();
    }

    public function testCreatePrefixNotAlphanumeric()
    {
        $this->startTest();
    }

    public function testCreatePrefixDefault()
    {
        $this->startTest();
    }

    public function testCreatePrefixAlreadyExists()
    {
        $createData = $this->testData['testCreatePrefix']['request'];

        $response = $this->makeRequestAndGetContent($createData);

        $this->assertEquals('paytorzp', $response['prefix']);

        $this->fixtures->create('merchant', ['id' => '20000000000000']);

        $merchant = $this->getDbEntity('merchant', ['id' => '20000000000000']);

        $this->ba->setMerchant($merchant);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatePrefix()
    {
        $createData = $this->testData['testCreatePrefix']['request'];

        $response = $this->makeRequestAndGetContent($createData);

        $this->assertEquals('paytorzp', $response['prefix']);

        $virtualVpaPrefixId = $this->getDbLastEntity('virtual_vpa_prefix')->getId();

        $this->startTest();

        $virtualVpaPrefix = $this->getDbEntity('virtual_vpa_prefix', ['id' => $virtualVpaPrefixId]);

        $virtualVpaPrefixHistoryPrevious = $this->getDbEntities('virtual_vpa_prefix_history', ['virtual_vpa_prefix_id' => $virtualVpaPrefixId, 'is_active' => 0])->last();

        $virtualVpaPrefixHistoryCurrent = $this->getDbLastEntity('virtual_vpa_prefix_history');

        $virtualVpaPrefixHistoryPreviousArray = [
            'virtual_vpa_prefix_id' => $virtualVpaPrefix->getId(),
            'merchant_id'           => '10000000000000',
            'current_prefix'        => 'paytorzp',
            'previous_prefix'       => null,
            'terminal_id'           => 'VaVpaShrdicici',
            'is_active'             => 0,
        ];

        $virtualVpaPrefixHistoryCurrentArray = [
            'virtual_vpa_prefix_id' => $virtualVpaPrefix->getId(),
            'merchant_id'           => '10000000000000',
            'current_prefix'        => 'acmecorp',
            'previous_prefix'       => 'paytorzp',
            'terminal_id'           => 'VaVpaShrdicici',
            'is_active'             => 1,
            'deactivated_at'        => null,
        ];

        $this->assertEquals('acmecorp', $virtualVpaPrefix->getPrefix());

        $this->assertArraySelectiveEquals($virtualVpaPrefixHistoryPreviousArray, $virtualVpaPrefixHistoryPrevious->toArray());

        $this->assertNotNull($virtualVpaPrefixHistoryPrevious->toArray()['deactivated_at']);

        $this->assertArraySelectiveEquals($virtualVpaPrefixHistoryCurrentArray, $virtualVpaPrefixHistoryCurrent->toArray());
    }
}
