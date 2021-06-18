<?php

namespace RZP\Models\Merchant\Product;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product\Config;
use RZP\Models\Merchant\Account\Entity as AccountEntity;
use RZP\Models\Merchant\Product\Util\ProductRequestHandler;
use RZP\Models\Merchant\Product\Util\ProductResponseHandler;

class Service extends Base\Service
{

    public function getConfig(string $merchantId, string $merchantProductConfigId)
    {
        $timeStarted = microtime(true);

        $merchant = $this->validateAndSetMerchantContext($merchantId);

        Entity::verifyIdAndStripSign($merchantProductConfigId);

        $merchantProduct = $this->validateAndGetMerchantProduct($merchant->getId(), $merchantProductConfigId);

        $response = $this->core()->getConfig($merchant, $merchantProduct);

        $timeTaken = get_diff_in_millisecond($timeStarted);

        $this->captureMetricsForFetchProductConfig($merchantProduct, $timeTaken);

        return ProductResponseHandler::handleResponse($merchantProduct, $response);
    }

    public function updateConfig(string $merchantId, string $merchantProductConfigId, array $request)
    {
        $merchant = $this->validateAndSetMerchantContext($merchantId);

        Entity::verifyIdAndStripSign($merchantProductConfigId);

        $merchantProduct = $this->validateAndGetMerchantProduct($merchant->getId(), $merchantProductConfigId);

        $productName = $merchantProduct->getProduct();

        $transformedRequest = ProductRequestHandler::handleRequest($productName, $request);

        $response = $this->core()->updateConfig($merchant, $merchantProduct, $transformedRequest);

        $this->captureMetricsForUpdateProductConfig($merchantProduct);

        return ProductResponseHandler::handleResponse($merchantProduct, $response);
    }

    public function createConfig(string $merchantId, array $payload): array
    {
        $merchant = $this->validateAndSetMerchantContext($merchantId);

        $productName = $payload['name'];

        $merchantProduct = $this->repo->merchant_product->fetchMerchantProductConfigByProductName($merchantId, $productName);

        if (empty($merchantProduct) === false)
        {
            $this->trace->info(TraceCode::MERCHANT_PRODUCT_ALREADY_EXISTS,
                               $merchantProduct->toArrayPublic());

            $response = $this->getConfig(AccountEntity::getSignedId($merchantId), $merchantProduct->getPublicId());
        }

        else
        {
            $payload = $this->getPayload($payload);

            $merchantProduct = (new Entity)->generateId();

            $response = $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantProduct, $payload, $productName) {

                $input = ['merchant_id' => $merchant->getId(), 'product_name' => $productName];

                $merchantProduct->setActivationStatus(Status::REQUESTED);

                $merchantProduct->build($input);

                $this->repo->merchant_product->saveOrFail($merchantProduct);

                $response = $this->core()->createConfig($merchant, $merchantProduct, $payload);

                $this->captureMetricsForCreateProductConfig($merchantProduct);

                return ProductResponseHandler::handleResponse($merchantProduct, $response);
            });
        }

        return $response;
    }

    private function getPayload(array $input): array
    {
        $productName = $input['name'];

        unset($input['name']);

        if( empty($input) === true)
        {
            $input = $this->getDefaultConfiguration($productName);
        }

        return $input;
    }

    public function getDefaultConfiguration(string $productName): array
    {
        $data = [];

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
                $data = Config\Defaults::PAYMENT_GATEWAY;
                break;
        }

        return $data;
    }

    private function validateAndSetMerchantContext(string & $merchantId): Merchant\Entity
    {
        $this->app = App::getFacadeRoot();

        AccountEntity::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        // This means auth can be private auth of partner or partner auth of partner without X-Account-Id
        if($this->merchant->getId() !== $merchantId)
        {
            (new Account\Core)->validatePartnerAccess($this->merchant, $merchantId);

            $this->app['basicauth']->setPartnerMerchantId($this->merchant->getId());

        }

        $this->app['basicauth']->setMerchant($merchant);

        return $merchant;
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
