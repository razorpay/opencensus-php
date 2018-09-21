<?php
/**
 * Created by PhpStorm.
 * User: pankajkumar
 * Date: 21/09/18
 * Time: 5:09 PM
 */

namespace RZP\Models\Merchant\Detail;



use RZP\Tests\TestCase;

class BusinessSubcategoryTest extends TestCase
{

    public function testIsValidSubcategoryForInvalidSubCategories()
    {
        $this->assertFalse(BusinessSubcategory::isValidSubcategory("test"));
        $this->assertFalse(BusinessSubcategory::isValidSubcategory("descriptions"));
        $this->assertFalse(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::DESCRIPTION));
        $this->assertFalse(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::MCC_CODE));
    }

    public function testIsValidSubcategoryForvalidSubCategories()
    {
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::MULTI_LEVEL_MARKETING));
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::TECHNICAL_SUPPORT));
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::BETTING));
        $this->assertTrue(BusinessSubcategory::isValidSubcategory(BusinessSubcategory::GET_RICH_SCHEMES));
    }
}
