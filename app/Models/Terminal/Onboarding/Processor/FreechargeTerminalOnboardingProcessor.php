<?php

namespace RZP\Models\Terminal\Onboarding\Processor;

use RZP\Models\Base\Core;
use RZP\Jobs\TerminalOnboarding;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Terminal\Service as TerminalService;
use RZP\Models\Terminal\Status as TerminalStatus;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Onboarding\Constants;
use RZP\Models\Terminal\Onboarding\Validator;

class FreechargeTerminalOnboardingProcessor extends Core
{
    /**
     * @var Terminal\Service
     */
    protected $terminalService;

    public function __construct()
    {
        $this->terminalService = new TerminalService;
    }

    public function process(array $input, string $subMerchantId)
    {
        (new Validator)->validateInput('freecharge_input', $input);                

        $createTerminalParams = $this->getCreateTerminalParams($input, $subMerchantId);

        $terminal = $this->terminalService->createTerminal($subMerchantId, $createTerminalParams);
        
        return $terminal->toArrayPublic();
    }

    /**
     * Transform partner terminal request to Razorpay terminal creation params
     */
    protected function getCreateTerminalParams(array $input, $subMerchantId)
    {   
        // TODO For now, we are storing gateway_merchant_id as subMerchantId so that it works for freecharge testing, we need to
        // change it when gateway contract is ready
        $createTerminalParams = [
            TerminalEntity::STATUS              => TerminalStatus::CREATED, 
            TerminalEntity::ENABLED             => 0,
            TerminalEntity::GATEWAY             => Gateway::ATOS,
            TerminalEntity::GATEWAY_MERCHANT_ID => $subMerchantId
        ];

        if (isset($input[Constants::MPAN][Constants::MASTERCARD]) === true)
        {
            $createTerminalParams[TerminalEntity::MC_MPAN] = $input[Constants::MPAN][Constants::MASTERCARD];
        }

        if (isset($input[Constants::MPAN][Constants::VISA]) === true)
        {
            $createTerminalParams[TerminalEntity::VISA_MPAN] = $input[Constants::MPAN][Constants::VISA];
        }

        if (isset($input[Constants::MPAN][Constants::RUPAY]) === true)
        {
            $createTerminalParams[TerminalEntity::RUPAY_MPAN] = $input[Constants::MPAN][Constants::RUPAY];
        }

        return $createTerminalParams;
    }
}
