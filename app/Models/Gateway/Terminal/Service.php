<?php

namespace RZP\Models\Gateway\Terminal;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Gateway\Base\Terminal;
use RZP\Constants\Environment;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\TerminalOnboardingDetail;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Entity as Merchant;

const HITACHI_ONBOARDING_MOZART = 'hitachi_onboarding_mozart';
const MOZART_VARIANT_RESPONSE = 'mozart';

class Service extends Base\Service
{
    const MERCHANT_ONBOARD                          = 'merchant_onboard';
    const GATEWAY_INPUT                             = 'gateway_input';
    const TERMINAL                                  = 'terminal';
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

        $merchantDetail = $merchant->merchantDetail->toArray();

        $gatewayProcessor->addDefaultValueToMerchantDetailIfApplicable($merchantDetail);

        $gatewayProcessor->validateGatewayInput($gatewayInput, $merchantDetail);

        return $this->performOnboarding($merchant, $gatewayProcessor, $gatewayInput, $merchantDetail);
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

                $gatewayInput = $gatewayProcessor->getInputValue($gatewayInput, $merchant);

                $gatewayData = [
                    'gateway'          => $gateway,
                    'merchant'         => $merchant,
                    'merchant_details' => $merchantDetail,
                    'gateway_input'    => $gatewayInput,
                ];

                $variantFlag = $this->app->razorx->getTreatment($merchant['id'], HITACHI_ONBOARDING_MOZART, $this->mode);
                $shouldUseMozart = ($variantFlag === MOZART_VARIANT_RESPONSE ? true : false);

                try
                {
                    if ($shouldUseMozart) {
                        $terminalData = $this->app['gateway']->call('mozart',
                            Constants::MERCHANT_ONBOARD,
                            $gatewayData,
                            $this->mode);

                    } else {
                        $terminalData = $this->app['gateway']->call($gateway,
                            Constants::MERCHANT_ONBOARD,
                            $gatewayData,
                            $this->mode);
                    }

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

    protected function shouldCreateTerminal(bool $checkFeatureEnabled, $merchantId)
    {
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
                ($terminal->getCategory() === $category))
            {
                return true;
            }
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
                        throw new LogicException('Terminal has already been processed');
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
}
