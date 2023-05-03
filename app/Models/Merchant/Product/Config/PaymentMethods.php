<?php

namespace RZP\Models\Merchant\Product\Config;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Services\TerminalsService;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Product\Util;


class PaymentMethods
{
    const X_DASHBOARD_MERCHANT_ID     = "X-Dashboard-Merchant-Id";
    const X_DASHBOARD_MERCHANT_ORG_ID = "X-Dashboard-Merchant-OrgId";
    const FEATURES                    = ['intent_on_ios', 'google_pay_omnichannel'];
    const PARTNERSHIP_TERMINAL_REQUEST_TIMEOUT = "PARTNERSHIP_TERMINAL_REQUEST_TIMEOUT";

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

        $this->trace = $this->app['trace'];

        $this->terminalService = $this->app['terminals_service'];

        $this->merchantService = new Merchant\Service();
    }


    public function get(Merchant\Entity $merchant): array
    {
        $timeout = env(self::PARTNERSHIP_TERMINAL_REQUEST_TIMEOUT);

        $response = $this->terminalService->proxyTerminalService(
            [],
            \Requests::GET,

            'v2/merchant_instrument_status?merchant_id=' . $merchant->getId(),
            ['timeout' => $timeout], //Increasing timeout to 4sec for now
            $this->getMerchantHeadersForInstrumentRequest());

//        $response[Util\Constants::FEATURES] = $this->getFeatures();

        $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE, [
            'response' => $response
        ]);

        return $response;
    }

    public function create(array $input): array
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $responses = [];

        foreach ($input as $row)
        {
            try {
                $request['merchant_id'] = $merchant->getId();

                $request['instrument'] = $row;

                $response = $this->app['terminals_service']->proxyTerminalService(
                    $request,
                    \Requests::POST,
                    'v2/merchant_instrument_request',
                    ['timeout' => 0.5],
                    $this->getMerchantHeadersForInstrumentRequest());

                $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE, $response);

                array_push($responses, $response);
            }
            catch (\Exception $exp)
            {
                $this->trace->traceException($exp,
                    Trace::ERROR,
                    TraceCode::TERMINALS_SERVICE_INTEGRATION_ERROR
                );
            }
        }

        return $responses;

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

    public function createMethod(string $instrument)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $request = [
            'merchant_id' => $merchant->getId(),
            'instrument'  => $instrument
        ];

        $response = $this->app['terminals_service']->proxyTerminalService(
            $request,
            \Requests::POST,
            'v2/merchant_instrument_request',
            ['timeout' => 1],
            $this->getMerchantHeadersForInstrumentRequest());

        $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE, $response);

        return $response;
    }
}
