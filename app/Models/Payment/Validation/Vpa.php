<?php

namespace RZP\Models\Payment\Validation;

use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Metric;
use RZP\Models\Payment\Processor\Vpa as VpaTrait;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;


class Vpa extends Base
{
    use VpaTrait;

    public function processValidation($input)
    {
        // Check if we can process the validate account request for numeric/non-numeric VPA to UPS directly
        if ($this->shouldRouteValidateAccountRequestToUps())
        {
            try
            {
                return $this->app['upi.payments']->action(Payment\Action::VALIDATE_ACCOUNT_PROXY, $input, "");
            }
            catch(\Throwable $e)
            {
                $this->trace->error(
                    TraceCode::VALIDATE_ACCOUNT_UPS_REQUEST_FAILED,
                    [
                        'error_message' => $e->getMessage(),
                    ]);

                $this->trace->count(Metric::VALIDATE_ACCOUNT_UPS_REQUEST_FAILED_COUNT);

                // In case of errors, continue with the old flow
            }
        }

        $methodInput = [$input['entity'] => $input['value']];

        if(isset($input['_'][Payment\Analytics\Entity::LIBRARY]))
        {
            $methodInput[Payment\Analytics\Entity::LIBRARY] = $input['_'][Payment\Analytics\Entity::LIBRARY];
        }

        return $this->validateVpa($methodInput);
    }

    /**
     * Check whether to route validate account request to UPS
     * @return bool
     */
    protected function shouldRouteValidateAccountRequestToUps(): bool
    {
        $mode = $this->mode ?? Mode::LIVE;

        $merchantId = optional($this->merchant)->getId() ?? 'default';

        $variant = $this->app->razorx->getTreatment($merchantId, RazorxTreatment::VALIDATE_ACCOUNT_REARCH_UPS, $mode);

        return str_starts_with(strtolower($variant), 'on') === true;
    }
}
