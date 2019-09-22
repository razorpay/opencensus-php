<?php

namespace RZP\Models\Gateway\Terminal;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Gateway\Base\Terminal;
use RZP\Constants\Environment;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Terminal\Service as TerminalService;
use RZP\Models\Merchant\Entity as Merchant;

class Service extends Base\Service
{
    const MERCHANT_ONBOARD   = 'merchant_onboard';
    const GATEWAY_INPUT      = 'gateway_input';
    const TERMINAL           = 'terminal';
    const MUTEX_LOCK_TIMEOUT = '60';

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = App::getFacadeRoot()['api.mutex'];
    }

    public function onboardMerchant(Merchant $merchant, array $input, bool $checkFeatureEnabled)
    {
        (new Validator)->validateInput(self::MERCHANT_ONBOARD, $input);

        $gateway = $input['gateway'];

        $gatewayInput = $input['gateway_input'];

        $gatewayProcessor = GatewayFactory::build($gateway);

        $createTerminal = $this->shouldCreateTerminal($checkFeatureEnabled, $merchant->getId());

        if ($createTerminal === false)
        {
            return null;
        }

        $this->trace->info(
            TraceCode::MERCHANT_ONBOARD_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $input,
            ]);

        $gatewayProcessor->validateGatewayInput($gatewayInput, $merchant);

        return $this->performOnboarding($merchant, $gatewayProcessor, $gatewayInput);
    }

    public function onboardMerchantAsync(Merchant $merchant, $input)
    {
        (new Validator)->validateInput(self::MERCHANT_ONBOARD, $input);

        $gateway = $input['gateway'];

        $gatewayInput = $input['gateway_input'];

        $gatewayProcessor = GatewayFactory::build($gateway);

        $gatewayProcessor->validateGatewayInput($gatewayInput, $merchant);

        return $this->performOnboardingAsync($merchant, $gatewayProcessor, $gatewayInput);
    }

    protected function performOnboarding($merchant, $gatewayProcessor, $gatewayInput)
    {
        $gateway = $gatewayProcessor->getGatewayName();

        $merchantDetail = $merchant->merchantDetail->toArray();

        $lockResource = $gatewayProcessor->getLockResource($merchant, $gateway, $gatewayInput);

        $terminal = $this->mutex->acquireAndRelease(
            $lockResource,
            function () use ($gatewayProcessor, $merchant, $merchantDetail, $gatewayInput, $gateway) {

                $gatewayProcessor->checkDbConstraints($gatewayInput, $merchant);

                $gatewayInput = $gatewayProcessor->getInputValue($gatewayInput, $merchant);

                $gatewayData = [
                    'merchant'         => $merchant,
                    'merchant_details' => $merchantDetail,
                    'gateway_input'    => $gatewayInput,
                ];

                try
                {
                    $terminalData = $this->app['gateway']->call($gateway,
                        Terminal::MERCHANT_ONBOARD,
                        $gatewayData,
                        $this->mode);

                    $terminal = $gatewayProcessor->processTerminalData($terminalData, $merchant);

                    return $terminal;
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e, Trace::ERROR, TraceCode::MERCHANT_ONBOARD_REQUEST_FAILED, $gatewayInput);

                    throw $e;
                }
            },
            self::MUTEX_LOCK_TIMEOUT);

        if ($terminal !== null)
        {
            $terminal->setDirectForMerchant(true);
        }

        return $terminal;
    }

    protected function shouldCreateTerminal(bool $checkFeatureEnabled, $merchantId)
    {
        $isProduction = $this->app->environment(Environment::PRODUCTION);

        if ($isProduction === false)
        {
            if ($this->mode === Mode::TEST)
            {
                return true;
            }

            return false;
        }

        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        if ($checkFeatureEnabled === true)
        {
            $response = $this->app->razorx->getTreatment($merchantId, 'merchant_onboard_terminal', $this->mode);

            if (($response === 'control') or
                ($response === 'off'))
            {
                return false;
            }
        }

        return true;
    }

    public function checkDirectTerminalForGateway(array $terminals, $gateway, $merchant, $currency):bool
    {
        $category = $merchant->getCategory();

        foreach ($terminals as $terminal)
        {
            if (($terminal->getGateway() === $gateway) and
                ($terminal->isCurrency($currency)) and
                ($terminal->isDirectForMerchant() === true) and
                ($terminal->getCategory() === $category))
            {
                return true;
            }
        }

        return false;
    }

    protected function performOnboardingAsync($merchant, $gatewayProcessor, $gatewayInput)
    {
        $gatewayProcessor->checkDbConstraints($gatewayInput, $merchant);

        $terminalData = $gatewayProcessor->getInputValue($gatewayInput, $merchant);

        return $gatewayProcessor->processTerminalData($terminalData, $merchant);
    }
}
