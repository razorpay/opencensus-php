<?php
namespace RZP\Models\Terminal\Onboarding\Processor;
use RZP\Models\Base\Core;
use RZP\Jobs\TerminalOnboarding;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Terminal\Service as TerminalService;
use RZP\Models\Terminal\Status as TerminalStatus;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Onboarding\Constants as C;

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
        $createTerminalParams = $this->getCreateTerminalParams($input);

        $terminal = $this->terminalService->createTerminal($subMerchantId, $createTerminalParams);
        
        return $terminal->toArrayPublic();
    }

    /**
     * Transform partner terminal request to Razorpay terminal creation params
     */
    protected function getCreateTerminalParams(array $input)
    {   
        $createTerminalParams = [
                                    TerminalEntity::STATUS  => TerminalStatus::CREATED, 
                                    TerminalEntity::ENABLED => 0,
                                    TerminalEntity::GATEWAY => Gateway::ATOS,
                                ];

        if(isset($input[C::MPAN][C::MASTERCARD]))
        {
            $createTerminalParams[TerminalEntity::MC_MPAN] = $input[C::MPAN][C::MASTERCARD];
        }

        if(isset($input[C::MPAN][C::VISA]))
        {
            $createTerminalParams[TerminalEntity::VISA_MPAN] = $input[C::MPAN][C::VISA];
        }

        if(isset($input[C::MPAN][C::RUPAY]))
        {
            $createTerminalParams[TerminalEntity::RUPAY_MPAN] = $input[C::MPAN][C::RUPAY];
        }

        return $createTerminalParams;
    }
}
