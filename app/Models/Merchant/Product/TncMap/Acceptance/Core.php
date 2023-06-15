<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Product;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Models\Merchant\Product\Status as ProductStatus;
use RZP\Models\Merchant\Product\TncMap\Core as TncCore;
use RZP\Models\Merchant\Product\TncMap\Entity as TncMap;

class Core extends Base\Core
{
    public function fetchTnc(string $productName = Product\Name::ALL) : TncMap
    {
        $entity = $this->repo->tnc_map->fetchLatestTnCByProductName($productName);

        return $entity;
    }

    public function hasPendingTnc(Merchant\Entity $merchant, $productName = Product\Name::ALL): bool
    {
        $tncMap = $this->fetchTnc($productName);

        $exists = $this->repo->merchant_tnc_acceptance->acceptedTncExists($tncMap->getId(), $merchant->getId());

        return ($exists === false);
    }

    public function hasAcceptedBusinessUnitTnc(Merchant\Entity $merchant, $businessUnit = Product\BusinessUnit\Constants::PAYMENTS): bool
    {
        $tncMap = (new TncCore)->fetchTncForBU($businessUnit);

        $exists = $this->repo->merchant_tnc_acceptance->acceptedTncExists($tncMap->getId(), $merchant->getId());

        return ($exists === true);
    }

    public function fetchMerchantAcceptance(Merchant\Entity $merchant, $productName = Product\Name::ALL)
    {
        $tncMap = $this->fetchTnc($productName);

        return $this->repo->merchant_tnc_acceptance->fetchMerchantAcceptanceByTncMapId($tncMap->getId(), $merchant->getId());
    }

    public function fetchMerchantAcceptanceViaBU(Merchant\Entity $merchant, $businessUnit = Product\BusinessUnit\Constants::PAYMENTS)
    {
        $tncMap = (new TncCore)->fetchTncForBU($businessUnit);

        return $this->repo->merchant_tnc_acceptance->fetchMerchantAcceptanceByTncMapId($tncMap->getId(), $merchant->getId());
    }

    public function acceptTnc(Merchant\Entity $merchant, TncMap $tncMap, string $ip = null, string $channel = 'API')
    {
        return $this->repo->transactionOnLiveAndTest(function() use($ip, $merchant, $tncMap, $channel) {

            $request = (new Entity)->generateId();

            $request->merchant()->associate($merchant);

            $request->tncMap()->associate($tncMap);

            $request[Entity::ACCEPTED_CHANNEL] = $channel;

            [$clientIp, $device] = $this->fetchIpAndDevice();

            // Overriding value of IP address to what is provided in input payload
            $clientIp = $ip ?: $clientIp;

            // Because of 5xx errors when device is null, "unknown" is assigned as client_device value by default for
            // the below routes. Jira - https://razorpay.atlassian.net/browse/PRTS-2630
            if ($device === null)
            {
                $route = $this->app['api.route']->getCurrentRouteName();

                if (empty($route) === false and in_array($route, ['product_config_tnc_accept_v2', 'product_config_create_v2']) === true)
                {
                    $device = 'unknown';
                }
            }

            $request[Entity::CLIENT_DEVICE] = $device;

            $request[Entity::CLIENT_IP] = $clientIp;

            $this->repo->saveOrFail($request);

            AutoUpdateMerchantProducts::dispatch(ProductStatus::TNC_SOURCE, $merchant->getId());

            return $request;
        });
    }

    private function fetchIpAndDevice(): array
    {
        if ($this->app['env'] === Environment::TESTING)
        {
            return ['127.0.0.1', 'phpunit'];
        }

        $ip = $this->app['request']->ip();

        $device = $_SERVER["HTTP_USER_AGENT"];

        return [$ip, $device];
    }

    public function isPartnerExcludedFromProvidingSubmerchantIp($partnerId): bool
    {
        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $this->app['config']->get('app.excluded_partners_from_providing_subm_ip_experiment_id')
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }
}
