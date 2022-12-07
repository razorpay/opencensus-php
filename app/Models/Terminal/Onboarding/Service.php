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
use RZP\Error\PublicErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Gateway;
use RZP\Exception\BaseException;
use RZP\Models\Mpan\Entity as MpanEntity;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Batch\Processor\UpiTerminalOnboarding;
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

        $terminal->setStatus(Terminal\Status::ACTIVATED);

        $terminal = (new Terminal\Core)->toggle($terminal, true);

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

        // proxy code
        $mode  = $this->mode ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($merchantId, "ROUTE_PROXY_TS_MERCHANT_TERMINAL_FETCH_ONBOARDING", $mode);

        if ($variantFlag === 'proxy')
        {
            $content = ["merchant_ids" => [$merchantId]];

            $content = array_merge($input, $content);

            $path = "v1/public/merchants/terminals";

            $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

            $resData = [];

            $resData["entity"] = "collection";

            $resData["count"] = count($response);

            $resData["items"] = $response;

            return $resData;
        }
        else
        {
            $terminals = $this->repo->terminal->fetch($input, $merchantId, null,false);

            return $terminals->toArrayPublic();
        }

    }

    public function initiateOnboarding($input)
    {
        $merchant = $this->merchant;

        $redactedInput = $this->getRedactedInput($input);

        $this->trace->info(
            TraceCode::INITIATE_TERMINAL_ONBOARDING_REQUEST,
            [
                'merchant_id'    => $merchant->getId(),
                'input'          => $redactedInput,
            ]);

        (new Validator)->validateInput(self::ONBOARDING_INPUT, $input);

        $identifiers = isset($input['identifiers']) ? $input['identifiers'] : null;

        $response = $this->app['terminals_service']->initiateOnboarding($merchant->getId(), $input['gateway'], $identifiers, null, [], $input);

        return $response;
    }

    public function postUpiTerminalOnboardingBulk($input)
    {
        $response = new Base\PublicCollection;

        foreach ($input as $row)
        {
            $rowOutput = $this->processUpiTerminalCreationBulkRow($row);

            $response->add($rowOutput);
        }

        return $response;
    }

    public function processUpiTerminalCreationBulkRow(array $row)
    {
        $this->trace->info(
            TraceCode::UPI_TERMINAL_ONBOARDING_REQUEST,
            [
                'row'   =>  $row
            ]);

        $result = [
            Constants::IDEMPOTENCY_KEY        => $row[Constants::IDEMPOTENCY_KEY],
            Constants::BATCH_SUCCESS          => false,
            Constants::BATCH_HTTP_STATUS_CODE => 500,
            Constants::TERMINAL_ID            => '',
            Constants::BATCH_ERROR => [
                Constants::BATCH_ERROR_CODE        => '',
                Constants::BATCH_ERROR_DESCRIPTION => '',
            ],
            Constants::VPA_WHITELISTED => '',
        ];

        $result = array_merge($result, $row);

        try
        {
            (new UpiTerminalOnboarding())->processEntry($row);

            $result[Constants::BATCH_SUCCESS] = true;
            $result[Constants::TERMINAL_ID]  =  $row[Constants::TERMINAL_ID];
            $result[Constants::BATCH_HTTP_STATUS_CODE] = 201;
            $result[Constants::VPA_WHITELISTED] = $row[Constants::VPA_WHITELISTED];
        }
        catch(BaseException $exception)
        {
            $result[Constants::BATCH_ERROR] = [
                Constants::BATCH_ERROR_DESCRIPTION => $exception->getMessage(),
                Constants::BATCH_ERROR_CODE => $exception->getData()['response']['error']['internal_error_code'],
            ];

            $result[Constants::BATCH_HTTP_STATUS_CODE] = $exception->getCode();
        }
        catch (\Throwable $throwable)
        {
            $result[Constants::BATCH_ERROR] = [
                Constants::BATCH_ERROR_DESCRIPTION => $throwable->getMessage(),
                Constants::BATCH_ERROR_CODE => PublicErrorCode::SERVER_ERROR,
            ];

            $result[Constants::BATCH_HTTP_STATUS_CODE] = $throwable->getCode();
        }

        return $result;
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

    protected function getRedactedInput(array $input):array
    {
        $redactedInput = $input;

        if(isset($redactedInput["secrets"]) === true)
        {
            unset($redactedInput["secrets"]);
        }

        return $redactedInput;
    }
}
