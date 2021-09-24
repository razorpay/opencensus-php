<?php

namespace RZP\Models\Merchant\Product;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Stakeholder;
use RZP\Jobs\MerchantProductsConfig;
use RZP\Models\Merchant\Product\Util;
use RZP\Constants\Entity as EntityName;
use RZP\Models\Merchant\Product\Config;
use RZP\Models\Merchant\Product\Requirements;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Models\Merchant\Product\Request\Service as AuditService;

class Core extends Base\Core
{
    /**
     * @var Config\PaymentsGeneralConfig
     */
    private $paymentsGeneralConfig;

    /**
     * @var Config\PaymentMethods
     */
    private $paymentMethods;

    public function __construct()
    {
        parent::__construct();

        $this->paymentsGeneralConfig = new Config\PaymentsGeneralConfig();

        $this->paymentMethods        = new Config\PaymentMethods();
    }

    public function createConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input)
    {
        $response = [];

        $productName = $merchantProduct->getProduct();

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY :
            case Name::PAYMENT_LINKS:
                $response = $this->createPaymentGatewayConfig($merchant, $merchantProduct, $input);
                break;
        }

        return $response;
    }

    public function getConfig(Merchant\Entity $merchant, Entity $merchantProduct): array
    {
        $response = [];

        $productName = $merchantProduct->getProduct();

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY :
            case Name::PAYMENT_LINKS:
                $response = $this->getPaymentGatewayConfig($merchant, $merchantProduct);
                break;
        }

        return $response;
    }

    private function getPaymentGatewayConfig(Merchant\Entity $merchant, Entity $merchantProduct): array
    {
        $response = [];

        $response = array_merge($response, $this->paymentsGeneralConfig->getConfig($merchant));

        $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

        $response[Util\Constants::REQUIREMENTS] = $requirementService->fetchRequirements($merchant, $merchantProduct);

        $response[Util\Constants::PAYMENT_METHODS] = $this->paymentMethods->get($merchant);

        return $response;
    }

    private function createPaymentGeneralConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];

        $response = array_merge($response, $this->paymentsGeneralConfig->createConfig($merchant, $input));

        $merchantDetails = $merchant->merchantDetail;

        $merchantStatus = $merchantDetails->getActivationStatus();

        if (in_array($merchantStatus, Status::PAYMENT_GATEWAY_TERMINAL_STATUS) === true)
        {
            $response[Util\Constants::REQUIREMENTS] = [];

            $merchantProduct->setActivationStatus(Status::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING[$merchantStatus]);
        }
        else
        {
            $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

            $response[Util\Constants::REQUIREMENTS] = $requirementService->fetchRequirements($merchant, $merchantProduct);

            if (count($response[Util\Constants::REQUIREMENTS]) > 0)
            {
                $merchantProduct->setActivationStatus(Status::NEEDS_CLARIFICATION);
            }
        }

        $this->repo->merchant_product->saveOrFail($merchantProduct);

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        return $response;
    }

    private function createPaymentMethodsConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input)
    {
        if(array_key_exists(Util\Constants::PAYMENT_METHODS, $input) === false)
        {
            return;
        }

        $request = (new Util\PaymentMethodsRequestHandler())->handleRequest($input[Util\Constants::PAYMENT_METHODS]);

        $log = $this->audit($input, $merchantProduct->getId(),Util\Constants::REQUESTED, Util\Constants::PAYMENT_METHODS);

         MerchantProductsConfig::dispatch($this->mode, $log->getId(), $request);
    }

    public function updateConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];

        $productName = $merchantProduct->getProduct();

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY :
            case Name::PAYMENT_LINKS:
                $response = $this->updatePaymentGatewayConfig($merchant, $merchantProduct, $input);
                break;
        }

        return $response;
    }

    private function updatePaymentGatewayConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];

        if (isset($input[Util\Constants::PAYMENT_METHODS]) === true)
        {
            $paymentMethodsConfig = $input[Util\Constants::PAYMENT_METHODS];

            unset($input[Util\Constants::PAYMENT_METHODS]);

            $this->createPaymentMethodsConfig($merchant, $merchantProduct, [Util\Constants::PAYMENT_METHODS => $paymentMethodsConfig]);

            $response[Util\Constants::PAYMENT_METHODS_UPDATE] = $paymentMethodsConfig;
        }

        $response = array_merge($response, $this->paymentsGeneralConfig->updateConfig($merchant, $input));

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

        $response[Util\Constants::REQUIREMENTS] = $requirementService->fetchRequirements($merchant, $merchantProduct);

        return $response;
    }

    /**
     * This function syncs all product status with merchant activation status which are inclined with merchant activation status
     *
     * @param Detail\Entity $merchantDetails
     */
    public function syncMerchantStatusToMerchantProducts(Detail\Entity $merchantDetails)
    {
        $eventService = (new Events\Service());

        $updateProducts = [];

        try
        {
            $products = $this->repo->merchant_product->fetchMerchantProductConfigByProductNames($merchantDetails->getMerchantId(), Status::MERCHANT_STATUS_ASSOCIATED_PRODUCTS);

            foreach ($products as $product)
            {
                $this->trace->info(TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE, [
                    'merchant_id'                => $merchantDetails->getMerchantId(),
                    'merchant_product_id'        => $product->getId(),
                    'product_name'               => $product->getProduct(),
                    'merchant_activation_status' => $merchantDetails->getActivationStatus()
                ]);

                $merchantActivationStatus = $merchantDetails->getActivationStatus();

                $productStatusMapping = Status::PRODUCT_NAME_STATUS_MAPPING[$product->getProduct()];

                $productActivationStatus = $productStatusMapping[$merchantActivationStatus] ?? $product->getStatus();

                $product->setActivationStatus($productActivationStatus);

                $this->repo->merchant_product->saveOrFail($product);

                $eventService->notifyProductActivationStatus($product);

                $updateProducts[$product->getId()] = $product->getProduct();

                $this->trace->info(TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE, [
                    'merchant_id'         => $merchantDetails->getMerchantId(),
                    'merchant_product_id' => $product->getId(),
                    'success'             => true,
                ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Logger::CRITICAL,
                                         TraceCode::MERCHANT_PRODUCT_STATUS_UPDATE_FAILURE,
                                         [
                                             'merchant_id'      => $merchantDetails->getMerchantId(),
                                             'updated_products' => $updateProducts
                                         ]);
            $this->trace->count(Metric::PRODUCT_CONFIG_AUTO_UPDATE_MERCHANT_STATUS_FAILED);
        }
    }

    /**
     * Different products have different ways to alter the merchant product status once all the requirements are met.
     * The underlying entities i.e. merchant, merchant_details, stakeholder, merchant_documents are mostly common for
     * most of the products. So when any of the entities get updated, we try to calculate requirements and if the
     * requirements are 0, Further processing will be taken care by respective products
     *
     * @param Merchant\Entity $subMerchant
     * @param Detail\Entity   $merchantDetails
     *
     * @throws \RZP\Exception\LogicException
     */
    public function updateMerchantProductsIfApplicable(Merchant\Entity $subMerchant, Detail\Entity $merchantDetails)
    {
        $merchantProducts = $subMerchant->merchantProducts;

        foreach ($merchantProducts as $merchantProduct)
        {
            $productName = $merchantProduct->getProduct();

            $terminalStateReached = $this->isTerminalState($merchantProduct);

            if ($terminalStateReached === false)
            {
                $requirementService = Requirements\Factory::getInstance($productName);

                $requirements = $requirementService->getRequirements($subMerchant, $merchantDetails, $merchantProduct);

                if (count($requirements) === 0)
                {
                    $function = 'update' . studly_case($productName) . 'ProductIfApplicable';

                    $this->$function($subMerchant, $merchantDetails, $merchantProduct);
                }
            }
        }
    }

    /**
     * Payment gateway product is closely inlined with merchant activation. Hence if all the requirements are met, we
     * try to submit the L2 form.
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param Entity          $merchantProduct
     */
    private function updatePaymentGatewayProductIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Entity $merchantProduct)
    {
        $this->submitMerchantActivation($merchant, $merchantDetails, $merchantProduct);
    }

    /**
     * payment_links product is closely inlined with merchant activation. Hence if all the requirements are met, we
     * try to submit the L2 form.
     *
     * @param Merchant\Entity $merchant
     * @param Detail\Entity   $merchantDetails
     * @param Entity          $merchantProduct
     */
    private function updatePaymentLinksProductIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Entity $merchantProduct)
    {
        $this->submitMerchantActivation($merchant, $merchantDetails, $merchantProduct);
    }

    private function submitMerchantActivation(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Entity $merchantProduct)
    {
        $merchantDetailCore = new Detail\Core;

        $input = [
            EntityName::STAKEHOLDER => [
                Stakeholder\Entity::AADHAAR_LINKED => 0,
            ],
            Detail\Entity::SUBMIT => '1',
        ];

        // Two payment merchant products(payment_gateway, payment_links) can be requested parallely.
        // So form submission needs to be done only once to avoid form lock validation exception
        // For example upon 0 requirements (would be same for payment_links, payment_gateway product)
        // 1. payment_gateway - submitted the form
        // 2. payment_links - skip form submission
        if (empty($merchantDetails) === true || $merchantDetails->isLocked() === true)
        {
            return;
        }

        $submitResponse = [];

        if ($merchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            // auto submit the activation form if all requirements are met
            $submitResponse = $merchantDetailCore->saveMerchantDetails($input, $merchant);
        }
        else
        {
            $nonAcknowledgedNCFields = (new NeedsClarification\Core)->getNonAcknowledgedNCFields($merchant, $merchantDetails);

            if ($nonAcknowledgedNCFields[Merchant\Constants::COUNT] === 0)
            {
                $submitResponse = $merchantDetailCore->saveMerchantDetails($input, $merchant);
            }
        }

        $submitted = $submitResponse[Detail\Entity::SUBMITTED] ?? false;

        $this->trace->info(TraceCode::MERCHANT_SUBMITTED_POST_ZERO_PRODUCT_REQUIREMENTS, [
            'merchant_id'  => $merchant->getId(),
            'product_name' => $merchantProduct->getProduct(),
            'product_id'   => $merchantProduct->getId(),
            'submitted'    => $submitted
        ]);
    }

    private function traceContext()
    {

    }

    /**
     * This function would return true/false based on the terminal status of respective merchant product compared with
     * its current status.
     *
     * @param Entity $merchantProduct
     *
     * @return bool
     */
    private function isTerminalState(Entity $merchantProduct)
    {
        $productName = $merchantProduct->getProduct();

        $currentStatus = $merchantProduct->getStatus();

        $terminalStateReached = false;

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
                $terminalStateReached = (in_array($currentStatus, Status::PAYMENT_GATEWAY_TERMINAL_STATUS) === true);
                break;
        }

        return $terminalStateReached;
    }

    private function audit(array $input, string $merchantProductId, string $status, string $type)
    {
        return (new AuditService)->log($input, $merchantProductId, $status, $type);
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Entity $merchantProduct
     * @return array
     */
    private function createPaymentGatewayConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        if (array_key_exists(Util\Constants::PAYMENT_METHODS, $input))
        {
            unset($input[Util\Constants::PAYMENT_METHODS]);
        }

        $input = Util\PaymentGatewayRequestHandler::handleRequest($input);

        $response = $this->createPaymentGeneralConfig($merchant, $merchantProduct, $input);

        $response[Util\Constants::PAYMENT_METHODS] = $this->paymentMethods->get($merchant);

        return $response;
    }
}
