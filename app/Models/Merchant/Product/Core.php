<?php

namespace RZP\Models\Merchant\Product;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Jobs\MerchantProductsConfig;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Product\Config;
use RZP\Models\Merchant\Product\Requirements;
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
                $response = $this->getPaymentGatewayConfig($merchant, $merchantProduct);
                break;
        }

        return $response;
    }

    private function getPaymentGatewayConfig(Merchant\Entity $merchant, Entity $merchantProduct): array
    {
        $response = [];

        $response = array_merge($response, $this->paymentsGeneralConfig->getConfig($merchant));

        $response[Util\Constants::REQUIREMENTS] = $this->paymentsGeneralConfig->getRequirements($merchant, $merchantProduct);

        $response[Util\Constants::PAYMENT_METHODS] = $this->paymentMethods->get($merchant);

        return $response;
    }

    private function createPaymentGeneralConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        $response = [];

        $response = array_merge($response, $this->paymentsGeneralConfig->createConfig($merchant, $input));

        $response[Util\Constants::REQUIREMENTS] = $this->paymentsGeneralConfig->getRequirements($merchant, $merchantProduct);

        $this->audit($input, $merchantProduct->getId(),Util\Constants::COMPLETED, Util\Constants::GENERAL);

        if (count($response[Util\Constants::REQUIREMENTS]) > 0)
        {
            $merchantProduct->setActivationStatus(Status::NEEDS_CLARIFICATION);

            $this->repo->merchant_product->saveOrFail($merchantProduct);
        }

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
        }

        $response = array_merge($response, $this->paymentsGeneralConfig->updateConfig($merchant, $input));

        $this->audit($input, $merchantProduct->getId(), Util\Constants::COMPLETED, Util\Constants::GENERAL);

        $response[Util\Constants::REQUIREMENTS] = $this->paymentsGeneralConfig->getRequirements($merchant, $merchantProduct);

        return $response;
    }

    /**
     * This function syncs PG product status with merchant activation status since PG product is tightly bounded with
     * merchant activation
     *
     * @param Detail\Entity $merchantDetails
     */
    public function updatePaymentGatewayConfigStatusIfApplicable(Detail\Entity $merchantDetails)
    {
        try
        {
            $paymentGatewayMerchantProduct = $this->repo->merchant_product->fetchMerchantProductConfigByProductName($merchantDetails->getMerchantId(), Name::PAYMENT_GATEWAY);

            if (empty($paymentGatewayMerchantProduct) === false)
            {
                $this->trace->info(TraceCode::MERCHANT_PRODUCT_STATUS_AUTO_UPDATE, [
                    'merchant_id'         => $merchantDetails->getMerchantId(),
                    'merchant_product_id' => $paymentGatewayMerchantProduct->getId()
                ]);

                $merchantActivationStatus = $merchantDetails->getActivationStatus();

                $paymentGatewayMerchantProductStatus = Status::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING[$merchantActivationStatus] ?? null;

                $paymentGatewayMerchantProduct->setActivationStatus($paymentGatewayMerchantProductStatus);

                $this->repo->merchant_product->saveOrFail($paymentGatewayMerchantProduct);

                (new Events\Service())->notifyProductActivationStatus($paymentGatewayMerchantProduct);
            }

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         null,
                                         TraceCode::MERCHANT_PRODUCT_STATUS_UPDATE_FAILURE,
                                         [
                                             'merchant_id' => $merchantDetails->getMerchantId()
                                         ]);
        }
    }

    private function audit(array $input, string $merchantProductId, string $status, string $type)
    {
        return (new AuditService)->log($input, $merchantProductId, $status, $type);
    }

    /**
     * @param array $input
     * @param $request
     * @param Merchant\Entity $merchant
     * @param Entity $merchantProduct
     * @return array
     */
    private function createPaymentGatewayConfig(Merchant\Entity $merchant, Entity $merchantProduct, array $input): array
    {
        if (array_key_exists(Util\Constants::PAYMENT_METHODS, $input)) {

//            $request = [];

//            $request[Util\Constants::PAYMENT_METHODS] = $input[Util\Constants::PAYMENT_METHODS];

            unset($input[Util\Constants::PAYMENT_METHODS]);

//            $response = $this->createPaymentMethodsConfig($merchant, $merchantProduct, $request);
        }



        $input = Util\PaymentGatewayRequestHelper::handleRequest($input);

        $response = $this->createPaymentGeneralConfig($merchant, $merchantProduct, $input);

        $response[Util\Constants::PAYMENT_METHODS] = $this->paymentMethods->get($merchant);

        return $response;
    }
}
