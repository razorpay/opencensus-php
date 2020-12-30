<?php

namespace RZP\Models\Terminal\Onboarding;

use App;
use Config;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal\Status;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Gateway;
use RZP\Exception\BaseException;
use RZP\Models\Mpan\Entity as MpanEntity;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Gateway\Terminal\Service as GatewayTerminalService;

class Service extends Base\Service
{
    protected $core;

    protected $mutex;

    const ONBOARDING_INPUT    = 'onboarding_input';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        $submerchant = $this->merchant;

        $this->trace->info(
            TraceCode::TERMINAL_ONBOARDING_REQUEST,
            [
                'merchant_id'    => $this->merchant->getId(),
                'partner_id'     => $this->app['basicauth']->getPartnerMerchantId(),
                'submerchant_id' => $submerchant->getId(),
                'input'          => $this->getCreateTraceInput($input),
            ]);

        $this->verifySubMerchantShouldBeActivated($submerchant);

        $this->verifyPartnerTerminalOnboardingAccess();

        $onboardInput['gateway'] = Gateway::WORLDLINE;

        $onboardInput['gateway_input'] = $input;

        $onboardedTerminal = (new GatewayTerminalService)->onboardMerchant($submerchant, $onboardInput, false);

        return $onboardedTerminal->toArrayPublic();
    }

    public function enableTerminal(string $id)
    {
        TerminalEntity::verifyIdAndStripSign($id);

        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::TERMINAL_ENABLE_REQUEST,
            [
                'merchant_id' => $merchantId,
                'terminal_id' => $id,
                'partner_id'  => $this->app['basicauth']->getPartnerMerchantId()
            ]);

        $this->verifyPartnerTerminalOnboardingAccess();

        $terminal = $this->repo->terminal->findByIdAndMerchantId($id, $merchantId);

        if ($terminal->getStatus() !== Terminal\Status::DEACTIVATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_DEACTIVATED_TERMINALS_CAN_BE_ENABLED);
        }

        (new GatewayTerminalService)->callGatewayForTerminalEnableOrDisable($terminal, 'enable_terminal');

        $terminal = (new Terminal\Core)->toggle($terminal, true);

        $terminal->setStatus(Terminal\Status::ACTIVATED);

        $terminal->save();

        return $terminal->toArrayPublic();
    }

    public function disableTerminal(string $id)
    {
        TerminalEntity::verifyIdAndStripSign($id);

        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::TERMINAL_DISABLE_REQUEST,
            [
                'merchant_id' => $merchantId,
                'terminal_id' => $id,
                'partner_id'  => $this->app['basicauth']->getPartnerMerchantId()
            ]);

        $this->verifyPartnerTerminalOnboardingAccess();

        $terminal = $this->repo->terminal->findByIdAndMerchantId($id, $merchantId);

        $terminal = (new Terminal\Core)->disableTerminal($terminal);

        return $terminal->toArrayPublic();
    }

    public function fetchTerminals(array $input)
    {
        $this->verifyPartnerTerminalOnboardingAccess();

        $merchantId = $this->merchant->getId();

        $terminals = $this->repo->terminal->fetch($input, $merchantId);

        $data = $terminals->toArrayPublic();

        // proxy code
        $mode  = $this->mode ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($merchantId, "ROUTE_PROXY_TS_MERCHANT_TERMINAL_FETCH_ONBOARDING", $mode);

        if ($variantFlag === 'proxy')
        {
            $content = ["merchant_ids" => [$merchantId]];

            $content = array_merge($input, $content);

            $path = "v1/public/merchants/terminals";

            $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

            $dataToCompare = $data["items"];

            if ((new Terminal\Service())->compareArrayOfTerminalArrays($dataToCompare, $response) === false)
            {
                $traceData = ["content" => $content];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_FETCH_ONBOARDING_COMPARISON_FAILED, $traceData);
            }

            return $response;
        }

        return $data;
    }

    public function initiateOnboarding($input)
    {
        $merchant = $this->merchant;

        $this->trace->info(
            TraceCode::INITIATE_TERMINAL_ONBOARDING_REQUEST,
            [
                'merchant_id'    => $merchant->getId(),
                'input'          => $input,
            ]);

        (new Validator)->validateInput(self::ONBOARDING_INPUT, $input);

        $response = $this->app['terminals_service']->initiateOnboarding($merchant->getId(), $input['gateway'], null, [], $input);

        return $response;
    }

    public function processTerminalOnboardCallback(string $gateway, array $input)
    {
        $this->app['trace']->info(TraceCode::TERMINAL_ONBOARDING_CALLBACK_RECEIVED, $input);

        $response = $this->app['terminals_service']->terminalOnboardCallback($gateway, $input);

        return $response;
    }

    protected function verifyPartnerTerminalOnboardingAccess()
    {
        if ($this->isTerminalOnboardinglEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_DISABLED);
        }
    }

    protected function isTerminalOnboardinglEnabled()
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if ($partnerMerchantId !== null)
        {
            $partnerMerchant = $this->repo->merchant->findOrFailPublic($partnerMerchantId);

            return $partnerMerchant->isTerminalOnboardingEnabled();
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER);
    }

    protected function verifySubMerchantShouldBeActivated(Merchant\Entity $submerchant)
    {
        if ($submerchant->isActivated() === true)
        {
            return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
    }

    public function getCreateTraceInput(array $input)
    {
        foreach([Constants::MASTERCARD, Constants::VISA, Constants::RUPAY] as $network)
        {
            if (isset($input['mpan'][$network]) === true)
            {
                $input['mpan'][$network] = (new MpanEntity)->getMaskedMpan($input['mpan'][$network]);
            }
        }

        return $input;
    }

}
