<?php

namespace RZP\Models\Merchant\Fraud\HealthChecker;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Jobs\NotifyRas;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Http\Request\Requests;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use Illuminate\Cache\RedisStore;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Merchant\BusinessDetail as MerchantBusinessDetail;
use RZP\Models\MerchantRiskAlert\Service as MraService;
use RZP\Models\MerchantRiskAlert\Constants as MraConstants;
use RZP\Models\Merchant\Fraud\HealthChecker\Core as HealthCheckerCore;

class Job extends Base\Core
{
    /**
     * @var RedisStore
     */
    private $redis;

    public function __construct()
    {
        parent::__construct();

        $this->redis = $this->app['cache'];
    }

    public function performRiskCheck($merchantId, $eventType, $checkerType, $retryCount = 0)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        list($isNotLive, $websiteResults) = $this->noWebsiteOrAppLive($merchant, $checkerType);

        if ($isNotLive === false)
        {
            $this->trace->info(TraceCode::HEALTH_CHECKER_MERCHANT_REACHABLE, [
                'retry_count'               => $retryCount,
                'merchant_id'               => $merchant->getId(),
                Constants::CHECKER_TYPE     => $checkerType,
            ]);

            return;
        }

        $this->trace->info(TraceCode::HEALTH_CHECKER_MERCHANT_NOT_REACHABLE, [
            'retry_count'               => $retryCount,
            'merchant_id'               => $merchant->getId(),
            Constants::CHECKER_TYPE     => $checkerType,
        ]);

        $maxTriesReached = ($retryCount >= Constants::MAX_RISK_CHECK_RETRIES);

        $redisMap = Constants::eventAndCheckerTypeRedisMap($eventType, $checkerType);

