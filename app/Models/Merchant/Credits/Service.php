<?php

namespace RZP\Models\Merchant\Credits;

use Mail;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Credits;

class Service extends Base\Service
{
    public function grantCreditsForMerchant($mid, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $creditsLog = (new Credits\Core)->create($merchant, $input);

        return $creditsLog->toArrayPublic();
    }

    public function fetchCreditsLog($id)
    {
        // Raises Exception if record does not exist.
        $creditsLog = $this->repo->credits->findByPublicIdAndMerchant($id, $this->merchant);

        return $creditsLog->toArrayPublic();
    }

    /*
     * Update the CreditsLog, Presently We support update of credits only.
     *
     * @return array
     */
    public function updateCreditsLog($mid, $id, $input)
    {
        $id = Entity::verifyIdAndSilentlyStripSign($id);

        $creditsLog = $this->repo->credits->findByIdAndMerchantId($id, $mid);

        $creditsLog->getValidator()->validateInput('edit', $input);

        $credits = $input['value'];

        $creditsLog = (new Credits\Core)->updateCredits($creditsLog, $credits);

        return $creditsLog->toArrayPublic();
    }

    /**
     * Fetches multiple free credit logs based on query params.
     *
     * @return array
     */
    public function fetchMultiple($input)
    {
        $creditsLogs = $this->repo->credits->fetch($input, $this->merchant->getId());

        return $creditsLogs->toArrayPublic();
    }

    public function bulkCreateCredits(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_CREDITS_BULK_REQUEST, $input);

        $failedIds = [];

        foreach ($input as $merchantId => $creditInput)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $creditInput)
                {
                    $this->grantCreditsForMerchant($merchantId, $creditInput);
                });
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    \Razorpay\Trace\Logger::ERROR,
                    TraceCode::MERCHANT_CREDITS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $creditInput,
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        return [
            'total_count'  => count($input),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }
}
