<?php

namespace RZP\Models\Merchant\AccessMap;

use RZP\Constants\Product;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    const ADD_APP     = 'add_app';

    /**
     * @see MerchantController's getConnectedApplications()
     *
     * @param string $merchantId
     * @param array $input
     * @return array
     */
    public function getConnectedApplications(string $merchantId, array $input): array
    {
        $this->trace->info(TraceCode::APP_MERCHANT_ACCESS_MAP, ['merchant_id' => $merchantId, 'input' => $input]);

        $accessMaps = $this->core()->getConnectedApplications($merchantId, $input);

        return $accessMaps->toArrayPublic();
    }

    /**
     * Maps the oauth application to the merchant when he
     * first gives access to his account to the app.
     *
     * @param string $merchantId
     * @param array  $input
     * @param bool $consent // Is true only for request coming from RZP Oauth
     *
     * @return array
     */
    public function mapOAuthApplication(string $merchantId, array $input, bool $consent = false): array
    {
        $this->trace->info(TraceCode::APP_MERCHANT_ACCESS_MAP, ['input' => $input]);

        (new Validator)->validateInput(self::ADD_APP, $input);

        $merchant    = $this->repo->merchant->findOrFailPublic($merchantId);
        $entityOwner = $this->repo->merchant->findOrFailPublic($input['partner_id']);

        $mapping     = $this->core()->addMappingForOAuthApp($entityOwner, $merchant, $input);

        if ($consent === true and $input['env'] === 'prod')
        {
            (new Merchant\Core())->captureConsentsForOauth($merchantId, $input);
        }

        return $mapping->toArrayPublic();
    }

    /**
     * Deletes the mapping of oauth application to the merchant when
     * the last access token is revoked for the app.
     *
     * @param string $merchantId
     * @param string $appId
     *
     * @return array
     */
    public function deleteMapOAuthApplication(string $merchantId, string $appId)
    {
        $this->trace->info(TraceCode::APP_MERCHANT_ACCESS_MAP_DELETE, ['app_id' => $appId]);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $response = $this->core()->deleteMappingForOAuthApp($merchant, $appId);

        if(empty($response) === false)
        {
            $this->core()->triggerAccountAppAuthorizationRevokeWebhook($merchant, $appId);
        }

        return ['success' => true];
    }

    public function updateMapFromTokens()
    {
        return (new Core)->updateMapFromTokens();
    }
}
