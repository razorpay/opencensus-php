<?php

namespace RZP\Models\Gateway\Terminal;

use App;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Constants\Environment;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Hitachi\TerminalFields;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Gateway\Terminal\Constants as TerminalConstants;
use RZP\Models\Terminal\Onboarding\Service as TerminalOnboardingService;

const HITACHI_ONBOARDING_TERMINAlS_SERVICE = "hitachi_onboarding_terminals_service";
const HITACHI_ONBOARDING_TERINALS_SERVICE_VARIANT = "terminals";

class Service extends Base\Service
{
    const MERCHANT_ONBOARD                          = 'merchant_onboard';
    const GATEWAY_INPUT                             = 'gateway_input';
    const MUTEX_LOCK_TIMEOUT                        = '60';

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

        $createTerminal = $this->shouldCreateTerminal($checkFeatureEnabled, $merchant->getId(), $gateway);

        if ($createTerminal === false)
        {
            return null;
        }

        $this->trace->info(
            TraceCode::MERCHANT_ONBOARD_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $this->getMerchantOnboardingTrace($input),
            ]);

        $shouldOnboardViaTerminalsService = $this->shouldOnboardMerchantViaTerminalsService($merchant, $gateway);

        if ($shouldOnboardViaTerminalsService === true)
        {
            return $this->onboardMerchantsViaTerminalService($merchant, $input);
        }

        // applicable only for worldline
        $gatewayProcessor = GatewayFactory::build($gateway);

        $merchantDetail = $merchant->merchantDetail->toArray();

        $gatewayProcessor->addDefaultValueToMerchantDetailIfApplicable($merchantDetail);

        $gatewayProcessor->validateGatewayInput($gatewayInput, $merchantDetail);

        return $this->performOnboarding($merchant, $gatewayProcessor, $gatewayInput, $merchantDetail);
    }

    public function onboardMerchantsViaTerminalService(Merchant $merchant, array $input)
    {
        $identifiers = null;

        if (empty($input[TerminalFields::MCC]) === false)
        {
            $identifiers = [];

            $identifiers[TerminalConstants::CATEGORY] = $input[TerminalFields::MCC];
        }

        $currency = [];
        if (empty($input["gateway_input"][TerminalConstants::CURRENCY_CODE]) === false)
        {
            $currency[] = $input["gateway_input"][TerminalConstants::CURRENCY_CODE];
        };

        $terminalServiceResp = $this->app['terminals_service']->initiateOnboarding($merchant->getId(), $input['gateway'],
            $identifiers, null, $currency);

        $terminalId = $terminalServiceResp["terminal"]["id"];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE_TERMINAL, $terminalServiceResp);

        $newTerminal = $this->repo->terminal->findOrFail($terminalId);

        return $newTerminal;
    }

    protected function performOnboarding($merchant, $gatewayProcessor, $gatewayInput, $merchantDetail)
    {
        $gateway = $gatewayProcessor->getGatewayName();

        $lockResource = $gatewayProcessor->getLockResource($merchant, $gateway, $gatewayInput);

        $terminal = $this->mutex->acquireAndRelease(
            $lockResource,
            function () use ($gatewayProcessor, $merchant, $merchantDetail, $gatewayInput, $gateway) {

                $gatewayProcessor->checkDbConstraints($gatewayInput, $merchant);

                $gatewayData = $gatewayProcessor->getGatewayData($gatewayInput, $merchant, $merchantDetail);

                try
                {
                    $terminalData = $this->app['gateway']->call('mozart',
                        $gatewayProcessor->getGatewayActionName(),
                        $gatewayData,
                        $this->mode);

                        $terminal = $gatewayProcessor->processTerminalData($terminalData, $merchant, $gatewayData);

                    return $terminal;
                }
                // We are catching gateway errors so that end-user see custom msg instead of "Payment processing failed due to error at bank or wallet gateway"
                catch (Exception\GatewayErrorException $e)
                {
                    $traceData = $this->getTraceData($gatewayInput);

                    $this->trace->traceException($e, Trace::ERROR, TraceCode::MERCHANT_ONBOARD_REQUEST_FAILED, $traceData);

                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_TERMINAL_ONBOARDING_FAILED);
                }
                catch (\Throwable $e)
                {
                    $traceData = $this->getTraceData($gatewayInput);

                    $this->trace->traceException($e, Trace::ERROR, TraceCode::MERCHANT_ONBOARD_REQUEST_FAILED, $traceData);

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

    protected function shouldCreateTerminal(bool $checkFeatureEnabled, $merchantId, $gateway)
    {
        if ($gateway === Gateway::WORLDLINE)
        {
            return true;
        }

        $isFunc = $this->app->environment(Environment::FUNC);

        if($isFunc === true){
            return false;
        }

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
                ($terminal->supportsCurrency($currency) === true) and
                ($terminal->isDirectForMerchant() === true) and
                ($terminal->getCategory() === $category) and
                ($terminal->getStatus() === Terminal\Status::ACTIVATED))
            {
                return true;
            }
        }
        return false;
    }

    public function checkDirectTerminalForFulcrumGateway(array $terminals, $gateway, $merchant, $currency):bool
    {
        $category = $merchant->getCategory();

        foreach ($terminals as $terminal)
        {
            if (($terminal->getGateway() === $gateway) and
                ($terminal->supportsCurrency($currency) === true) and
                ($terminal->getCategory() === $category))
            {
                return true;
            }
        }
        return false;
    }

    protected function shouldOnboardMerchantViaTerminalsService($merchant, $gateway)
    {
        if ($gateway === Gateway::WORLDLINE)
        {
            return false;
        }

        return true;
    }

    public function callGatewayForTerminalEnableOrDisable($terminal, $action)
    {
        $gateway = $terminal->gateway;

        $gatewayProcessor = GatewayFactory::build($gateway);

        $request = $gatewayProcessor->getGatewayRequestArrayForEnableOrDisable($terminal);

        $response = $this->app['gateway']->call($gateway, $action, $request, $this->mode, $terminal);

        $gatewayProcessor->raiseExceptionIfEnableOrDisableFails($response, $action);
    }

    protected function getMerchantOnboardingTrace($input)
    {
        if ($input['gateway'] === Gateway::WORLDLINE)
        {
            return (new TerminalOnboardingService)->getCreateTraceInput($input['gateway_input']);
        }

        return $input;
    }

    private function getTraceData($gatewayInput)
    {
        if (is_array($gatewayInput) === false)
        {
            return $gatewayInput;
        }

        $keys = ['mpan'];

        foreach ($keys as $key)
        {
            unset($gatewayInput[$key]);
        }

        return $gatewayInput;
    }
}
