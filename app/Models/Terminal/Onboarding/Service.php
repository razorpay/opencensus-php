<?php

namespace RZP\Models\Terminal\Onboarding;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Core as TerminalCore;
use RZP\Models\Terminal\Onboarding\Processor\FreechargeTerminalOnboardingProcessor;
use RZP\Models\Terminal\Status;

class Service extends Base\Service
{
    protected $core;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        $submerchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::TERMINAL_ONBOARDING_REQUEST,
            [
                'merchant_id'    => $this->merchant->getId(),
                'partner_id'     => $this->app['basicauth']->getPartnerMerchantId(),
                'submerchant_id' => $submerchantId,
                'input'          => $input,
            ]);
    
        $this->verifyPartnerTerminalOnboardingAccess();
                      
        return (new FreechargeTerminalOnboardingProcessor)->process($input, $submerchantId);
    }

    public function enableTerminal(string $id)
    {
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

        if ($terminal->getStatus() !== Status::ACTIVATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_ACTIVATED_TERMINALS_CAN_BE_ENABLED);
        }

        $terminal = (new TerminalCore)->toggle($terminal, true);

        return $terminal->toArrayPublic();
    }

    public function disableTerminal(string $id)
    {
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

        $terminal = (new TerminalCore)->toggle($terminal, false);

        return $terminal->toArrayPublic();
    }

    public function fetchTerminals(array $input)
    {
        $this->verifyPartnerTerminalOnboardingAccess();

        $merchantId = $this->merchant->getId();

        $terminals = $this->repo->terminal->fetch($input, $merchantId);

        return $terminals->toArrayPublic();
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
}
