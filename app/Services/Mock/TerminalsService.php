<?php

namespace RZP\Services\Mock;

use RZP\Services\TerminalsService as BaseTerminalsService;

class TerminalsService extends BaseTerminalsService
{

    public function initiateOnboarding(string $merchantId, string $gateway): array
    {
        $response =
        [
            "links" => "https://www.sandbox.paypal.com/IN/merchantsignup/partner/onboardingentry?token=MWRiYWM1NDQtZWJlZC00M2VjLTlkMGMtZmM2MjRmYzc0N2M4ZW5NUGdxS2FUb0ozcTRRYmtSUkd5bXNtYnJiOUs0Y2ZYQU9JZURVL29SWT12MQ==&context_token=4909428984085513216"
        ];
        
        return $response;
    }

}
