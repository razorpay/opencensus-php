<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Tests\TestCase;

class BusinessSubcategoryTest extends TestCase
{
    const TEST = 'Test';
    const DESCRIPTIONS = 'descriptions';

    public function testIsValidSubcategoryForInvalidSubCategories()
    {
        $this->assertFalse(BusinessSubcategory::isValidSubcategory(self::TEST));
        $this->assertFalse(BusinessSubcategory::isValidSubcategory(self::DESCRIPTIONS));
        $this->assertFalse(BusinessSubcategory::isValidSubcategory(BusinessSubCategoryMetaData::DESCRIPTION));
    }

    public function testIsValidSubcategoryForvalidSubCategories()
    {
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::MULTI_LEVEL_MARKETING));
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::TECHNICAL_SUPPORT));
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::BETTING));
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::GET_RICH_SCHEMES));
    }
}
