<?php

namespace RZP\Models\Merchant\Product\Config;

use App;
use RZP\Models\Merchant;
use RZP\Services\TerminalsService;
use RZP\Models\Merchant\Product\Util;

class PaymentMethods
{
    const X_DASHBOARD_MERCHANT_ID     = "X-Dashboard-Merchant-Id";
    const X_DASHBOARD_MERCHANT_ORG_ID = "X-Dashboard-Merchant-OrgId";
    const FEATURES                    = ['intent_on_ios', 'google_pay_omnichannel'];

    /**
     * @var TerminalsService
     */
    private $terminalService;

    private $app;

    /**
     * @var Merchant\Service
     */
    private $merchantService;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->terminalService = new TerminalsService($this->app);

        $this->merchantService = new Merchant\Service();
    }


    public function get(Merchant\Entity $merchant): array
    {
        $response = $this->terminalService->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/merchant_instrument_request?merchant_id=' . $merchant->getId(),
            ['timeout' => 1],
            $this->getMerchantHeadersForInstrumentRequest());

        $response = [];

        $response[Util\Constants::FEATURES] = $this->getFeatures();

        return $response;
    }

    public function create(array $input): array
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $input['merchant_id'] = $merchant->getId();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            'v2/merchant_instrument_request',
            ['timeout' => 0.5],
            $this->getMerchantHeadersForInstrumentRequest());

        return $response;
    }

    private function getMerchantHeadersForInstrumentRequest(): array
    {
        $merchant = $this->app['basicauth']->getMerchant();

        return [
            self::X_DASHBOARD_MERCHANT_ID     => $merchant->getId(),
            self::X_DASHBOARD_MERCHANT_ORG_ID => $merchant->getOrgId(),
        ];

    }

    private function getFeatures(): array
    {
        $response = [];

        $merchantFeatures = $this->merchantService->getMerchantFeatures();

        $features = array_filter($merchantFeatures['features'], function($feature) {
            return (in_array($feature['feature'], self::FEATURES));
        });

        foreach ($features as $feature)
        {
            $featureData = [];

            $featureData[$feature['feature']] = $feature['value'];

            $response[] = $featureData;
        }

        return $response;
    }

    public function update(Merchant\Entity $merchant, array $configs): array
    {
        if(isset($configs[Util\Constants::FEATURES]) == true)
        {
            $features = $configs[Util\Constants::FEATURES];

            $this->updateFeatures($features);
        }

        //return $this->get($merchant);
        return [];
    }

    public function updateFeatures(array $input)
    {
        //$this->merchantService->addOrRemoveMerchantFeatures($input);
    }
}
