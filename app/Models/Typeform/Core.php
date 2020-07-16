<?php

namespace RZP\Models\Typeform;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\lib\DataParser;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\ProductInternational\ProductInternationalField;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;

class Core extends Base\Core
{
    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function processTypeformWebhook(array $input)
    {
        $merchant = $this->fetchMerchant($input);

        $this->app['basicauth']->setMerchant($merchant);

        $typeformParser = DataParser\Factory::getDataParserImpl(DataParser\Base::TYPEFORM, $input);

        $this->trace->info(TraceCode::TYPEFORM_RAW_DATA, ['mid' => $merchant->getId()]);

        $typeformWorkflowData = $typeformParser->parseWebhookData();

        $this->trace->info(TraceCode::TYPEFORM_PARSED_DATA,
                           ['mid'        => $merchant->getId(),
                            'parsedData' => $typeformWorkflowData]);

        $this->createInternationalWorkflow($merchant, $typeformWorkflowData, $input);

        return ['success' => true];
    }

    /**
     * @param array $input
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    private function fetchMerchant(array $input)
    {
        if ((array_key_exists('hidden', $input['form_response'])) and
            (array_key_exists('mid', $input['form_response']['hidden'])))
        {
            $merchantId = $input['form_response']['hidden']['mid'];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            return $merchant;
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT,
                null,
                ['data' => $input['event_id']]
            );
        }
    }

    /**
     * @param Merchant $merchant
     * @param array    $typeformWorkflowData
     * @param array    $input
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    private function createInternationalWorkflow(Merchant $merchant, array $typeformWorkflowData, array $input)
    {
        $productCategoriesRequested = [];
        //add a check if workflow should be created - for blacklist??
        $productInternationalField = new ProductInternationalField($merchant);
        //To be removed (post final testing)
        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['input' => $input]);

        if (key_exists('permission', $input))
        {
            $this->executeApproval($input['permission'], $merchant);
        }
        else
        {
            foreach (ProductInternationalMapper::LIVE_PRODUCTS as $productName)
            {
                if ($productInternationalField->isRequestedEnablement($productName) === true)
                {
                    $productInternationalField->setProductStatus(
                        $productName, ProductInternationalMapper::DISABLED);

                    array_push($productCategoriesRequested,
                               ProductInternationalMapper::fetchProductCategory($productName));
                }
            }

            $productCategoriesRequested = array_unique($productCategoriesRequested);

            $this->repo->merchant->saveOrFail($merchant);

            $this->createMerchantWorkflow($productCategoriesRequested, $merchant, $typeformWorkflowData);
        }
    }

    /**
     * @param array    $productCategoriesRequested
     * @param Merchant $merchant
     * @param array    $typeformWorkflowData
     */
    public function createMerchantWorkflow(array $productCategoriesRequested,
                                           Merchant $merchant, array $typeformWorkflowData)
    {
        //To be removed (post final testing)
        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['method' => 'createInternationalWorkflow']);

        foreach ($productCategoriesRequested as $index => $productCategoryRequested)
        {
            $nextWorkflowPresent = false ? ($index === count($productCategoryRequested) - 1) : true;

            $permission = ProductInternationalMapper::PRODUCT_PERMISSION[$productCategoryRequested];

            $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['permission1' => $permission]);

            $this->app['workflow']
                ->setEntityAndId($merchant->getEntity(), $merchant->getId())
                ->setPermission($permission)
                ->handle(null, $typeformWorkflowData, $nextWorkflowPresent);
        }
        //To be removed (post final testing)
        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['approval action' => 'reached to createMerchantWorkflow']);
    }


    /**
     * @param string   $permission
     * @param Merchant $merchant
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    private function executeApproval(string $permission, Merchant $merchant)
    {
        // sync between old international enabling flows with product based international flows.
        // For old international enabling flows(which are not closed before product based international goes live),
        // all the products are enabled if workflow is approved

        if (($permission === Name::EDIT_MERCHANT_INTERNATIONAL_NEW or
            $permission === Name::EDIT_MERCHANT_INTERNATIONAL) === true)
        {
            $productNames = ProductInternationalMapper::LIVE_PRODUCTS;
        }
        else
        {
            $permissionProductCategories = array_flip(ProductInternationalMapper::PRODUCT_PERMISSION);

            $permissionProductCategory = $permissionProductCategories[$permission];

            if (array_key_exists($permission, $permissionProductCategories) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_PERMISSION,
                    null,
                    ['data' => $permission]
                );
            }

            $productNames = ProductInternationalMapper::PRODUCT_CATEGORIES[$permissionProductCategory];
        }

        $this->checkWebsiteValidity($merchant);

        $this->trace->info(
            TraceCode::PRODUCT_INTERNATIONAL_APPROVED,
            ['productNames' => $productNames]);

        $productInternationalField = new ProductInternationalField($merchant);

        foreach ($productNames as $productName)
        {
            $productInternationalField->updateProductStatus($productName, ProductInternationalMapper::ENABLED);
        }

    }

    /**
     * @param Merchant $merchant
     *
     * @throws Exception\BadRequestValidationFailureException
     *
     * This check is required to avoid the situation when a workflow is executed and website of the Merchant is not
     * valid, the workflow gets closed without activating international as website validation is base requirement
     * for international enabling.
     *
     */
    private function checkWebsiteValidity(Merchant $merchant)
    {
        $merchantCore = new MerchantCore();

        $websiteValid =
            $merchantCore->validateWebsiteCheckForInternationalActivation($merchant, $merchant->merchantDetail);

        if ($websiteValid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Workflow can\'t be approved as Merchant Website is not Valid'
            );
        }

    }
}