        if ($maxTriesReached === true)
        {
            $this->notifyRas($merchant, $websiteResults, $eventType, $checkerType);

            $this->redis->connection()->hdel($redisMap, $merchantId);
        }
        else
        {
            $this->redis->connection()->hset($redisMap, $merchantId, now()->timestamp);
        }
    }

    private function shouldRemindMerchant(Merchant\Entity $merchant, Action\Entity $workflowAction, $checkerType)
    {
        if (is_null($workflowAction) === true)
        {
            return false;
        }

        $this->trace->info(TraceCode::HEALTH_CHECKER_REMINDER_INFERRED_WORKFLOW_ACTION, [
            'workflow_action_id' => $workflowAction->getId(),
            'merchant_id'        => $merchant->getId(),
        ]);

        $wfTriggeredByHealthChecker = in_array(Constants::RAS_TRIGGER_TAG[$checkerType], $workflowAction->tagNames(), true);

        if ($wfTriggeredByHealthChecker === false)
        {
            $this->trace->info(TraceCode::HEALTH_CHECKER_REMINDER_ABORT, [
                'workflow_action_id'    => $workflowAction->getId(),
                'merchant_id'           => $merchant->getId(),
                'reason'                => sprintf('workflow doesnt have %s tag', $checkerType),
                Constants::CHECKER_TYPE => $checkerType,
            ]);

            return false;
        }

        list($isNotLive, ) = $this->noWebsiteOrAppLive($merchant, $checkerType);
        if ($isNotLive === false)
        {
            $this->trace->info(TraceCode::HEALTH_CHECKER_REMINDER_ABORT, [
                'workflow_action_id'    => $workflowAction->getId(),
                'merchant_id'           => $merchant->getId(),
                'reason'                => 'atleast one website(or app) is live',
                Constants::CHECKER_TYPE => $checkerType,
            ]);

            $workflowAction->tag(Constants::MERCHANT_LIVE_TAG[$checkerType]);

            return false;
        }

        $hasReplied = in_array(Constants::MERCHANT_REPLIED_TAG, $workflowAction->tagNames(), true);

        $hasReminded = in_array(Constants::MERCHANT_REMINDED_TAG, $workflowAction->tagNames(), true);

        if (($hasReplied === true) || ($hasReminded === true))
        {
            $this->trace->info(TraceCode::HEALTH_CHECKER_REMINDER_ABORT, [
                'workflow_action_id'    => $workflowAction->getId(),
                'merchant_id'           => $merchant->getId(),
                'reason'                => 'has replied or reminded',
                'has_replied'           => $hasReplied,
                'has_reminded'          => $hasReminded,
                Constants::CHECKER_TYPE => $checkerType,
            ]);

            return false;
        }

        return true;
    }

    public function remindMerchantIfApplicable($merchantId, $checkerType)
    {
        $this->redis->connection()->hdel(Constants::REDIS_REMINDER_MAP_NAME[$checkerType], $merchantId);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(),
            Constants::MERCHANT_DETAIL_KEY,
            Permission\Name::MERCHANT_RISK_ALERT_FOH,
            Org\Entity::RAZORPAY_ORG_ID
        );

        $workflowAction = $workflowActions->first();

        if ($this->shouldRemindMerchant($merchant, $workflowAction, $checkerType) === true)
        {
            $this->remindMerchant($merchant, $workflowAction, $checkerType);

            $workflowAction->tag(Constants::MERCHANT_REMINDED_TAG);
        }
    }

    private function remindMerchant(Merchant\Entity $merchant, Action\Entity $wfAction, $checkerType)
    {
        foreach ($wfAction->tagNames() as $tagName)
        {
            $tagName = strtolower($tagName);

            if (starts_with($tagName, Constants::FD_TICKET_TAG_PREFIX[$checkerType]))
            {
                $ticketId = substr($tagName, strlen(Constants::FD_TICKET_TAG_PREFIX[$checkerType]));

                break;
            }
        }

        if (isset($ticketId) === true)
        {
            $this->trace->info(TraceCode::HEALTH_CHECKER_REMIND, [
                'merchant_id'           => $merchant->getId(),
                'fd_ticket_id'          => $ticketId,
                'workflow_action_id'    => $wfAction->getId(),
                Constants::CHECKER_TYPE => $checkerType
            ]);

            $mraService = new MraService();

            $mraService->sendNotificationsIfApplicable(
                $merchant,
                [
                    'fd_ticket_id'          => $ticketId,
                    Constants::CHECKER_TYPE => $checkerType,
                    'days_to_foh'           => MraConstants::HEALTH_CHECKER_NC_REMINDER_DAYS_TO_FOH,
                    'tags'                  => [
                        MraConstants::RAS_TRIGGER_REASON_KEY => $checkerType,
                    ],
                ],
                MraConstants::FOH_NC_NOTIFICATION);
        }
        else
        {
            $this->trace->info(TraceCode::HEALTH_CHECKER_REMIND_SKIPPED_BECAUSE_NO_FD_ID, [
                'workflow_action_id' => $wfAction->getId(),
                'wf_tags'            => $wfAction->tagNames(),
            ]);
        }
    }

    private function getWebsites(Merchant\Entity $merchant): array
    {
        $businessWebsite = $merchant->merchantDetail->getWebsite();

        $additionalWebsites = $merchant->merchantDetail->getAdditionalWebsites();

        $additionalWebsites[] = $businessWebsite;

        return $additionalWebsites;
    }

    private function getAppUrls(Merchant\Entity $merchant): array
    {
        $businessApp = $merchant->merchantBusinessDetail->getAppUrls();
        return array_flatten($businessApp);
    }

    private function getUrlsForChekerType($merchant, $checkerType): array
    {
        $urls = [];
        switch ($checkerType)
        {
            case Constants::WEBSITE_CHECKER:
                $urls = $this->getWebsites($merchant);
                break;
            case Constants::APP_CHECKER:
                $urls = $this->getAppUrls($merchant);
                break;
            default:
                break;
        }
        return $urls;
    }

    private function noWebsiteOrAppLive($merchant, $checkerType): array
    {
        $allUrls = $this->getUrlsForChekerType($merchant, $checkerType);
        return $this->healthCheckStatus($allUrls);
    }

    private function healthCheckStatus($websites): array
    {
        $websites = array_filter(array_unique($websites));

        $results = [];

        foreach ($websites as $website)
        {
            $singleResult = $this->isLive($website);

            if ($singleResult['result'] === Constants::RESULT_LIVE)
            {
                return [false, null];
            }

            $results []= $singleResult;
        }

        return [true, $results];
    }

    public function isLive(string $url): array
    {
        try
        {
            $response = Requests::request($url);
            $comment = sprintf(Constants::NO_EXCEPTION_COMMENT_FORMAT, $response->status_code);
            $result = Constants::STATUS_CODE_RESULT_MAP[$response->status_code] ?? Constants::RESULT_MANUAL_REVIEW;
        }
        catch (\Throwable $e)
        {
            $comment = sprintf(Constants::EXCEPTION_COMMENT_FORMAT, $e->getMessage());
            $result = Constants::RESULT_MANUAL_REVIEW;
        }

        return [
            'url'     => $url,
            'result'  => $result,
            'comment' => $comment,
        ];
    }

    private function notifyRas(Merchant\Entity $merchant, array $websiteResults, string $eventType, string $checkerType)
    {
        $merchantId = $merchant->getId();

        $this->trace->info(TraceCode::HEALTH_CHECKER_NOTIFY_RAS_INITIATED,
                           [
                               'merchant_id'            => $merchantId,
                               Constants::CHECKER_TYPE  => $checkerType
                           ]
        );

        try
        {
            // sending additionally,
            // just in case to track any issues with isMerchantEligibleForRiskCheck
            $merchantAppsExemptFromRiskCheck = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK);
            $category = $checkerType . '_checker';
            $entityType = ($checkerType === Constants::WEBSITE_CHECKER) ? 'transaction_websites' : 'transaction_apps';

            if ($checkerType === Constants::WEBSITE_CHECKER && $eventType === Constants::RISK_SCORE_CHECKER_EVENT)
            {
                $category = Constants::RISK_SCORE_CHECKER_EVENT;
                $eventType = Constants::TRANSACTION_DEDUPE;
            }

            $rasAlertRequest = [
                'merchant_id'     => $merchantId,
                'entity_type'     => $entityType,
                'entity_id'       => $merchantId,
                'category'        => $category,
                'source'          => 'api_service',
                'event_type'      => $eventType,
                'event_timestamp' => now()->timestamp,
                'data'            => [
                    'apps_exempt_risk_check' => ($merchantAppsExemptFromRiskCheck === true ? '1' : '0'),
                    'website_results'        => json_encode($websiteResults),
                ],
            ];

            NotifyRas::dispatch($this->mode, $rasAlertRequest);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::HEALTH_CHECKER_NOTIFY_RAS_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]
            );
        }
    }
}
