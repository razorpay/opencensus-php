<?php

namespace RZP\Tests\Unit\Models\Merchant\Detail;

use RZP\Models\Merchant\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData;

class BusinessSubCategoryMetaDataTest extends TestCase
{

    public function testAllValidFieldsArePresentForMetaData()
    {
        $subCategoriesMetaData = BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA;

        foreach ($subCategoriesMetaData as $subCategory => $subCategoryMetaData)
        {
            $subCategoryMetaDataAttributes = array_keys($subCategoryMetaData);

            $diff = array_diff($this->getMetaDataRequiredForSubcategories(), $subCategoryMetaDataAttributes);

            $this->assertTrue(count($diff) === 0);
        }

        // for others categories subcategory will be null
        $diff = array_diff($this->getMetaDataRequiredForOthers() , array_keys(BusinessSubCategoryMetaData::getMetaDataForOthersCategory()));


        $this->assertTrue(count($diff) === 0);
    }

    private function getMetaDataRequiredForSubcategories()
    {
        return [
            BusinessSubCategoryMetaData::INTERNATIONAL_ACTIVATION,
            BusinessSubCategoryMetaData::NON_REGISTERED_ACTIVATION_FLOW,
            BusinessSubCategoryMetaData::NON_REGISTERED_MAX_PAYABLE_AMOUNT,
            BusinessSubCategoryMetaData::EMI_ACTIVATION,
            BusinessSubCategoryMetaData::DESCRIPTION,
            Entity::CATEGORY,
            Entity::CATEGORY2,
            DetailEntity::ACTIVATION_FLOW,
        ];
    }

    private function getMetaDataRequiredForOthers()
    {
        return [
            BusinessSubCategoryMetaData::INTERNATIONAL_ACTIVATION,
            BusinessSubCategoryMetaData::NON_REGISTERED_ACTIVATION_FLOW,
            BusinessSubCategoryMetaData::NON_REGISTERED_MAX_PAYABLE_AMOUNT,
            Entity::CATEGORY,
            Entity::CATEGORY2,
            DetailEntity::ACTIVATION_FLOW,
        ];
    }

}
