<?php

namespace RZP\Models\Merchant\Fraud\WebsiteChecker;

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
use RZP\Models\MerchantRiskAlert\Service as MraService;
use RZP\Models\MerchantRiskAlert\Constants as MraConstants;

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

    public function performRiskCheck($merchantId, $eventType, $retryCount = 0)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        list($isNotLive, $websiteResults) = $this->noWebsiteLive($merchant);

        if ($isNotLive === false)
        {
            $this->trace->info(TraceCode::WEBSITE_CHECKER_MERCHANT_REACHABLE, [
                'retry_count' => $retryCount,
                'merchant_id' => $merchant->getId(),
            ]);

            return;
        }

        $this->trace->info(TraceCode::WEBSITE_CHECKER_MERCHANT_NOT_REACHABLE, [
            'retry_count' => $retryCount,
            'merchant_id' => $merchant->getId(),
        ]);

        $maxTriesReached = ($retryCount >= Constants::MAX_RISK_CHECK_RETRIES);

        $redisMap = Constants::EVENT_TYPE_RETRY_REDIS_HASH_MAP[$eventType];

        if ($maxTriesReached === true)
        {
            $this->notifyRas($merchant, $websiteResults, $eventType);

            $this->redis->connection()->hdel($redisMap, $merchantId);
        }
        else
        {
            $this->redis->connection()->hset($redisMap, $merchantId, now()->timestamp);
        }
    }

    private function shouldRemindMerchant(Merchant\Entity $merchant, Action\Entity $workflowAction)
    {
        if (is_null($workflowAction) === true)
        {
            return false;
        }

        $this->trace->info(TraceCode::WEBSITE_CHECKER_REMINDER_INFERRED_WORKFLOW_ACTION, [
            'workflow_action_id' => $workflowAction->getId(),
            'merchant_id'        => $merchant->getId(),
        ]);

        // check if current workflow action triggered due to website_checker
        $wfTriggeredByWebChecker = in_array(Constants::RAS_TRIGGER_WEBCHECKER_WF_TAG, $workflowAction->tagNames(), true);

        if ($wfTriggeredByWebChecker === false)
        {
            $this->trace->info(TraceCode::WEBSITE_CHECKER_REMINDER_ABORT, [
                'workflow_action_id' => $workflowAction->getId(),
                'merchant_id'        => $merchant->getId(),
                'reason'             => 'workflow doesnt have website checker tag',
            ]);

            return false;
        }

        list($isNotLive, ) = $this->noWebsiteLive($merchant);
        if ($isNotLive === false)
        {
            $this->trace->info(TraceCode::WEBSITE_CHECKER_REMINDER_ABORT, [
                'workflow_action_id' => $workflowAction->getId(),
                'merchant_id'        => $merchant->getId(),
                'reason'             => 'atleast one website is live',
            ]);

            $workflowAction->tag(Constants::MERCHANT_WEBSITE_LIVE_TAG);

            return false;
        }

        $hasReplied = in_array(Constants::MERCHANT_REPLIED_TAG, $workflowAction->tagNames(), true);

        $hasReminded = in_array(Constants::MERCHANT_REMINDED_TAG, $workflowAction->tagNames(), true);

        if (($hasReplied === true) || ($hasReminded === true))
        {
            $this->trace->info(TraceCode::WEBSITE_CHECKER_REMINDER_ABORT, [
                'workflow_action_id' => $workflowAction->getId(),
                'merchant_id'        => $merchant->getId(),
                'reason'             => 'has replied or reminded',
                'has_replied'        => $hasReplied,
                'has_reminded'       => $hasReminded,
            ]);

            return false;
        }

        return true;
    }

    public function remindMerchantIfApplicable($merchantId)
    {
        $this->redis->connection()->hdel(Constants::REDIS_REMINDER_MAP_NAME, $merchantId);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(),
            Constants::MERCHANT_DETAIL_KEY,
            Permission\Name::MERCHANT_RISK_ALERT_FOH,
            Org\Entity::RAZORPAY_ORG_ID);

        $workflowAction = $workflowActions->first();

        if ($this->shouldRemindMerchant($merchant, $workflowAction) === true)
        {
            $this->remindMerchant($merchant, $workflowAction);

            $workflowAction->tag(Constants::MERCHANT_REMINDED_TAG);
        }
    }

    private function remindMerchant(Merchant\Entity $merchant, Action\Entity $wfAction)
    {
        foreach ($wfAction->tagNames() as $tagName)
        {
            $tagName = strtolower($tagName);

            if (starts_with($tagName, Constants::FD_TICKET_TAG_PREFIX))
            {
                $ticketId = substr($tagName, strlen(Constants::FD_TICKET_TAG_PREFIX));

                break;
            }
        }

        if (isset($ticketId) === true)
        {
            $this->trace->info(TraceCode::WEBSITE_CHECKER_REMIND, [
                'merchant_id'        => $merchant->getId(),
                'fd_ticket_id'       => $ticketId,
                'workflow_action_id' => $wfAction->getId(),
            ]);

            $mraService = new MraService();

            $mraService->sendNotificationsIfApplicable(
                $merchant,
                [
                    'fd_ticket_id' => $ticketId,
                    'days_to_foh'  => MraConstants::WEBSITE_CHECKER_NC_REMINDER_DAYS_TO_FOH,
                    'tags'         => [
                        MraConstants::RAS_TRIGGER_REASON_KEY => MraConstants::RAS_TRIGGER_REASON_WEBSITE_CHECKER,
                    ],
                ],
                MraConstants::FOH_NC_NOTIFICATION);
        }
        else
        {
            $this->trace->info(TraceCode::WEBSITE_CHECKER_REMIND_SKIPPED_BECAUSE_NO_FD_ID, [
                'workflow_action_id' => $wfAction->getId(),
                'wf_tags'            => $wfAction->tagNames(),
            ]);
        }
    }

    private function noWebsiteLive(Merchant\Entity $merchant): array
    {
        $businessWebsite = $merchant->merchantDetail->getWebsite();

        $additionalWebsites = $merchant->merchantDetail->getAdditionalWebsites();

        $additionalWebsites[] = $businessWebsite;

        $websites = array_filter(array_unique($additionalWebsites));

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

    private function notifyRas(Merchant\Entity $merchant, array $websiteResults, string $eventType)
    {
        $merchantId = $merchant->getId();

        $this->trace->info(TraceCode::WEBSITE_CHECKER_NOTIFY_RAS_INITIATED, ['merchant_id' => $merchantId]);

        try
        {
            // sending additionally,
            // just in case to track any issues with isMerchantEligibleForRiskCheck
            $merchantAppsExemptFromRiskCheck = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK);

            $rasAlertRequest = [
                'merchant_id'     => $merchantId,
                'entity_type'     => 'transaction_websites',
                'entity_id'       => $merchantId,
                'category'        => 'website_checker',
                'source'          => 'api_service',
                'event_type'      => $eventType,
                'event_timestamp' => now()->timestamp,
                'data'            => [
                    'apps_exempt_risk_check' => ($merchantAppsExemptFromRiskCheck === true ? '1' : '0'),
                    'website_results'        => $websiteResults,
                ],
            ];

            NotifyRas::dispatch($this->mode, $rasAlertRequest);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::WEBSITE_CHECKER_NOTIFY_RAS_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]
            );
        }
    }
}
