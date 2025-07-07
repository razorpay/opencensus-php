<?php

namespace Tests\Unit\Utils\RegionUtils;

use App\Session\SessionConstants;
use App\Utils\RegionUtils\RegionUtils;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Utils\RegionUtils\RegionConstants;

class RegionUtilsTest extends BaseTestCase
{
    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testGetSessionStorageType()
    {
        // Test that getSessionStorageType returns valid storage types
        $storageType = RegionUtils::getSessionStorageType();

        $this->assertContains($storageType, [
            SessionConstants::STORAGE_TYPE_REDIS,
            SessionConstants::STORAGE_TYPE_MEMORY_DB
        ]);
    }

    public function testSessionStorageConstants()
    {
        // Test that storage constants are properly defined
        $this->assertEquals('redis', SessionConstants::STORAGE_TYPE_REDIS);
        $this->assertEquals('memory', SessionConstants::STORAGE_TYPE_MEMORY_DB);
    }

    public function testRequiresSessionMigration()
    {
        // Test that requiresSessionMigration returns a boolean
        $migrationRequired = RegionUtils::requiresSessionMigration();
        $this->assertIsBool($migrationRequired);
    }

    public function testGetSessionStorageConfiguration()
    {
        // Test that getSessionStorageConfiguration returns expected structure
        $strategy = RegionUtils::getSessionStorageConfiguration();

        // Verify structure
        $this->assertIsArray($strategy);
        $this->assertArrayHasKey(SessionConstants::STRATEGY_KEY_STORAGE_TYPE, $strategy);
        $this->assertArrayHasKey(SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION, $strategy);
        $this->assertArrayHasKey(SessionConstants::STRATEGY_KEY_DECISION_REASON, $strategy);

        // Verify types
        $this->assertIsString($strategy[SessionConstants::STRATEGY_KEY_STORAGE_TYPE]);
        $this->assertIsBool($strategy[SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION]);
        $this->assertIsString($strategy[SessionConstants::STRATEGY_KEY_DECISION_REASON]);

        // Verify storage type is valid
        $this->assertContains($strategy[SessionConstants::STRATEGY_KEY_STORAGE_TYPE], [
            SessionConstants::STORAGE_TYPE_REDIS,
            SessionConstants::STORAGE_TYPE_MEMORY_DB
        ]);

        // Verify decision reason is valid
        $this->assertContains($strategy[SessionConstants::STRATEGY_KEY_DECISION_REASON], [
            SessionConstants::DECISION_REASON_CROSS_REGION_COOKIE_SET,
            SessionConstants::DECISION_REASON_CROSS_REGION_CONTEXT_WITHOUT_COOKIE,
            SessionConstants::DECISION_REASON_SAME_REGION_DEFAULT
        ]);

        // Verify consistency with individual methods
        $this->assertEquals($strategy[SessionConstants::STRATEGY_KEY_STORAGE_TYPE], RegionUtils::getSessionStorageType());
        $this->assertEquals($strategy[SessionConstants::STRATEGY_KEY_REQUIRES_MIGRATION], RegionUtils::requiresSessionMigration());
    }

    public function testIsValidMerchantRegion()
    {
        // Test valid regions
        $this->assertTrue(RegionUtils::isValidMerchantRegion(RegionConstants::REGION_US));
        $this->assertTrue(RegionUtils::isValidMerchantRegion(RegionConstants::REGION_SINGAPORE));
        $this->assertTrue(RegionUtils::isValidMerchantRegion(RegionConstants::REGION_INDIA));
        $this->assertTrue(RegionUtils::isValidMerchantRegion(RegionConstants::REGION_MALAYSIA));

        // Test case insensitive validation
        $this->assertTrue(RegionUtils::isValidMerchantRegion('in'));
        $this->assertTrue(RegionUtils::isValidMerchantRegion('my'));
        $this->assertTrue(RegionUtils::isValidMerchantRegion('sg'));
        $this->assertTrue(RegionUtils::isValidMerchantRegion('us'));

        // Test invalid region
        $this->assertFalse(RegionUtils::isValidMerchantRegion('invalid-region'));
        $this->assertFalse(RegionUtils::isValidMerchantRegion(''));
        $this->assertFalse(RegionUtils::isValidMerchantRegion('XX'));
    }

    public function testRegionConstantsMapping()
    {
        // Test that the mapping constants are properly defined
        $this->assertEquals('IN', RegionConstants::REGION_INDIA);
        $this->assertEquals('MY', RegionConstants::REGION_MALAYSIA);
        $this->assertEquals('SG', RegionConstants::REGION_SINGAPORE);
        $this->assertEquals('US', RegionConstants::REGION_US);

        // Test cell region constants
        $this->assertEquals('IN', RegionConstants::CELL_REGION_INDIA);
        $this->assertEquals('SG', RegionConstants::CELL_REGION_SINGAPORE);
        $this->assertEquals('US', RegionConstants::CELL_REGION_US);

        // Test that valid regions array includes all regions
        $this->assertContains(RegionConstants::REGION_INDIA, RegionConstants::VALID_MERCHANT_REGIONS);
        $this->assertContains(RegionConstants::REGION_MALAYSIA, RegionConstants::VALID_MERCHANT_REGIONS);
        $this->assertContains(RegionConstants::REGION_SINGAPORE, RegionConstants::VALID_MERCHANT_REGIONS);
        $this->assertContains(RegionConstants::REGION_US, RegionConstants::VALID_MERCHANT_REGIONS);
    }

