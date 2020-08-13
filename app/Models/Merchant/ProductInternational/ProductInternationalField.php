<?php

namespace RZP\Models\Merchant\ProductInternational;

use App;
use RZP\Exception;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Core as MerchantCore;

class ProductInternationalField
{
    protected $merchant;

    protected $validator;

    protected $app;

    protected $repo;

    public function __construct(Merchant $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->merchant = $merchant;

        $this->validator = new Validator();
    }

    /**
     * @param string $productName
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function isRequestedEnablement(string $productName): bool
    {
        $this->validator->validateProductName($productName);

        $productInternational = $this->merchant->getProductInternational();

        $status = $this->getProductStatus($productName, $productInternational);

        return ($status === ProductInternationalMapper::REQUESTED_ENABLEMENT);
    }

    /**
     * @param string $productName
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function requestEnablement(string $productName)
    {
        $this->updateProductStatus($productName, ProductInternationalMapper::REQUESTED_ENABLEMENT);
    }

    /**
     * @param string $productName
     * @param string $productInternational
     *
     * @return mixed
     */
    public function getProductStatus(string $productName, string $productInternational)
    {
        $productPosition = ProductInternationalMapper::PRODUCT_POSITION[$productName];

        return $productInternational[$productPosition];
    }


    /**
     * @param string $productName
     * @param string $status
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function updateProductStatus(string $productName, string $status)
    {
        $this->setProductStatus($productName, $status);

        $this->repo->merchant->saveOrFail($this->merchant);
    }


    /**
     * @param array  $productNameStatus
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function setMultipleProductStatus(array $productNameStatus)
    {
        //the whole operation will fail if one product's status update fails.
        foreach ($productNameStatus as $productName => $productStatus)
        {
            $this->setProductStatus($productName, $productStatus);
        }
    }

    /**
     * @param string $productName
     * @param string $status
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function setProductStatus(string $productName, string $status)
    {
        $this->validator->validateProductName($productName);

        $productInternational = $this->merchant->getProductInternational();

        $currentStatus = $this->getProductStatus($productName, $productInternational);

        //for cases where international has been disabled(only international is disabled)
        // and then has to be enabled again.
        $this->handleProductEnabling($status);

        $this->handleProductDisabling($status);

        if ($currentStatus !== $status)
        {
            $newProductInternational =
                $this->fetchUpdatedProductInternationalValue($productName, $productInternational, $status);

            $this->merchant->setProductInternational((string) $newProductInternational);
        }
    }

    /**
     * @param array  $productNames
     * @param string $status
     *
     * @return string
     * @throws Exception\BadRequestException
     */
    public function fetchProductInternationalValue(array $productNames, string $status): string
    {
        $productInternational = $this->merchant->getProductInternational();

        $productNames = ProductInternationalField::updateProductNamesThroughCategory($productNames);

        foreach ($productNames as $productName)
        {
            $productInternational =
                $this->fetchUpdatedProductInternationalValue($productName,
                                                             $productInternational,
                                                             $status);
        }

        return $productInternational;
    }

    /**
     * @param $productName
     * @param $productInternational
     * @param $status
     *
     * @return string
     * @throws Exception\BadRequestException
     */
    public function fetchUpdatedProductInternationalValue(string $productName,
                                                          string $productInternational,
                                                          string $status): string
    {
        $this->validator->validateProductName($productName);

        $productPos = ProductInternationalMapper::PRODUCT_POSITION[$productName];

        $productInternational[$productPos] = $status;

        return $productInternational;
    }


    /**
     * @param $newStatus
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     *
     * Checks if a product is being enabled, then it should satisfy the criteria of being enabled, and if international
     * isn't activated yet, then that is also taken care of.
     */
    private function handleProductEnabling($newStatus)
    {
        $merchantCore = new MerchantCore();

        if ($newStatus === ProductInternationalMapper::ENABLED)
        {
            if ($this->merchant->getInternationalAttribute() !== true)
            {
                $merchantCore->updateInternationalTypeform($this->merchant, $this->merchant->merchantDetail);
            }
            else
            {
                $merchantCore->shouldActivateProductInternational($this->merchant, $this->merchant->merchantDetail);
            }
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    private function handleProductDisabling($newStatus)
    {
        if ($this->merchant->getProductInternational() === $this->getDisabledValueForLiveProducts() and
            ($this->merchant->getInternationalAttribute() === true) and
            $newStatus === ProductInternationalMapper::DISABLED)
        {
            $merchantCore = new MerchantCore();

            $merchantCore->toggleInternational($this->merchant, false);
        }
    }

    /**
     * @param $productNames
     *
     * @return array
     *
     * checks the product names and add the rest of V2 products if one of them is present as v2
     * products are enabled and disabled at the same time.
     */
    public static function updateProductNamesThroughCategory($productNames)
    {
        $productCategoryV2 = ProductInternationalMapper::PRODUCT_CATEGORIES[ProductInternationalMapper::PROD_V2];

        $productCategoryV2Intersection = array_intersect($productNames, $productCategoryV2);

        if (empty($productCategoryV2Intersection) === false)
        {
            $productNamesMerger = array_merge($productNames, $productCategoryV2);

            $productNames = array_unique($productNamesMerger, SORT_REGULAR);
        }

        return $productNames;
    }

    /**
     * @return string
     * @throws Exception\BadRequestException
     */
    public function getEnabledValueForLiveProducts(): string
    {
        $productNames = ProductInternationalMapper::LIVE_PRODUCTS;

        $enabledValue = $this->fetchProductInternationalValue($productNames,
                                                              ProductInternationalMapper::ENABLED);

        return $enabledValue;
    }

    /**
     * @return string
     * @throws Exception\BadRequestException
     */
    public function getDisabledValueForLiveProducts(): string
    {
        $productNames = ProductInternationalMapper::LIVE_PRODUCTS;

        $disabledValue = $this->fetchProductInternationalValue($productNames,
                                                              ProductInternationalMapper::DISABLED);
        return $disabledValue;
    }
}

