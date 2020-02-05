<?php

namespace RZP\Models\Terminal\Onboarding;

use App;
use Config;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Gateway;
use RZP\Jobs\TerminalOnboardingCreateJob;
use RZP\Models\TerminalOnboardingDetail;
use RZP\Exception\BaseException;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Gateway\Terminal\Service as GatewayTerminalService;

class Service extends Base\Service
{
    protected $core;

    protected $mutex;

    const TERMINAL_IDS_FETCHED              = 'terminal_ids_fetched';

    const TERMINAL_IDS_QUEUED               = 'terminal_ids_queued';

    const CREATION_MUTEX_LOCK_TIMEOUT                =  180;

    const TERMINAL_ONBOARDING_CREATION_MUTEX_LOCK    = 'terminal_onboarding_creation_mutex_lock';

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
                'input'          => $input,
            ]);
        
        $this->verifySubMerchantShouldBeActivated($submerchant);

        $this->verifyPartnerTerminalOnboardingAccess();

        $onboardInput['gateway'] = Gateway::WORLDLINE;

        $onboardInput['gateway_input'] = $input;

        $onboardedTerminal = (new GatewayTerminalService)->onboardMerchantAsync($submerchant, $onboardInput);

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

        $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

        $terminal->setStatus(Terminal\Status::ACTIVATED);

        $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::ACTIVATED);

        $terminal->save();

        $terminalOnboardingDetail->save();

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

        if ($terminal->getStatus() !== Terminal\Status::ACTIVATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_ACTIVATED_TERMINALS_CAN_BE_DISABLED);
        }

        (new GatewayTerminalService)->callGatewayForTerminalEnableOrDisable($terminal, 'disable_terminal');

        $terminal = (new Terminal\Core)->toggle($terminal, false);

        $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

        $terminal->setStatus(Terminal\Status::DEACTIVATED);

        $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::DEACTIVATED);

        $terminal->save();

        $terminalOnboardingDetail->save();

        return $terminal->toArrayPublic();
    }

    public function fetchTerminals(array $input)
    {
        $this->verifyPartnerTerminalOnboardingAccess();

        $merchantId = $this->merchant->getId();

        $terminals = $this->repo->terminal->fetch($input, $merchantId);

        return $terminals->toArrayPublic();
    }

    // TerminalOnboarding Cron
    public function onboardTerminals($input)
    {
        $input['count'] = $input['count'] ?? 500;

        $resource = self::TERMINAL_ONBOARDING_CREATION_MUTEX_LOCK;

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($input)
            {
                // Will pick only those terminals whose terminalonboarding's status is created
                $createdTerminals = $this->repo->terminal->fetchTerminalsForOnboarding($input);

                $terminalIdsFetched = $createdTerminals->pluck(Terminal\Entity::ID)->all();

                $terminalIdsQueued = [];

                foreach ($createdTerminals as $terminal)
                {
                    try
                    {
                        $terminalId = $terminal->getId();

                        $this->trace->info(
                            TraceCode::TERMINAL_ONBOARDING_DISPATCHING_TO_QUEUE,
                            [
                                'terminal_id' => $terminalId,
                            ]
                        );                

                        // We are passing terminal_id to job because, we can't pass entity to job
                        TerminalOnboardingCreateJob::dispatch($this->mode, $terminal->getId());

                        array_push($terminalIdsQueued, $terminalId);

                        // Change status to 'queue', if dispatching succeeds
                        $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

                        $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::QUEUED);

                        $terminalOnboardingDetail->save();
                    }
                    catch (\Throwable $ex)
                    {
                        $this->trace->error(
                            TraceCode::TERMINAL_ONBOARDING_CREATE_QUEUING_FAILED,
                            [
                                'terminal'    => $terminal->toArrayPublic(),
                                'message'     => $ex->getMessage(),
                            ]
                        );

                        $message = '*ALERT*: Queing failed while onboarding terminal'; 

                        $this->app['slack']->queue(
                            $message,
                            $terminal->toArrayPublic(),
                            [
                                'channel'  => Config::get('slack.channels.tech_logs'),
                            ]
                        );
                    }
                }

                return [
                    self::TERMINAL_IDS_FETCHED => $terminalIdsFetched,
                    self::TERMINAL_IDS_QUEUED  => $terminalIdsQueued
                    ];

            }, self::CREATION_MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_ANOTHER_OPERATION_IN_PROGRESS
        );

        return $response;
    }

    public function verifyTerminals($input)
    {
        (new TerminalOnboardingDetail\Validator())->validateInput('verify_terminal', $input);

        $count = $input['count'] ?? 500;

        $terminals = $this->repo->terminal->fetchTerminalsForActivation($count);

        $response = (new GatewayTerminalService)->verifyTerminals($terminals);

        return $response;
    }

    /**
     * Below method is for precautionary api to change terminalonboarding status manually
     * if terminals get stuck in queued state forever
     */
    public function updateTerminalOnboardingStatus($input)
    {
        $response = ['updated_terminal_onboarding_ids'        => [],
                     'not_applicable_terminal_onboarding_ids' => []];

        foreach ($input as $terminalOnboardingDetailId)
        {
            try
            {
                $terminalOnboardingDetail = $this->repo->terminal_onboarding_detail->findOrFailPublic($terminalOnboardingDetailId);

                $terminal = $terminalOnboardingDetail->terminal;

                if ($terminalOnboardingDetail->getStatus() !== TerminalOnboardingDetail\Status::QUEUED)
                {
                    throw new Exception\LogicException(
                        'Only queued status can be manually updated',
                        null,
                        [
                            'input' => $input
                        ]);
                }

                $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::CREATED);
            
                $terminalOnboardingDetail->save();    

                array_push($response['updated_terminal_onboarding_ids'], $terminalOnboardingDetailId);
            }
            catch(\Throwable $ex)
            {
                array_push($response['not_applicable_terminal_onboarding_ids'], $terminalOnboardingDetailId);
            }
        }

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
}
