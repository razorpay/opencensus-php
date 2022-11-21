<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify\AddressIngestion;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;

class Service extends \RZP\Models\Merchant\OneClickCheckout\Shopify\Service
{
    /**
     * @throws Exception\BadRequestException if merchant doesn't have shopify account configured
     */
    protected function getShopifyClientByMerchant(): Client
    {
        $credentials = $this->getShopifyAuthByMerchant();
        if (empty($credentials) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED);
        }
        return new Client($credentials);
    }

    protected function getShopifyFormattedDate($timestamp)
    {
        return date('c', $timestamp);
    }

    /**
     * @throws Exception\BadRequestException if merchant is an invalid one cc merchant
     */
    public function getCustomerAddresses($input): array
    {
        $client = $this->getShopifyClientByMerchant();

        $feature = $this->repo->feature->findByEntityTypeEntityIdAndName(
            Feature\Constants::MERCHANT,
            $this->merchant->getId(),
            Feature\Constants::ONE_CLICK_CHECKOUT
        );

        if(empty($feature))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_INVALID_ONE_CC_MERCHANT);
        }

        $sinceId = $input['since_id'] ?? 0;

        $oneCCMerchantOnboardedTimestamp = $feature->getCreatedAt();
        $queryParams = [
            'created_at_max' => $this->getShopifyFormattedDate($oneCCMerchantOnboardedTimestamp),
            'fields' => 'id,addresses',
            'limit' => 250,
            'since_id' => $sinceId,
        ];

        return $client->getCustomerAddresses($queryParams);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function getInternalCustomerAddresses($merchantId, $input): array
    {
        try
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Exception $ex)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
        }
        $this->app['basicauth']->setMerchant($this->merchant);

        return $this->getCustomerAddresses($input);
    }

}
