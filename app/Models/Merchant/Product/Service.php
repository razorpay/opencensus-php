<?php

namespace RZP\Models\Merchant\Product;

use App;
use RZP\Constants\HyperTrace;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Methods;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product\Config;
use RZP\Models\Merchant\Product\TncMap\Acceptance\Service as Tnc;
use RZP\Models\Merchant\Account\Entity as AccountEntity;
use RZP\Models\Merchant\Product\Util\ProductRequestHandler;
use RZP\Models\Merchant\Product\Util\ProductResponseHandler;
use RZP\Trace\Tracer;

class Service extends Base\Service
{

    public function getConfig(string $merchantId, string $merchantProductConfigId)
    {
        $timeStarted = millitime();

        list($merchant, $partner) = $this->validateAndSetMerchantContext($merchantId);

        Entity::verifyIdAndStripSign($merchantProductConfigId);

        $merchantProduct = $this->validateAndGetMerchantProduct($merchant->getId(), $merchantProductConfigId);

        $response = Tracer::inspan(['name' => HyperTrace::GET_PRODUCT_CONFIG_CORE], function () use ($merchant, $merchantProduct) {

            return $this->core()->getConfig($merchant, $merchantProduct);
        });

        $timeTaken = millitime() - $timeStarted;

        $this->captureMetricsForFetchProductConfig($merchantProduct, $timeTaken);

        return Tracer::inspan(['name' => HyperTrace::HANDLE_PRODUCT_CONFIG_RESPONSE], function () use ($merchantProduct, $response) {

            return ProductResponseHandler::handleResponse($merchantProduct, $response);
        });
    }

    public function updateConfig(string $merchantId, string $merchantProductConfigId, array $request)
    {
        list($merchant, $partner) = $this->validateAndSetMerchantContext($merchantId);

        Entity::verifyIdAndStripSign($merchantProductConfigId);

        $merchantProduct = $this->validateAndGetMerchantProduct($merchant->getId(), $merchantProductConfigId);

        $productName = $merchantProduct->getProduct();

        $isPaymentMethodConfigUpdateExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
            $partner->getId(),
            Merchant\RazorxTreatment::PAYMENT_METHOD_CONFIG_UPDATE
        );

        \Request::instance()->request->add([Util\Constants::CONFIG_UPDATE_FLOW_ENABLED => $isPaymentMethodConfigUpdateExperimentEnabled]);

        $transformedRequest = Tracer::inspan(['name' => HyperTrace::TRANSFORM_PRODUCT_CONFIG_REQUEST], function () use ($productName, $request) {

            return ProductRequestHandler::handleRequest($productName, $request);
        });

        $response = Tracer::inspan(['name' => HyperTrace::UPDATE_PRODUCT_CONFIG_CORE], function () use ($merchant, $merchantProduct, $transformedRequest) {

            return $this->core()->updateConfig($merchant, $merchantProduct, $transformedRequest);
        });

        $this->captureMetricsForUpdateProductConfig($merchantProduct);

