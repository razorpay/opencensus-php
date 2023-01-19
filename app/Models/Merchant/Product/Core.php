<?php

namespace RZP\Models\Merchant\Product;

use RZP\Constants\HyperTrace;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Stakeholder;
use RZP\Jobs\MerchantProductsConfig;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\Product\Util;
use RZP\Constants\Entity as EntityName;
use RZP\Models\Merchant\Product\Config;
use RZP\Models\Merchant\Product\Requirements;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Models\Merchant\Product\Config\PaymentMethods;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Merchant\Product\Status as ProductStatus;
use RZP\Models\Merchant\Product\Request\Service as AuditService;
use RZP\Models\Merchant\Product\BusinessUnit\Constants as BusinessUnit;
use RZP\Trace\Tracer;

class Core extends Base\Core
{
    /**
     * @var Config\PaymentsGeneralConfig
     */
    private $paymentsGeneralConfig;

    /**
     * @var Config\RouteGeneralConfig
     */
    private $routeGeneralConfig;

    /**
     * @var Config\PaymentMethods
     */
    private $paymentMethods;
    /**
     * @var TncMap\Acceptance\Service
     */
    private $tnc;

    private $tncCore;

    private $otpCore;

    public function __construct()
    {
        parent::__construct();

        $this->paymentsGeneralConfig = new Config\PaymentsGeneralConfig();

        $this->routeGeneralConfig    = new Config\RouteGeneralConfig();

        $this->paymentMethods        = new Config\PaymentMethods();

        $this->tnc                   = new TncMap\Acceptance\Service();

        $this->tncCore               = new TncMap\Acceptance\Core();

        $this->otpCore               = new Otp\Core();
    }

