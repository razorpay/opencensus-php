<?php

namespace RZP\Models\Merchant\ShippingInfo;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Country;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;
use RZP\Models\Merchant\OneClickCheckout\Shopify\StateMap;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
use RZP\Models\Order;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;

// For evaluation of shipping options using shipping + cod engine and API decomp.
class MagicCheckoutProvider extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    // shipping engine returns the response in the same structure as what FE expects to make decomp easier.
    public function fetchRatesFromShippingEngine(
        $order,
        array $orderMetaArray,
        array $address,
        string $platform,
        array $dimensions,
    ): array
    {
        $taxDetails = null;
        $isDigitalProduct = false;
        // Tax details exist only for Shopify merchants for now.
        // Shipping engine does not perform any tax evaluation.
        // Tax will always be set as null from MCS so we override it, depending on the experiment.
        if ($platform === 'shopify')
        {
            $checkoutId = $order->toArrayPublic()['notes']['storefront_id'];

            // This fetaure flag is required to resolve the issue where Shopify storefront API is giving the
            // tax information as exclusive, even though the merchant store is configured as tax inclusive.
            // So using this flag we will be able to override tax_included field as inclusive and fetch the
            // right tax amount from calculate draft order API.
            if ($this->merchant->isFeatureEnabled(FeatureConstants::ONE_CC_TAX_INCLUSION) === true)
            {
                $checkoutResponse = (new Shopify\Service)->getTaxDetailsAndIfProductIsDigitalFromDraftOrder($order, $orderMetaArray, $address);
            }
            else
            {
                $checkoutResponse = (new Shopify\Service)->getTaxDetailsAndIfProductIsDigitalFromCheckout($checkoutId, $address);
            }
            $isDigitalProduct = $checkoutResponse['is_digital_product'];
            $isTaxExpEnabled = (new CommonUtils())->isTaxesExpEnabled();
            if ($isTaxExpEnabled)
            {
                $taxDetails = $checkoutResponse['tax_details'];
            }
            // evaluate_rates is false if any error is received by Shopify. If the error thrown is of type 'virtual_product_found'
            // it means all products are virtual so we must allow serviceability with fee as Re 0.
            // In other cases a non-recoverable failure has occured so we block shipping.
            // In such cases 'is digital_product' will be false.
            if (!$checkoutResponse['evaluate_rates'])
            {
                return $this->responseIfShopifyErrors($address, $checkoutResponse, $taxDetails);
            }
        }
        // TODO: Remove digital products from the evaluation in shipping engine?
        // It could mess with the item category based queries.
        $payload = $this->shippingEnginePayload($order, $address, $platform);
        try
        {
            $shippingOptions = (new MagicCheckoutService\Service())->getShippingOptions($payload);
            if ($isDigitalProduct)
            {
                $shippingOptions = $this->disableCodForShippingOptions($shippingOptions);
            }
            $shippingMethods = $shippingOptions['addresses'][0]['shipping_methods'];
            $serviceable = false;
            $cod = false;
            $shippingFee = 0;
            if (count($shippingMethods) > 0)
            {
                $serviceable = $shippingMethods[0]['serviceable'];
                $cod = $shippingMethods[0]['cod'];
                $shippingFee = $shippingMethods[0]['shipping_fee'];
            }
            // For 0 or 1 shipping method we need to remove the array due to how the FE prioritizes reading fields.
            if ($platform !== 'woocommerce' && count($shippingMethods) < 2)
            {
                unset($shippingOptions['addresses'][0]['shipping_methods']);
            }
            return array_merge(
                $shippingOptions['addresses'][0],
                [
                    'serviceable'        => $serviceable,
                    'cod'                => $cod,
                    'shipping_fee'       => $shippingFee,
                    'cod_fee'            => null,
                    'tax_details'        => $taxDetails,
                    'is_digital_product' => $isDigitalProduct
                ]
            );
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::MAGIC_SHIPPING_ENGINE_API_ERROR,
                [
                    'error'   => $e->getMessage(),
                    'address' => $address,
                    'payload' => $payload,
                ]
            );
            return $this->errorResponse($address, $taxDetails);
        }
    }

    public function shouldUseShippingEngine(): bool
    {
        $configs = $this->getShippingEngineConfigs();
        return ($configs[Merchant1ccConfig\Type::SHIPPING_ENGINE] ?? '0') === '1';
    }

    protected function getShippingEngineConfigs(): array
    {
        $configs = $this->repo->merchant_1cc_configs->findByMerchantAndConfigArray(
            $this->merchant->getId(),
            [Merchant1ccConfig\Type::SHIPPING_ENGINE]
        );
        $shippingConfigs = [];
        foreach ($configs as $config)
        {
            $shippingConfigs[$config->getConfig()] = $config->getValue();
        }
        return $shippingConfigs;
    }

    // applyCodEngineRulesIfApplicable checks if merchant has opted for using cod engine.
    // It makes a call to Magic Checkout service and overrides cod values based on
    // configurations set by the merchant using Rzp dashboard. The updated address is returned.
    public function applyCodEngineRulesIfApplicable(
        $order,
        array $orderMetaArray,
        array $customerInfo,
        array $address,
        array $dimensions): array
    {
        $codEngineConfigs = $this->getCodEngineConfigs();
        $shippingProvider = $dimensions['shipping_provider'];
        // default values in case of failures.
        $isCodEligible = false;
        $codFee = 0;
        $shouldUseCodEngine = $this->shouldUseCodEngine($codEngineConfigs);
        // In rare cases we have seen cod engine as enabled but cod_engine_type as not saved so adding a double check.
        $codEngineType = $codEngineConfigs[Merchant1ccConfig\Type::COD_ENGINE_TYPE];
        if (!$shouldUseCodEngine || empty($codEngineType))
        {
            // In case a merchant configures shipping engine but not cod engine (which is a
            // pre-requisite), we must disable cod or the fee may default to Re 0.
            if ($shippingProvider === Merchant1ccConfig\Type::SHIPPING_ENGINE)
            {
                return $this->forceDisableCodForAddress($address);
            }
            return $address;
        }
        $payload = $this->codEnginePayload($order, $orderMetaArray, $customerInfo, $address, $codEngineConfigs[Merchant1ccConfig\Type::COD_ENGINE_TYPE]);

        try
        {
            $res = $this->app['magic_checkout_cod_engine_service']->evaluate($payload);
            $isCodEligible = $res['cod'];
            $codFee = $res['cod_fee'];
        }
        catch (\Exception $ex)
        {
            $this->trace->count(
                Metric::MAGIC_COD_ENGINE_EVALUATE_API_ERROR_COUNT,
                array_merge($dimensions, ['code' => $ex->getCode()])
            );
            $this->trace->error(TraceCode::MAGIC_COD_ENGINE_EVALUATE_CALL_ERROR,
                [
                    'code'    => $ex->getCode(),
                    'message' => $ex->getMessage(),
                ]
            );
            return $address;
        }
        $this->trace->info(
            TraceCode::MAGIC_COD_ENGINE_EVALUATE_CALL_SUCCESS,
            ['response' => $res, 'shipping_provider' => $shippingProvider]
        );

        // In case of shipping engine, we do not want to force override cod eligibility settings.
        // We override the cod values only if cod is marked as true for the shipping method.
        if ($shippingProvider === Merchant1ccConfig\Type::SHIPPING_ENGINE)
        {
            // Even for a single shipping method, shipping engine will return it in an array form.
            // So at least one value will always exist
            if ($address['cod'])
            {
                $address['cod'] = $isCodEligible;
                $address['cod_fee'] = $codFee;
            }
            if (empty($address['shipping_methods']))
            {
                return $address;
            }
            foreach ($address['shipping_methods'] as &$method)
            {
                if ($method['cod'] == true)
                {
                    $method['cod'] = $isCodEligible;
                    $method['cod_fee'] = $codFee;
                    // Top level fields are being set for compatibility with single shipping method ft.
                    // In these scenarios, FE will depend on the top level field but pick the display text
                    // from within shipping methods. Support will be extended till FE migrates their logic.
                    $address['cod'] = $isCodEligible;
                    $address['cod_fee'] = $codFee;
                }
            }
            return $address;
        }

        $address['cod'] = $isCodEligible;
        $address['cod_fee'] = $codFee;
        // set cod fee for all shipping methods to support multiple shipping if feature flag is enabled.
        // NOTE(imp check): If a Shopify merchant opts for shipping engine, multiple shipping methods is auto enabled.
        // Below check is executed only if Shopify is chosen as the shipping provider. shipping_methods count
        // is required for merchants using shipping engine but have the ONE_CC_SHOPIFY_MULTIPLE_SHIPPING still enabled.
        if ($this->merchant->isFeatureEnabled(FeatureConstants::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING) && count($address['shipping_methods']) > 0)
        {
            foreach ($address['shipping_methods'] as &$method)
            {
                $method['cod'] = $isCodEligible;
                $method['cod_fee'] = $codFee;
            }
        }
        return $address;
    }

    protected function shippingEnginePayload($order, array $address, string $platform): array
    {
        $orderId = $order->getPublicId();
        $address = $this->getShippingAddress($address);
        return [
            'order_id'  => $orderId,
            'addresses' => [$address],
            'platform'  => $platform,
        ];
    }

    protected function codEnginePayload($order, array $orderMetaArray, array $customerInfo, array $address, string $type): array
    {
        $location = $this->getShippingAddress($address);
        $customerMetaInfo = $this->getCustomerDetails($orderMetaArray);

        if(empty($customerMetaInfo['phone']) === true || empty($customerMetaInfo['email']) === true)
        {
            $customerMetaInfo = array_merge($customerMetaInfo, $customerInfo);
        }

        $rzpOrderId = $order->getPublicId();
        $orderAmount = $orderMetaArray['line_items_total'];
        $orderAmountInRupee = $orderAmount/pow(10,2);
        $roundOrderAmount = round($orderAmountInRupee)*100;
        $products = [];
        foreach ($orderMetaArray['line_items'] as $lineItems)
        {
            $product = ['id' => $lineItems['product_id']];
            array_push($products, $product);
        }
        $inputOrder = [
            'id'       => $rzpOrderId,
            'amount'   => $roundOrderAmount,
            'products' => $products,
        ];
        return [
            'merchant_id'   => $this->merchant->getMerchantId(),
            'type'          => $type,
            'order'         => $inputOrder,
            'location'      => $location,
            'customer_info' => $customerMetaInfo,
        ];
    }

    // cod and shipping engine uses shopify locations codes. We override Google location with shopify.
    // TODO: See international location mapping for shipping engine.
    protected function getShippingAddress(array $address): array
    {
        $stateCode = $stateCodeFromName = (new StateMap)->getPincodeMappedStateCode($address['zipcode']);
        if ($stateCode === null)
        {
            $stateCode = (new StateMap)->getShopifyStateCode($address);
            $stateCodeFromName = (new StateMap)->getShopifyStateCodeFromName($address);
        }
        return [
            'zipcode'      => $address['zipcode'],
            'state_code'   => strtoupper($stateCode ?? $stateCodeFromName),
            'country_code' => strtoupper($address['country'])
        ];
    }

    protected function getCustomerDetails(array $orderMeta): array
    {
        $customerDetails = $orderMeta['customer_details'];
        return [
            'email' => $customerDetails['email'],
            'phone' => $customerDetails['contact'],
            'ip'    => $this->app['request']->ip(),
        ];
    }

    protected function getCodEngineConfigs(): array
    {
        $configs = $this->repo->merchant_1cc_configs->findByMerchantAndConfigArray(
            $this->merchant->getId(),
            [Merchant1ccConfig\Type::COD_ENGINE, Merchant1ccConfig\Type::COD_ENGINE_TYPE]
        );
        $codEngineConfigs = array();
        foreach ($configs as $config)
        {
            $codEngineConfigs[$config->getConfig()] = $config->getValue();
        }
        return $codEngineConfigs;
    }

    // Enables routing of shipping options API to Magic Checkout microservice as part of API decomp.
    // This is currently a placeolder function and will made be public during implementation.
    protected function shouldRouteToMagicCheckoutSvc(string $merchantId): bool
    {
        return false;
    }

    protected function shouldUseCodEngine(array $codEngineConfigs): bool
    {
        return ($codEngineConfigs[Merchant1ccConfig\Type::COD_ENGINE] ?? '0') === '1';
    }

    protected function responseIfShopifyErrors(array $address, array $checkoutResponse, array $taxDetails): array
    {
        $result = [
            'id'		         => 0,
            'zipcode'            => $address['zipcode'],
            'state_code'         => $address['state_code'],
            'country'            => $address['country'],
            'serviceable'        => $checkoutResponse['is_digital_product'],
            'cod'                => false,
            'shipping_fee'       => 0,
            'cod_fee'            => null,
            'tax_details'        => $taxDetails,
            'is_digital_product' => $checkoutResponse['is_digital_product']
        ];
        return $result;
    }

    // only 1 object will exist in $addresses but we are iterating for future proof solution.
    // cod_fee is kept as null since it gets overridden by cod engine.
    protected function disableCodForShippingOptions(array $shippingOptions): array
    {
        $addresses = &$shippingOptions['addresses'];
        foreach ($addresses as $key => $address)
        {
            $addresses[$key]['cod'] = false;
            $addresses[$key]['cod_fee'] = null;
            $shippingMethods = $address['shipping_methods'];
            foreach ($shippingMethods as $methodKey => $method)
            {
                $addresses[$key]['shipping_methods'][$methodKey]['cod'] = false;
                $addresses[$key]['shipping_methods'][$methodKey]['cod_fee'] = null;
            }
        }
        return $shippingOptions;
    }

    // The final response to FE must have cod_fee as a valid integer so we set it as Re 0.
    protected function forceDisableCodForAddress(array $address): array
    {
        $address['cod'] = false;
        $address['cod_fee'] = 0;
        foreach ($address['shipping_methods'] as &$method)
        {
            $method['cod'] = false;
            $method['cod_fee'] = 0;
        }
        return $address;
    }

    protected function errorResponse(array $address, array $taxDetails): array
    {
        return array_merge(
            $address,
            [
                'serviceable'        => false,
                'cod'                => false,
                'shipping_fee'       => 0,
                'cod_fee'            => null,
                'tax_details'        => $taxDetails,
                'is_digital_product' => false,
            ]
        );
    }
}