        return Tracer::inspan(['name' => HyperTrace::HANDLE_PRODUCT_CONFIG_RESPONSE], function () use ($merchantProduct, $response) {

            return ProductResponseHandler::handleResponse($merchantProduct, $response);
        });
    }

    public function createConfig(string $merchantId, array $payload): array
    {
        list($merchant, $partner) = $this->validateAndSetMerchantContext($merchantId);

        (new Validator())->validateInput('create', $payload);

        $merchantProductInput = $this->getMerchantProductInput($payload);

        $productName = $merchantProductInput[Entity::PRODUCT_NAME];

        $payload = Tracer::inspan(['name' => HyperTrace::ACCEPT_PRODUCT_TNC], function () use ($payload, $productName, $merchant) {

            if (isset($payload[Util\Constants::TNC_ACCEPTED]) === true)
            {
                unset($payload[Util\Constants::TNC_ACCEPTED]);

                $ip = null;

                if(isset($payload[Util\Constants::IP]) === true)
                {
                    $ip = $payload[Util\Constants::IP];

                    unset($payload[Util\Constants::IP]);
                }

                (new Tnc)->acceptProductConfigTnc($productName, $merchant, $ip);
            }

            return $payload;
        });

        $merchantProduct = $this->repo->merchant_product->fetchMerchantProductConfigByProductName($merchantId, $productName);

        if (empty($merchantProduct) === false)
        {
            $this->trace->info(TraceCode::MERCHANT_PRODUCT_ALREADY_EXISTS,
                               [
                                   'merchant_id'         => $merchant->getId(),
                                   'merchant_product'    => $merchantProduct->getProduct(),
                                   'merchant_product_id' => $merchantProduct->getId()
                               ]);

            $response = Tracer::inspan(['name' => HyperTrace::GET_PRODUCT_CONFIG], function () use ($merchantId, $merchantProduct) {

                return $this->getConfig(AccountEntity::getSignedId($merchantId), $merchantProduct->getPublicId());
            });
        }

        else
        {
            $payload = Tracer::inspan(['name' => HyperTrace::VALIDATE_AND_FETCH_DEFAULT_CONFIG], function() use ($payload, $productName, $merchant) {
                return $this->getProductConfigPayload($payload, $productName, $merchant);
            });

            $isExpEnabled = (new Merchant\Core())->isExpEnabledForProductConfigIssue($partner);

            Tracer::inspan(['name' => HyperTrace::SET_DEFAULT_METHODS], function () use ($merchant, $partner, $isExpEnabled) {

                if($isExpEnabled === true) {
                    (new Methods\Core())->setMethods($merchant, $partner);
                }
                else {
                    (new Methods\Core())->setDefaultMethods($merchant, $partner);
                }
            });

            $response = $this->repo->transactionOnLiveAndTest(function() use ($merchant, $payload, $productName) {

                $input = ['product_name' => $productName];

                $merchantProduct = $this->core()->create($merchant, $input);

                $response = Tracer::inspan(['name' => HyperTrace::CREATE_PRODUCT_CONFIG_CORE], function () use ($merchant, $merchantProduct, $payload) {

                    return $this->core()->createConfig($merchant, $merchantProduct, $payload);
                });

                $this->captureMetricsForCreateProductConfig($merchantProduct);

                return Tracer::inspan(['name' => HyperTrace::HANDLE_PRODUCT_CONFIG_RESPONSE], function () use ($merchantProduct, $response) {

                    return ProductResponseHandler::handleResponse($merchantProduct, $response);
                });
            });
        }

        return $response;
    }

    /**
     * This function would return the input as is if input is not empty. If input is empty, fetches the default configuration for the product specified
     * @param array  $input
     * @param string $productName
     *
     * @return array
     */
    private function getProductConfigPayload(array $input, string $productName, Merchant\Entity $partner): array
    {
        if (empty($input) === true)
        {
            $input = (new Config\DefaultConfigurationHelper)->getDefaultConfiguration($productName, $input, $partner);
        }

        return $input;
    }

    public function getDefaultConfiguration(string $productName): array
    {
        $data = [];

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
            case Name::PAYMENT_LINKS:
                $data = Config\Defaults::PAYMENT_GATEWAY;
                break;
            case Name::ROUTE:
                $data = Config\Defaults::ROUTE;
                break;
        }

        return $data;
    }

    /**
     * This function returns the input needed for creation of merchant product entity
     * @param array $input
     *
     * @return array
     */
    private function getMerchantProductInput(array & $input): array
    {
        $payload = [];

        if(isset($input[Entity::PRODUCT_NAME]) === true)
        {
            $payload[Entity::PRODUCT_NAME] = $input[Entity::PRODUCT_NAME];

            unset($input[Entity::PRODUCT_NAME]);
        }

        return $payload;
    }

    private function validateAndSetMerchantContext(string & $merchantId): array
    {
        $this->app = App::getFacadeRoot();

        AccountEntity::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $partner = null;
        // This means auth can be private auth of partner or partner auth of partner without X-Account-Id
        if($this->merchant->getId() !== $merchantId)
        {
            $partner = $this->merchant;

            (new Account\Core)->validatePartnerAccess($this->merchant, $merchantId);

            $this->app['basicauth']->setPartnerMerchantId($this->merchant->getId());

        }

        $this->app['basicauth']->setMerchant($merchant);

        return [$merchant, $partner];
    }

    private function validateAndGetMerchantProduct(string $merchantId, string $merchantProductConfigId): Entity
    {
        $merchantProduct = $this->repo->merchant_product->fetchMerchantProductConfigByProductId($merchantId, $merchantProductConfigId);

        if (empty($merchantProduct) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_PRODUCT_CONFIG_DOESNT_EXIST,
                null,
                [
                    'account_id'                 => AccountEntity::getSignedId($merchantId),
                    'merchant_product_config_id' => Entity::getSignedId($merchantProductConfigId),
                ]);
        }

        return $merchantProduct;
    }

    private function captureMetricsForCreateProductConfig(Entity $merchantProduct)
    {
        $dimensions = $this->getDimensionsForMerchantProduct($merchantProduct);

        $this->trace->count(Metric::PRODUCT_CONFIG_CREATE_SUCCESS_TOTAL, $dimensions);
    }

    private function captureMetricsForUpdateProductConfig(Entity $merchantProduct)
    {
        $dimensions = $this->getDimensionsForMerchantProduct($merchantProduct);

        $this->trace->count(Metric::PRODUCT_CONFIG_UPDATE_SUCCESS_TOTAL, $dimensions);
    }

    private function captureMetricsForFetchProductConfig(Entity $merchantProduct, $latencyInMillis)
    {
        $dimensions = $this->getDimensionsForMerchantProduct($merchantProduct);

        $this->trace->count(Metric::PRODUCT_CONFIG_FETCH_SUCCESS_TOTAL, $dimensions);

        $this->trace->histogram(Metric::PRODUCT_CONFIG_FETCH_TIME_IN_MS, $latencyInMillis, $dimensions);
    }

    private function getDimensionsForMerchantProduct(Entity $merchantProduct)
    {
        $dimensions = [
            'product'     => $merchantProduct->getProduct(),
        ];

        return $dimensions;
    }
}
