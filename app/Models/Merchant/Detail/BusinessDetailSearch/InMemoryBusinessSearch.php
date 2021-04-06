<?php

namespace RZP\Models\Merchant\Detail\BusinessDetailSearch;

use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Models\Merchant\Detail\BusinessPickerMetaData;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData as MetaData;

class InMemoryBusinessSearch extends Base
{
    protected $businessDetailsAdded;

    public function __construct(string $searchString)
    {
        parent::__construct($searchString);

        $this->businessDetailsAdded = [BusinessCategory::class => [], BusinessSubcategory::class => []];
    }

    /**
     * @return array
     */
    public function searchString(): array
    {
        if(empty($this->searchString) === true)
        {
            $this->fetchAllCategorySubCategory();
        }
        else
        {
            $this->findStringInSubCategoryScope();

            $this->findStringInMccDescriptor();

            $this->findStringInCategoryScope();
        }

        return $this->searchResponse;
    }

    private function fetchAllCategorySubCategory()
    {
        foreach (BusinessCategory::SUBCATEGORY_MAP as $category => $subCategories)
        {
            foreach ($subCategories as $subCategory)
            {
                $subCategoryDescription = BusinessSubcategory::DESCRIPTIONS[$subCategory];

                $this->createSearchResponse([], $category, $subCategory, $subCategoryDescription);
            }
        }
    }

    /**
     * Finds all the subcategories which has SearchString as a part of it.
     */
    private function findStringInSubCategoryScope()
    {
        foreach (BusinessCategory::SUBCATEGORY_MAP as $category => $subCategories)
        {
            foreach ($subCategories as $subCategory)
            {
                $subCategoryDescription = BusinessSubcategory::DESCRIPTIONS[$subCategory];

                $tags = $this->stringStartsWithSearchString($subCategoryDescription);

                if (empty($tags) === false)
                {
                    $this->createSearchResponse($tags, $category, $subCategory, $subCategoryDescription);
                }
            }
        }
    }

    /**
     * Finds all the MCC descriptors which has SearchString as a part of it.
     */
    private function findStringInMccDescriptor()
    {
        foreach (BusinessPickerMetaData::MCC_DESCRIPTOR_MAP as $subCategory => $mccDescriptors)
        {
            if (in_array($subCategory, $this->businessDetailsAdded[BusinessSubcategory::class], true) === false)
            {
                foreach ($mccDescriptors as $mccDescriptor)
                {
                    $tags = $this->stringStartsWithSearchString($mccDescriptor);

                    if (empty($tags) === false)

                    {
                        $category = BusinessCategory::getCategoryFromSubCategory($subCategory);

                        $subCategoryDescription = BusinessSubcategory::DESCRIPTIONS[$subCategory];

                        $this->createSearchResponse($tags, $category, $subCategory, $subCategoryDescription);

                        break;
                    }

                }
            }
        }
    }

    /**
     * Finds all the Categories which has SearchString as a part of it.
     */
    private function findStringInCategoryScope()
    {
        foreach (BusinessCategory::SUBCATEGORY_MAP as $category => $subcategories)
        {
            if (in_array($category, $this->businessDetailsAdded[BusinessCategory::class], true) === false)
            {
                $tags = $this->stringStartsWithSearchString($category);

                if (empty($tags) === false)
                {
                    foreach ($subcategories as $subCategory)
                    {
                        $subCategoryDescription = BusinessSubcategory::DESCRIPTIONS[$subCategory];

                        $this->createSearchResponse($tags, $category, $subCategory, $subCategoryDescription);
                    }
                }
            }
        }
    }


    /**
     * @param string $subCategory
     * @param bool   $categoryAlreadyAdded
     */
    private function updateBusinessDetailsAdded(string $subCategory, bool $categoryAlreadyAdded)
    {
        array_push($this->businessDetailsAdded[BusinessSubcategory::class], $subCategory);

        $category = BusinessCategory::getCategoryFromSubCategory($subCategory);

        if ($categoryAlreadyAdded === false)
        {
            $this->businessDetailsAdded[BusinessCategory::class][count($this->searchResponse)] = $category;
        }
    }


    /**
     * @param array  $tags
     * @param string $category
     * @param string $subCategory
     * @param string $subCategoryDescription
     */
    private function createSearchResponse(array $tags,
                                          string $category,
                                          string $subCategory,
                                          string $subCategoryDescription)
    {
        $subcategoryMetaData = MetaData::getSubCategoryMetaData($category, $subCategory);

        $match = [
            self::SUBCATEGORY_VALUE => $subCategory,
            self::SUBCATEGORY_NAME  => $subCategoryDescription,
            self::TAGS              => $tags,
            Entity::ACTIVATION_FLOW                  => $subcategoryMetaData[Entity::ACTIVATION_FLOW],
            MetaData::NON_REGISTERED_ACTIVATION_FLOW => $subcategoryMetaData[MetaData::NON_REGISTERED_ACTIVATION_FLOW]
        ];

        $responseCategoryPosition = array_search($category,
                                                 $this->businessDetailsAdded[BusinessCategory::class],
                                                 true);

        $categoryAlreadyAdded = $responseCategoryPosition !== false;

        $this->updateBusinessDetailsAdded($subCategory, $categoryAlreadyAdded);

        //
        //Category not already added
        //
        if ($categoryAlreadyAdded === false)
        {
            $this->createSearchResponseForNewCategory($category, $match);
        }

        else
        {
            if (empty($this->searchResponse[$responseCategoryPosition][self::MATCHES]))
            {
                $this->searchResponse[$responseCategoryPosition][self::MATCHES] = [];
            }

            array_push($this->searchResponse[$responseCategoryPosition][self::MATCHES], $match);
        }

    }

    /**
     * @param string $category
     * @param array  $match
     */
    private function createSearchResponseForNewCategory(string $category, array $match)
    {
        $currentResponse = [
            self::GROUP_NAME    => BusinessCategory::DESCRIPTIONS[$category],
            self::GROUP_VALUE   => $category,
            self::MATCHES       => [],
        ];

        array_push($currentResponse[self::MATCHES], $match);

        array_push($this->searchResponse, $currentResponse);
    }

}
