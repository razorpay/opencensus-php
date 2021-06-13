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

    public function performRiskCheck($merchantId, $retryCount = 0)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($this->noWebsiteLive($merchant) === false)
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

        if ($maxTriesReached === true)
        {
            $this->notifyRas($merchant);

            $this->redis->connection()->hdel(Constants::REDIS_RETRY_MAP_NAME, $merchantId);
        }
        else
        {
            $this->redis->connection()->hset(Constants::REDIS_RETRY_MAP_NAME, $merchantId, now()->timestamp);
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

        if ($this->noWebsiteLive($merchant) === false)
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

    private function noWebsiteLive(Merchant\Entity $merchant): bool
    {
        $businessWebsite = $merchant->merchantDetail->getAttribute(MerchantDetail\Entity::BUSINESS_WEBSITE);

        $additionalWebsites = $merchant->merchantDetail->getAttribute(MerchantDetail\Entity::ADDITIONAL_WEBSITES);

        $urls = json_decode($additionalWebsites);

        $urls []= $businessWebsite;

        foreach ($urls as $url)
        {
            if ($this->isLive($url) === true)
            {
                return false;
            }
        }

        return true;
    }

    private function isLive(string $url): bool
    {
        try
        {
            $response = Requests::request($url);

            $res = Constants::STATUS_CODE_RESULT_MAP[$response->status_code] === Constants::RESULT_LIVE;

            if ($res === false)
            {
                $this->trace->info(TraceCode::WEBSITE_CHECKER_URL_NOT_REACHABLE, [
                    'url'      => $url,
                    'response' => $response,
                ]);
            }

            return $res;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::WEBSITE_CHECKER_URL_NOT_REACHABLE, [
                'url' => $url,
            ]);

            return false;
        }
    }

    private function notifyRas(Merchant\Entity $merchant)
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
                'event_type'      => 'periodic_checker',
                'event_timestamp' => now()->timestamp,
                'data'            => [
                    'apps_exempt_risk_check' => ($merchantAppsExemptFromRiskCheck === true ? '1' : '0'),
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