    public function createConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input)
    {
        $response = [];

        $productName = $merchantProduct->getProduct();

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY :
            case Name::PAYMENT_LINKS:
            {
                $response = $this->createPaymentGatewayConfig($merchant, $merchantProduct, $input);

                if((new AccountV2\Core())->isInstantActivationTagEnabled($merchant->getId()) === true)
                {
                    AutoUpdateMerchantProducts::dispatch(Status::ACCOUNT_SOURCE, $merchant->getId());
                }

                break;
            }
            case Name::ROUTE:
            {
                $response = $this->createRouteConfig($merchant, $merchantProduct, $input);

                if((new AccountV2\Core())->isInstantActivationTagEnabled($merchant->getId()) === true)
                {
                    AutoUpdateMerchantProducts::dispatch(Status::ACCOUNT_SOURCE, $merchant->getId());
                }
                break;
            }
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
            case Name::ROUTE:
                $response = $this->getRouteProductConfig($merchant, $merchantProduct);
                break;
        }

        return $response;
    }

    private function getPaymentGatewayConfig(Merchant\Entity $merchant, Entity $merchantProduct): array
    {
        $response = [];

        $response = Tracer::inspan(['name' => HyperTrace::GET_CONFIG], function () use ($response, $merchant) {

            return array_merge($response, $this->paymentsGeneralConfig->getConfig($merchant));
        });

        $response[Util\Constants::REQUIREMENTS] = Tracer::inspan(['name' => HyperTrace::FETCH_REQUIREMENTS], function () use ($merchant, $merchantProduct) {

            $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

            return $requirementService->fetchRequirements($merchant, $merchantProduct);
        });

        $response[Util\Constants::PAYMENT_METHODS] = Tracer::inspan(['name' => HyperTrace::GET_PAYMENT_METHODS], function () use ($merchant) {

            return $this->paymentMethods->get($merchant);
        });

        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCEPTED_TNC_DETAILS], function () use ($merchant, $merchantProduct, $response) {

            $hasAcceptedTnc = $this->tncCore->hasAcceptedBusinessUnitTnc($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()]);

            if ($hasAcceptedTnc === true) {
                $response[Util\Constants::TNC] = $this->tnc->fetchProductConfigTnc($merchantProduct->getProduct(), $merchant);
            }
            return $response;
        });

        $otpLog = $this->otpCore->fetchOtpVerificationLog($merchant);
        if (empty($otpLog) == false)
        {
            $response[Util\Constants::OTP] = $otpLog;
        }

        return $response;
    }

    private function getRouteProductConfig(Merchant\Entity $merchant, Entity $merchantProduct): array
    {
        $response = [];

        $response = Tracer::inspan(['name' => HyperTrace::GET_CONFIG], function () use ($response, $merchant) {

            return array_merge($response, $this->routeGeneralConfig->getConfig($merchant));
        });

        $response[Util\Constants::REQUIREMENTS] = Tracer::inspan(['name' => HyperTrace::FETCH_REQUIREMENTS], function () use ($merchant, $merchantProduct) {

            $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

            return $requirementService->fetchRequirements($merchant, $merchantProduct);
        });

        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCEPTED_TNC_DETAILS], function () use ($merchant, $merchantProduct, $response) {

            $hasAcceptedTnc = $this->tncCore->hasAcceptedBusinessUnitTnc($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()]);

            if ($hasAcceptedTnc === true)
            {
                $response[Util\Constants::TNC] = $this->tnc->fetchProductConfigTnc($merchantProduct->getProduct(), $merchant);
            }
            return $response;
        });

        return $response;
    }

    private function createPaymentGeneralConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];

        $response = Tracer::inspan(['name' => HyperTrace::CREATE_CONFIG], function () use ($response, $input, $merchant) {

            return array_merge($response, $this->paymentsGeneralConfig->createConfig($merchant, $input));
        });

        $merchantDetails = $merchant->merchantDetail;

        $merchantStatus = $merchantDetails->getActivationStatus();

        if (in_array($merchantStatus, Status::PAYMENT_GATEWAY_TERMINAL_STATUS) === true)
        {
            $response[Util\Constants::REQUIREMENTS] = [];

            $merchantProduct->setActivationStatus(Status::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING[$merchantStatus]);
        }
        else
        {
            $response[Util\Constants::REQUIREMENTS] = Tracer::inspan(['name' => HyperTrace::FETCH_REQUIREMENTS], function () use ($merchant, $merchantProduct) {

                $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

                return $requirementService->fetchRequirements($merchant, $merchantProduct);
            });

            if (count($response[Util\Constants::REQUIREMENTS]) > 0)
            {
                $merchantProduct->setActivationStatus(Status::NEEDS_CLARIFICATION);
            }
        }

        $this->repo->merchant_product->saveOrFail($merchantProduct);

        $this->trace->info(TraceCode::PAYMENTS_GENERAL_CONFIG_CREATE_RESPONSE, [
                'merchant_id'           => $merchant->getId(),
                'merchant_product'      => $merchantProduct
            ]
        );

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        return $response;
    }

    private function createRouteGeneralConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];

        $response = Tracer::inspan(['name' => HyperTrace::CREATE_CONFIG], function () use ($response, $input, $merchant) {

            return array_merge($response, $this->routeGeneralConfig->createConfig($merchant, $input));
        });

        $merchantDetails = $merchant->merchantDetail;

        $merchantStatus = $merchantDetails->getActivationStatus();

        if (in_array($merchantStatus, Status::PAYMENT_GATEWAY_TERMINAL_STATUS) === true)
        {
            $response[Util\Constants::REQUIREMENTS] = [];

            $merchantProduct->setActivationStatus(Status::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING[$merchantStatus]);
        }
        else
        {
            $response[Util\Constants::REQUIREMENTS] = Tracer::inspan(['name' => HyperTrace::FETCH_REQUIREMENTS], function () use ($merchant, $merchantProduct) {

                $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

                return $requirementService->fetchRequirements($merchant, $merchantProduct);
            });

            if (count($response[Util\Constants::REQUIREMENTS]) > 0)
            {
                $merchantProduct->setActivationStatus(Status::NEEDS_CLARIFICATION);
            }
        }

        $this->repo->merchant_product->saveOrFail($merchantProduct);

        $this->trace->info(TraceCode::ROUTE_GENERAL_CONFIG_CREATE_RESPONSE, [
                'merchant_id'           => $merchant->getId(),
                'merchant_product'      => $merchantProduct
            ]
        );

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        return $response;
    }

    private function createPaymentMethodsConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input) : array
    {
        if(array_key_exists(Util\Constants::PAYMENT_METHODS, $input) === false)
        {
            return [];
        }

        $isRazorXExperimentEnabled = \Request::all()[Util\Constants::CONFIG_UPDATE_FLOW_ENABLED] ?? false;

        $request = (new Util\PaymentMethodsRequestHandler())->handleRequest($input[Util\Constants::PAYMENT_METHODS], $isRazorXExperimentEnabled);

        $log = $this->audit($input, $merchantProduct->getId(),Util\Constants::REQUESTED, Util\Constants::PAYMENT_METHODS);

        if ($isRazorXExperimentEnabled)
        {
            if (!empty($request))
            {
                return (new PaymentMethods())->createMethod($request[0]);
            }
        }
        else
        {
            MerchantProductsConfig::dispatch($this->mode, $log->getId(), $request);
        }

        return [];
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
            case Name::ROUTE:
                $response = $this->updateRouteConfig($merchant, $merchantProduct, $input);
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

            $paymentMethodsConfigResponse = $this->createPaymentMethodsConfig($merchant, $merchantProduct, [Util\Constants::PAYMENT_METHODS => $paymentMethodsConfig]);

            if (empty($paymentMethodsConfigResponse))
            {
                $response[Util\Constants::PAYMENT_METHODS_UPDATE] = $paymentMethodsConfig;
            }
            else
            {
                $response[Util\Constants::PAYMENT_METHODS] = [$paymentMethodsConfigResponse];
            }
        }

        list($input, $response) = Tracer::inspan(['name' => HyperTrace::ACCEPT_OR_FETCH_PRODUCT_TNC], function () use ($input, $response, $merchantProduct, $merchant) {

            $hasAcceptedTnc = $this->tncCore->hasAcceptedBusinessUnitTnc($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()]);

            if (isset($input[Util\Constants::TNC_ACCEPTED]) === true)
            {
                unset($input[Util\Constants::TNC_ACCEPTED]);

                $ip = null;

                if(isset($input[Util\Constants::IP]) === true)
                {
                    $ip = $input[Util\Constants::IP];

                    unset($input[Util\Constants::IP]);
                }

                $response[Util\Constants::TNC] = $this->tnc->acceptProductConfigTnc($merchantProduct->getProduct(), $merchant, $ip);
            }
            else if ($hasAcceptedTnc === true)
            {
                $response[Util\Constants::TNC] = $this->tnc->fetchProductConfigTnc($merchantProduct->getProduct(), $merchant);

                if(isset($input[Util\Constants::IP]) === true)
                {
                    unset($input[Util\Constants::IP]);
                }
            }
            return [$input, $response];
        });

        // Store otp verification log for no doc onboarded user
        if ($this->merchant->isNoDocOnboardingEnabled() === true)
        {
            $response[Util\Constants::OTP] = $this->createOrFetchOtpVerificationLog($merchant, $input);

            AutoUpdateMerchantProducts::dispatch(ProductStatus::OTP_SOURCE, $merchant->getId());
        }

        $response = Tracer::inspan(['name' => HyperTrace::UPDATE_CONFIG], function () use ($response, $merchant, $input) {

            return array_merge($response, $this->paymentsGeneralConfig->updateConfig($merchant, $input));
        });

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        $response[Util\Constants::REQUIREMENTS] = Tracer::inspan(['name' => HyperTrace::FETCH_REQUIREMENTS], function () use ($merchant, $merchantProduct) {

            $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

            return $requirementService->fetchRequirements($merchant, $merchantProduct);
        });

        return $response;
    }

    private function updateRouteConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];


        list($input, $response) = Tracer::inspan(['name' => HyperTrace::ACCEPT_OR_FETCH_PRODUCT_TNC], function () use ($input, $response, $merchantProduct, $merchant) {

            $hasAcceptedTnc = $this->tncCore->hasAcceptedBusinessUnitTnc($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()]);

            if (isset($input[Util\Constants::TNC_ACCEPTED]) === true)
            {
                unset($input[Util\Constants::TNC_ACCEPTED]);

                $ip = null;

                if(isset($input[Util\Constants::IP]) === true)
                {
                    $ip = $input[Util\Constants::IP];

                    unset($input[Util\Constants::IP]);
                }

                $response[Util\Constants::TNC] = $this->tnc->acceptProductConfigTnc($merchantProduct->getProduct(), $merchant, $ip);
            }
            else if ($hasAcceptedTnc === true)
            {
                $response[Util\Constants::TNC] = $this->tnc->fetchProductConfigTnc($merchantProduct->getProduct(), $merchant);

                if(isset($input[Util\Constants::IP]) === true)
                {
                    unset($input[Util\Constants::IP]);
                }
            }
            return [$input, $response];
        });

        $response = Tracer::inspan(['name' => HyperTrace::UPDATE_CONFIG], function () use ($response, $merchant, $input) {

            return array_merge($response, $this->routeGeneralConfig->updateConfig($merchant, $input));
        });

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        $response[Util\Constants::REQUIREMENTS] = Tracer::inspan(['name' => HyperTrace::FETCH_REQUIREMENTS], function () use ($merchant, $merchantProduct) {

            $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

            return $requirementService->fetchRequirements($merchant, $merchantProduct);
        });

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
     * This function will be invoked only via async job.
     * Even this function is invoked from an API flow, we always need submerchant to be acting as merchant in basicAuth
     * We need submerchant context in BasicAuth since we are submitting the products (i.e. form) of a submerchant only.
     *
     * @param Merchant\Entity $subMerchant
     * @param Detail\Entity   $merchantDetails
     *
     * @throws \RZP\Exception\LogicException
     */
    public function updateMerchantProductsIfApplicable(Merchant\Entity $subMerchant, Detail\Entity $merchantDetails)
    {
        $this->app['basicauth']->setMerchant($subMerchant);

        $merchantProducts = $subMerchant->merchantProducts;

        $this->trace->info(TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE_ATTEMPT,
                           [
                               'merchant_id'            => $subMerchant->getId(),
                               'message'                => 'attempting to update all merchant products',
                               'basic_auth_merchant_id' => $this->app['basicauth']->getMerchant()->getId(),
                           ]);

        foreach ($merchantProducts as $merchantProduct)
        {
            $productName = $merchantProduct->getProduct();

            $terminalStateReached = $this->isTerminalState($merchantProduct);

            if ($terminalStateReached === false)
            {
                $requirementService = Requirements\Factory::getInstance($productName);

                if ($requirementService->isNonTerminalStatusApplicable($merchantDetails) === true)
                {
                    $this->autoUpdateNonTerminalStatus($subMerchant, $merchantDetails);
                }

                [$requirements, $optionalRequirements] = $requirementService->getRequirements($subMerchant, $merchantDetails, $merchantProduct);

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

    private function updateRouteProductIfApplicable(Merchant\Entity $merchant, Detail\Entity $merchantDetails, Entity $merchantProduct)
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

        // Three payment merchant products(payment_gateway, payment_links, route) can be requested parallely.
        // So form submission needs to be done only once to avoid form lock validation exception
        // For example upon 0 requirements (would be same for payment_links, payment_gateway product)
        // 1. payment_gateway - submitted the form
        // 2. payment_links - skip form submission
        // 3. route - skip form submission
        if (empty($merchantDetails) === true || $merchantDetails->isLocked() === true)
        {
            return;
        }

        $submitResponse = [];

        $consentDocumentsService = TncMap\ConsentDocuments\Factory::getInstance($merchantProduct->getProduct());

        if ($merchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            $consentDocumentsService->createLegalDocuments($merchant, $merchantProduct, 'L2');
            // auto submit the activation form if all requirements are met
            $submitResponse = $merchantDetailCore->saveMerchantDetails($input, $merchant);
        }
        else
        {
            $nonAcknowledgedNCFields = (new NeedsClarification\Core)->getNonAcknowledgedNCFields($merchant, $merchantDetails);

            if ($nonAcknowledgedNCFields[Merchant\Constants::COUNT] === 0)
            {
                $consentDocumentsService->createLegalDocuments($merchant, $merchantProduct, 'L2');
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

        $response = Tracer::inspan(['name' => HyperTrace::CREATE_PAYMENT_GENERAL_CONFIG], function () use ($merchant, $merchantProduct, $input) {

            return $this->createPaymentGeneralConfig($merchant, $merchantProduct, $input);
        });

        $response[Util\Constants::PAYMENT_METHODS] = Tracer::inspan(['name' => HyperTrace::GET_PAYMENT_METHODS], function () use ($merchant) {

            return $this->paymentMethods->get($merchant);
        });

        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCEPTED_TNC_DETAILS], function () use ($merchant, $merchantProduct, $response) {
            $hasAcceptedTnc = $this->tncCore->hasAcceptedBusinessUnitTnc($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()]);

            if ($hasAcceptedTnc === true) {
                $response[Util\Constants::TNC] = $this->tnc->fetchProductConfigTnc($merchantProduct->getProduct(), $merchant);
            }
            return $response;
        });

        return $response;
    }

    private function createRouteConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $input = Util\PaymentGatewayRequestHandler::handleRequest($input);

        $response = Tracer::inspan(['name' => HyperTrace::CREATE_ROUTE_CONFIG], function () use ($merchant, $merchantProduct, $input) {

            return $this->createRouteGeneralConfig($merchant, $merchantProduct, $input);
        });

        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCEPTED_TNC_DETAILS], function () use ($merchant, $merchantProduct, $response) {
            $hasAcceptedTnc = $this->tncCore->hasAcceptedBusinessUnitTnc($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$merchantProduct->getProduct()]);

            if ($hasAcceptedTnc === true) {
                $response[Util\Constants::TNC] = $this->tnc->fetchProductConfigTnc($merchantProduct->getProduct(), $merchant);
            }
            return $response;
        });
        return $response;
    }

    private function createOrFetchOtpVerificationLog(Merchant\Entity $merchant,  array & $input )
    {
        if (isset($input[Util\Constants::OTP]) === true)
        {
            $otpLog = Tracer::inspan(['name' => HyperTrace::STORE_OTP_VERIFICATION_LOG], function() use ($input, $merchant) {

                return $this->otpCore->saveOtpVerificationLog($merchant, $input);
            });
            unset($input[Util\Constants::OTP]);
            return $otpLog;
        }
        else
        {
            return  $this->otpCore->fetchOtpVerificationLog($merchant);
        }
    }

    private function preparePayload(Detail\Entity $merchantDetails)
    {
        $input = $merchantDetails->toArrayPublic();

        $requiredFields = (new Detail\ValidationFields())->getRequiredFieldsForInstantActV2Apis($merchantDetails->getBusinessType());

        $input = array_only($input, $requiredFields);

        return $input;
    }

    public function autoUpdateNonTerminalStatus(Merchant\Entity $merchant, Detail\Entity $merchantDetails)
    {
        $merchantDetails->setActivationFormMilestone(Detail\Constants::L1_SUBMISSION);

        $input = $this->preparePayload($merchantDetails);

        $merchantDetailsCore = new Detail\Core();

        $this->trace->info(TraceCode::MERCHANT_STATUS_AUTO_UPDATE_ATTEMPTED,[
            'merchant_id'                 => $merchant->getId(),
            'attempted_activation_status' => Merchant\Constants::INSTANT_ACTIVATION,
        ]);

        try
        {
            $response = $merchantDetailsCore->saveInstantActivationDetails($input, $merchant);

            $this->trace->info(TraceCode::UPDATED_SUBMERCHANT_ACTIVATION_STATUS, [
                'merchant_id' => $merchant->getId(),
                'current_activation_status' => $response[Detail\Entity::ACTIVATION_STATUS],
            ]);

            if($response[Detail\Entity::ACTIVATION_STATUS] === Merchant\Constants::INSTANT_ACTIVATION)
            {
                $this->trace->count(Metric::PRODUCT_CONFIG_AUTO_UPDATE_MERCHANT_STATUS, [
                    'updated_activation_status' => Merchant\Constants::INSTANT_ACTIVATION
                ]);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e,null,
                TraceCode::MERCHANT_STATUS_AUTO_UPDATE_ATTEMPT_FAILED
            );
        }

        return true;
    }

    public function createMerchantProduct(Merchant\Entity $merchant, array $input)
    {
        $productName = $input[Util\Constants::PRODUCT_NAME];

        $merchantProduct = $this->repo->merchant_product->fetchMerchantProductConfigByProductName($merchant->getId(), $productName);

        if (empty($merchantProduct) === false)
        {
            $this->trace->info(TraceCode::MERCHANT_PRODUCT_ALREADY_EXISTS,
                               [
                                   'merchant_id'         => $merchant->getId(),
                                   'merchant_product'    => $merchantProduct->getProduct(),
                                   'merchant_product_id' => $merchantProduct->getId()
                               ]);
        } else
        {
            $this->create($merchant, $input);
        }
    }

    public function create(Merchant\Entity $merchant, array $input) : Entity
    {
        $merchantProduct = (new Entity)->generateId();

        $merchantProduct->setActivationStatus(Status::REQUESTED);

        $merchantProduct->merchant()->associate($merchant);

        $merchantProduct->build($input);

        $this->repo->merchant_product->saveOrFail($merchantProduct);

        $this->trace->info(TraceCode::PRODUCT_CONFIGURATION_CREATE_RESPONSE,
                           [
                               'merchant_id'           => $merchant->getId(),
                               'merchant_product'      => $merchantProduct->getProduct(),
                               'merchant_product_id'   => $merchantProduct->getId()
                           ]
        );

        return $merchantProduct;
    }
}
