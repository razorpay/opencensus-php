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
use RZP\Models\TerminalOnboardingDetail;
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
            $identifiers, $currency);

        $terminalId = $terminalServiceResp["terminal"]["id"];


        $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE_TERMINAL, $terminalServiceResp);

        $newTerminal = $this->repo->terminal->findOrFail($terminalId);

        return $newTerminal;
    }

    public function onboardMerchantAsync(Merchant $merchant, $input)
    {
        (new Validator)->validateInput(self::MERCHANT_ONBOARD, $input);

        $gateway = $input['gateway'];

        $gatewayInput = $input['gateway_input'];

        $gatewayProcessor = GatewayFactory::build($gateway);

        $merchantDetail = $merchant->merchantDetail->toArray();

        $gatewayProcessor->validateGatewayInput($gatewayInput, $merchantDetail);

        return $this->performOnboardingAsync($merchant, $gatewayProcessor, $gatewayInput);
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
                    $this->trace->traceException($e, Trace::ERROR, TraceCode::MERCHANT_ONBOARD_REQUEST_FAILED, $gatewayInput);

                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_TERMINAL_ONBOARDING_FAILED);
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

    public function verifyTerminals($terminals)
    {
        $cronResponse = [
            Constants::ACTIVATED_TERMINALS          => 0,
            Constants::PENDING_TERMINALS            => 0,
            Constants::ACTIVATION_FAILED_TERMINALS  => 0,
            Constants::NOT_APPLICABLE_TERMINALS  => 0,
            Constants::VERIFICATION_ERROR_TERMINALS => 0,
        ];

        foreach ($terminals as $terminal)
        {
            try
            {
                $updatedStatus = $this->performVerificationAsync($terminal);

                switch ($updatedStatus)
                {
                    case TerminalOnboardingDetail\Status::ACTIVATED:
                        $cronResponse[Constants::ACTIVATED_TERMINALS]++;
                        break;
                    case TerminalOnboardingDetail\Status::PENDING:
                        $cronResponse[Constants::PENDING_TERMINALS]++;
                        break;
                    case TerminalOnboardingDetail\Status::ACTIVATION_FAILED:
                        $cronResponse[Constants::ACTIVATION_FAILED_TERMINALS]++;
                        break;
                }

            }
            catch (\Throwable $ex)
            {
                if( ($ex->getMessage() === 'Terminal has already been processed') or
                    ($ex->getCode() === ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_ANOTHER_OPERATION_IN_PROGRESS)
                )
                {
                    $cronResponse[Constants::NOT_APPLICABLE_TERMINALS]++;
                }
                else
                {
                    $cronResponse[Constants::VERIFICATION_ERROR_TERMINALS]++;
                }
            }

        }

        return $cronResponse;
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

    protected function shouldOnboardMerchantViaTerminalsService($merchant, $gateway)
    {
        if ($gateway === Gateway::WORLDLINE)
        {
            return false;
        }
        
        $variantFlag = $this->app->razorx->getTreatment($merchant['id'], HITACHI_ONBOARDING_TERMINAlS_SERVICE, $this->mode);

        $data = [
            'feature'   => HITACHI_ONBOARDING_TERMINAlS_SERVICE,
            'variant'   => $variantFlag,
        ];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_ONBOARD_RESPONSE, $data);

        $shouldUseTerminalService = ($variantFlag === HITACHI_ONBOARDING_TERINALS_SERVICE_VARIANT ? true : false);

        if ($shouldUseTerminalService === true)
        {
            return true;
        }

        return false;
    }

    // Creates onboarded terminal on actual gateway
    public function callGatewayForOnboardingAsync($terminal)
    {
        $gateway = $terminal->getGateway();

        $gatewayProcessor = GatewayFactory::build($gateway);

        $request = $gatewayProcessor->getGatewayRequestArrayForCreation($terminal);

        $response = $this->app['gateway']->call($gateway, Constants::CREATE_TERMINAL, $request, $this->mode, $terminal);

        $gatewayProcessor->updateTerminalDetailsBasedOnCreationResponse($response, $terminal);
    }

    // Creates terminal for onboarding only in our database, not on actual gateway
    protected function performOnboardingAsync($merchant, $gatewayProcessor, $gatewayInput)
    {
        $gatewayProcessor->checkDbConstraints($gatewayInput, $merchant);

        $terminalData = $gatewayProcessor->getInputValue($gatewayInput, $merchant);

        return $gatewayProcessor->processTerminalData($terminalData, $merchant);
    }

    protected function performVerificationAsync($terminal)
    {
        $gateway = $terminal->getGateway();

        $gatewayProcessor = GatewayFactory::build($gateway);

        $lockResource = $gatewayProcessor->getLockResource($terminal, $gateway, []);

        $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

        $this->mutex->acquireAndRelease(
            $lockResource,
            function() use ($terminal, $terminalOnboardingDetail, $gateway, $gatewayProcessor)
            {
                $terminal->reload();

                $currentTimestamp = Carbon::now()->getTimestamp();

                // skip if it has already been processed
                if ((is_null($terminalOnboardingDetail->getVerifyAt())) or
                    ($terminalOnboardingDetail->getVerifyAt() > $currentTimestamp))
                    {
                        throw new Exception\LogicException('Terminal has already been processed');
                    }

                $request = $gatewayProcessor->getGatewayRequestArrayForVerification($terminal);

                $this->trace->info(
                    TraceCode::TERMINAL_ONBOARDING_VERIFY_REQUEST,
                    [
                        'terminal_id'                   => $terminal->getId(),
                        'gateway'                       => $gateway,
                        'merchant_id'                   => $terminal->merchant->getId(),
                        'terminal_onboarding_detail_id' => $terminalOnboardingDetail->getId(),
                    ]);

                try
                {
                    $response = $this->app['gateway']->call($gateway, Constants::VERIFY_TERMINAL, $request, $this->mode, $terminal);

                    $gatewayProcessor->updateTerminalDetailsBasedOnVerifyResponse($response, $terminal);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::TERMINAL_ONBOARDING_VERIFY_REQUEST_FAILURE_EXCEPTION,
                        $terminal->toArray()
                    );

                    throw $ex;
                }
            }, self::MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_ANOTHER_OPERATION_IN_PROGRESS
        );

        return $terminalOnboardingDetail->getStatus();
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
}
