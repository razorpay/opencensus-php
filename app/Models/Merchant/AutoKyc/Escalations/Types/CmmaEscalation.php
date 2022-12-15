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
                $isExcludedMerchant = self::isMerchantExcludedForCaseCreation($merchant);

                $merchantId = $merchant->getId();

                if ($isExcludedMerchant === true) {

                    $this->trace->info(TraceCode::CMMA_ESCALATION_EXCLUDE, [
                        'merchant_id'   => $merchantId
                    ]);

                    continue;
                }

                $merchantName = $merchant->getName() ?? "undefined";

                $hasMerchantTransacted = (new \RZP\Models\Payment\Repository)->hasMerchantTransacted($merchantId);

                $cmmaExperimentEnabled = self::isCMMAEscalationExperimentEnabled($merchantId, Constants::CMMA_EXPERIMENT_ID_KEY);

                $processId =  $this->app['config']->get(Constants::CMMA_PROCESS_ID_KEY);

                $this->trace->info(TraceCode::CMMA_ESCALATION_ATTEMPT, [
                    'merchant_id'   => $merchantId,
                    '$cmmaExperimentEnabled' => $cmmaExperimentEnabled,
                    'milestone' => $type . '_' . $level
                ]);

                $cmmaExperimentForNewProcessEnabled = self::isCMMAEscalationExperimentEnabled($merchantId, Constants::CMMA_NEW_EXPERIMENT_ID_KEY);

                if ($cmmaExperimentForNewProcessEnabled === true)
                {
                    $processId =  $this->app['config']->get(Constants::CMMA_NEW_PROCESS_ID_KEY);
                }

                $this->trace->info(TraceCode::CMMA_ESCALATION_ATTEMPT, [
                    'merchant_id'   => $merchantId,
                    '$cmmaExperimentEnabled' => $cmmaExperimentForNewProcessEnabled,
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
                            "hasMerchantTransacted" => $hasMerchantTransacted ? "true" : "false",
                        ]
                    ];

                    $cmmaProxyController = new CmmaProxyController();

                    if ($type === AutoKycConstants::SOFT_LIMIT)
                    {
                        // hide soft limit breach behind experiment for now
                         $softLimitExperimentEnabled = self::isCMMAEscalationExperimentEnabled($merchantId,
                             Constants::CMMA_SOFT_LIMIT_EXPERIMENT_ID);

                         if ($softLimitExperimentEnabled === true) {
                             $cmmaProxyController->handleInternalCronProxyRequests(Constants::CMMA_ROUTE, $escalationPayload);
                         }
                    } elseif ($type === AutoKycConstants::HARD_LIMIT)
                    {
                        // call CMMA with a hard-limit payload
                        $escalationPayload['variables']['triggeredOn'] = Constants::CMMA_HARD_LIMIT_BREACH;
                        $cmmaProxyController->handleInternalCronProxyRequests(Constants::CMMA_ROUTE, $escalationPayload);
                    } elseif ($type === AutoKycConstants::AMP)
                    {
                        // hide AMP breaches behind an experiment
                        $ampExperimentEnabled = self::isCMMAEscalationExperimentEnabled($merchantId,
                            Constants::CMMA_AMP_EXPERIMENT_ID);

                        if ($ampExperimentEnabled === true) {

                            // call CMMA with an AMP payload
                            $escalationPayload['variables']['triggeredOn'] = AutoKycConstants::AMP;

                            $cmmaProxyController->handleInternalCronProxyRequests(Constants::CMMA_ROUTE, $escalationPayload);
                        }

                    } elseif ($type === AutoKycConstants::AUTO_KYC_FAILURE)
                    {
                        // hide AUTO_KYC_FAILURE breaches behind an experiment
                        $autoKycFailureExperimentEnabled = self::isCMMAEscalationExperimentEnabled($merchantId,
                            Constants::CMMA_AUTO_KYC_FAILURE_EXPERIMENT_ID);

                        if ($autoKycFailureExperimentEnabled === true) {

                            // call CMMA with Auto Kyc Failure payload
                            $escalationPayload['variables']['triggeredOn'] = Constants::AUTO_KYC_FAILURE_TRIGGER;

                            $cmmaProxyController->handleInternalCronProxyRequests(Constants::CMMA_ROUTE, $escalationPayload);
                        }

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

    protected function isCMMAEscalationExperimentEnabled($merchantId, $experimentId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
        ];
        $response = $this->app['splitzService']->evaluateRequest($properties);
        $variant = $response['response']['variant']['name'] ?? '';
        return $variant === Constants::ENABLE;
    }

    protected function isMerchantExcludedForCaseCreation($merchant): bool
    {
        $merchantId = $merchant->getId();

        $isPartner = $merchant->getPartnerType();

        // exclude the merchant if it is not a partner or a sub-merchant of a partner
        if (empty($isPartner) === true) { // merchant is not a partner

            // is not a sub-merchant of a partner
            $isSubMerchantOfPartner  = $this->repo->merchant_access_map->getByMerchantId($merchantId);

            if (empty($isSubMerchantOfPartner) == true) {
                return false;
            }

        }

        return true;
    }
}
