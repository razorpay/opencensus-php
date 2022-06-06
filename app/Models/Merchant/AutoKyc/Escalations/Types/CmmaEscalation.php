<?php

namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;

use App;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;
use RZP\Http\Controllers\CmmaProxyController;
use RZP\Models\Merchant\Escalations\Constants;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as AutoKycConstants;
class CmmaEscalation
{
    /**
     * The application instance.
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];
    }

    // trigger CMMA soft level and hard level breach
    public function triggerCMMAEscalation($merchants, $type, $level)
    {
        foreach ($merchants as $merchant)
        {
            try
            {
                $merchantId = $merchant->getId();

                $merchantName = $merchant->getName() ?? "undefined";

                $cmmaExperimentEnabled = self::isCMMAEscalationExperimentEnabled($merchantId);

                $processId =  $this->app['config']->get(Constants::CMMA_PROCESS_ID_KEY);

                $this->trace->info(TraceCode::CMMA_ESCALATION_ATTEMPT, [
                    'merchant_id'   => $merchantId,
                    '$cmmaExperimentEnabled' => $cmmaExperimentEnabled,
                    'milestone' => $type . '_' . $level
                ]);

                if ($cmmaExperimentEnabled === true)
                {
                    $escalationPayload = [
                        'process_id'   => $processId,
                        'variables' => [
                            'caseType' => Constants::ACTIVATION,
                            "merchantId" => $merchantId,
                            "triggeredOn" => Constants::CMMA_SOFT_LIMIT_BREACH,
                            "merchantName" => $merchantName,
                        ]
                    ];

                    $cmmaProxyController = new CmmaProxyController();

                    if ($type === AutoKycConstants::SOFT_LIMIT)
                    {
                        $cmmaProxyController->handleInternalCronProxyRequests(Constants::CMMA_ROUTE, $escalationPayload);
                    } elseif ($type === AutoKycConstants::HARD_LIMIT)
                    {
                        // call CMMA with a hard-limit payload
                        $escalationPayload['variables']['triggeredOn'] = Constants::CMMA_HARD_LIMIT_BREACH;
                        $cmmaProxyController->handleInternalCronProxyRequests(Constants::CMMA_ROUTE, $escalationPayload);
                    }
                }
            } catch (\Throwable $err) // Exception in this flow should not affect the primary escalation flow
            {
                $this->trace->error(TraceCode::CMMA_ESCALATION_ATTEMPT_FAILURE, [
                    'merchant_id'   => $merchant->getId(),
                    'error' => $err
                ]);
            }
        }

    }

    protected function isCMMAEscalationExperimentEnabled($merchantId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get(Constants::CMMA_EXPERIMENT_ID_KEY),
        ];
        $response = $this->app['splitzService']->evaluateRequest($properties);
        $variant = $response['response']['variant']['name'] ?? '';
        return $variant === Constants::ENABLE;
    }
}