    public function testGetMappedCellRegion()
    {
        // Test that IN merchants are served by IN cell
        $this->assertEquals('IN', RegionConstants::getMappedCellRegion('IN'));
        
        // Test that MY merchants are served by IN cell (this is the key requirement)
        $this->assertEquals('IN', RegionConstants::getMappedCellRegion('MY'));
        
        // Test that SG merchants are served by SG cell
        $this->assertEquals('SG', RegionConstants::getMappedCellRegion('SG'));
        
        // Test that US merchants are served by US cell
        $this->assertEquals('US', RegionConstants::getMappedCellRegion('US'));

        // Test case insensitive mapping
        $this->assertEquals('IN', RegionConstants::getMappedCellRegion('in'));
        $this->assertEquals('IN', RegionConstants::getMappedCellRegion('my'));
        $this->assertEquals('SG', RegionConstants::getMappedCellRegion('sg'));
        $this->assertEquals('US', RegionConstants::getMappedCellRegion('us'));

        // Test unknown region defaults to IN
        $this->assertEquals('IN', RegionConstants::getMappedCellRegion('UNKNOWN'));
        $this->assertEquals('IN', RegionConstants::getMappedCellRegion(''));
    }

    public function testIsMerchantRegionServedByCellRegion()
    {
        // Test IN merchants served by IN cell
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('IN', 'IN'));
        
        // Test MY merchants served by IN cell (key requirement)
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('MY', 'IN'));
        
        // Test SG merchants served by SG cell
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('SG', 'SG'));
        
        // Test US merchants served by US cell
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('US', 'US'));

        // Test negative cases
        $this->assertFalse(RegionConstants::isMerchantRegionServedByCellRegion('IN', 'SG'));
        $this->assertFalse(RegionConstants::isMerchantRegionServedByCellRegion('MY', 'SG'));
        $this->assertFalse(RegionConstants::isMerchantRegionServedByCellRegion('SG', 'IN'));
        $this->assertFalse(RegionConstants::isMerchantRegionServedByCellRegion('US', 'IN'));

        // Test case insensitive comparison
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('in', 'IN'));
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('my', 'in'));
        $this->assertTrue(RegionConstants::isMerchantRegionServedByCellRegion('MY', 'in'));
    }

    public function testIsMerchantRegionSameAsCellRegionWithMapping()
    {
        // Mock the getCellRegion method to return 'IN' for testing
        // This simulates running in an IN cell region
        $this->mockCellRegion('IN');

        // Test that IN merchants are considered same region as IN cell
        $this->assertTrue(RegionUtils::isMerchantRegionSameAsCellRegion('IN'));
        
        // Test that MY merchants are considered same region as IN cell (key requirement)
        $this->assertTrue(RegionUtils::isMerchantRegionSameAsCellRegion('MY'));
        
        // Test that SG merchants are NOT considered same region as IN cell
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('SG'));
        
        // Test that US merchants are NOT considered same region as IN cell
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('US'));

        // Test null merchant region (should return true for non-login flows)
        $this->assertTrue(RegionUtils::isMerchantRegionSameAsCellRegion(null));
    }

    public function testIsMerchantRegionSameAsCellRegionWithSGCell()
    {
        // Test with SG cell region
        $this->mockCellRegion('SG');

        // Test that SG merchants are considered same region as SG cell
        $this->assertTrue(RegionUtils::isMerchantRegionSameAsCellRegion('SG'));
        
        // Test that other regions are NOT considered same region as SG cell
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('IN'));
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('MY'));
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('US'));
    }

    public function testIsMerchantRegionSameAsCellRegionWithUSCell()
    {
        // Test with US cell region
        $this->mockCellRegion('US');

        // Test that US merchants are considered same region as US cell
        $this->assertTrue(RegionUtils::isMerchantRegionSameAsCellRegion('US'));
        
        // Test that other regions are NOT considered same region as US cell
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('IN'));
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('MY'));
        $this->assertFalse(RegionUtils::isMerchantRegionSameAsCellRegion('SG'));
    }

    public function testCrossRegionCookiePreservation()
    {
        // Test that cross-region cookie value is preserved when already set to 'true'
        // This simulates the scenario where traffic has been routed to correct region
        // but we want to maintain cross-region session storage

        // Mock a scenario where cookie is already set to 'true'
        $_COOKIE['rzp_cross_region_enabled'] = 'true';

        // Test the logic directly by checking what isCrossRegionActive returns
        // Use reflection to access the private method
        $reflection = new \ReflectionClass('App\Utils\RegionUtils\RegionUtils');
        $method = $reflection->getMethod('isCrossRegionActive');
        $method->setAccessible(true);

        // Call with 'IN' region (which matches cell region in test environment)
        // In normal circumstances, this would return false
        // But since cookie is already 'true', it should return true (preserving the value)
        $result = $method->invoke(null, 'IN');

        // Verify that the method returns true, preserving the existing 'true' cookie
        $this->assertTrue($result, 'Cross-region should be active when cookie is already set to true');

        // Test the case when cookie is not set - should follow normal logic
        unset($_COOKIE['rzp_cross_region_enabled']);
        $result = $method->invoke(null, 'IN');
        $this->assertFalse($result, 'Cross-region should be inactive when not previously set and regions match');

        // Test when cookie is set to 'false' - should follow normal logic
        $_COOKIE['rzp_cross_region_enabled'] = 'false';
        $result = $method->invoke(null, 'IN');
        $this->assertFalse($result, 'Cross-region should be inactive when explicitly set to false');

        // Clean up
        unset($_COOKIE['rzp_cross_region_enabled']);
    }

    /**
     * Helper method to mock the cell region for testing
     */
    private function mockCellRegion($region)
    {
        // Mock the config to return the specified region
        config(['app.cell_region' => $region]);
    }
}
