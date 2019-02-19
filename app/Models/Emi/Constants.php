<?php

namespace RZP\Models\Emi;

use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData;

class Constants
{
    public static $blackListedMcc = [
        "4214",
        "4789",
        "4899",
        "4900",
        "5094",
        "5099",
        "5122",
        "5169",
        "5261",
        "5499",
        "5531",
        "5651",
        "5691",
        "5732",
        "5813",
        "5912",
        "5932",
        "5944",
        "5967",
        "5971",
        "5993",
        "5999",
        "6010",
        "6011",
        "6012",
        "6012",
        "6050",
        "6051",
        "6211",
        "6300",
        "6300",
        "6513",
        "6529",
        "6530",
        "7393",
        "7394",
        "7399",
        "7631",
        "7801",
        "7802",
        "7993",
        "7995",
        "7999",
        "8661",
        "9399",
    ];

    /**
     * returns if category or sub-category can have emi enabled
     * used in L1 Activation form
     * @param string $category
     * @param string|null $subcategory
     *
     * @return bool
     */
    public static function isCategoryOrSubcategoryBlacklisted(string $category, string $subCategory = null): bool
    {
        if ($category === BusinessCategory::OTHERS)
        {
            return true;
        }

        $subCategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subCategory);

        if (isset($subCategoryMetaData[BusinessSubCategoryMetaData::EMI_ACTIVATION]) === true)
        {
            return $subCategoryMetaData[BusinessSubCategoryMetaData::EMI_ACTIVATION] === ActivationFlow::BLACKLIST;
        }
        else
        {
            return false;
        }
    }
}
