<?php

namespace RZP\Models\Payment\Fraud;

use App;
use Carbon\Carbon;
use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use RZP\Constants\Shield;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\RiskMobileSignupHelper;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Fraud\Notifications\Config;
use RZP\Models\Payment\Fraud\Constants\Notification as Constants;

class Notify
{
    const SLACK_HEADLINE_TPL = "*%s (Rules) Triggered*\n\n*MID*: `<%s | %s>` flagged";

    const SLACK_RULE_TPL = "\n\n*Shield Id*: `<%s | %s>`\n*Shield Description*: %s";

    const SLACK_NOTIFY_CC_TPL = "\n\ncc: %s";

    const SLACK_USER_NOTIFY_TPL = '<@%s>';

    const SLACK_MESSAGE_USER_NAME = 'Transaction Risk';

    const SHIELD_RULE_ADMIN_DASHBOARD_URL = 'https://dashboard.razorpay.com/admin/entity/shield.rules/live/%s';

    protected $trace;

    protected $shieldSlackClient;

    protected $config;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->shieldSlackClient = $app['shield.slack'];

        $this->config = $app['config'];

        $this->redis = $app['redis'];
    }

    public function notifyMerchantIfNeeded(MerchantEntity $merchant, PaymentEntity $payment, string $errCode)
    {
        try
        {
            $fraudType = Constants::getFraudTypeByErrorCode($errCode);

            if (is_null($fraudType) === true)
            {
                return;
            }

            $isUnregisteredBusiness = $merchant->merchantDetail->isUnregisteredBusiness();

            if (($fraudType === Constants::DOMAIN_MISMATCH) and $isUnregisteredBusiness === true)
            {
                return;
            }

            if (RiskMobileSignupHelper::isEligibleForMobileSignUp($merchant) === true)
            {
                $fraudType = sprintf('%s_mobile_signup', $fraudType);
            }

            $settings = Constants::getSettingsForFraudType($fraudType);

            if (is_null($settings) === true)
            {
                return;
            }

            $handler = Constants::getHandlerForFraudType($fraudType);

            if (is_null($handler) === true)
            {
                return;
            }

            $config = new Config($fraudType, $settings);

            (new $handler($merchant, $payment, $config))->notifyMerchant();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::FRAUD_NOTIFICATION_FAILED);
        }
    }

    public function notifyOpsIfNeeded(MerchantEntity $merchant, array $triggeredRules)
    {
        try
        {
            $rulesGroupedByRuleCode = $this->getRulesGroupedByRuleCode($triggeredRules);

            if (empty($rulesGroupedByRuleCode) === true)
            {
                return;
            }

            foreach ($rulesGroupedByRuleCode as $ruleCode => $rulesInfo)
            {
                if ($this->isRuleCodeEligibleForOpsNotification($ruleCode) === false)
                {
                    continue;
                }

                $this->trace->info(
                    TraceCode::FRAUD_NOTIFICATION_TO_OPS_INITIATED,
                    [
                        'rule_code'   => $ruleCode,
                        'merchant_id' => $merchant->getId(),
                    ]);

                $this->sendSlackNotification($merchant, $ruleCode, $rulesInfo);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::FRAUD_NOTIFICATION_TO_OPS_FAILED);
        }
    }

    private function getRulesGroupedByRuleCode(array $triggeredRules): array
    {
        // Extract - block and review rules
        $rulesGroupedByRuleCode = [];

        if (isset($triggeredRules[Shield::ACTION_BLOCK]) === true)
        {
            foreach ($triggeredRules[Shield::ACTION_BLOCK] as $blockRule)
            {
                $ruleCode = strtolower($blockRule[Shield::RULE_CODE]);

                $ruleInfo = [
                    Shield::ID               => $blockRule[Shield::ID],
                    Shield::RULE_DESCRIPTION => $blockRule[Shield::RULE_DESCRIPTION],
                ];

                $rulesGroupedByRuleCode[$ruleCode][] = $ruleInfo;
            }
        }

        if (isset($triggeredRules[Shield::ACTION_REVIEW]) === true)
        {
            foreach ($triggeredRules[Shield::ACTION_REVIEW] as $reviewRule)
            {
                $ruleCode = strtolower($reviewRule[Shield::RULE_CODE]);

                $ruleInfo = [
                    Shield::ID               => $reviewRule[Shield::ID],
                    Shield::RULE_DESCRIPTION => $reviewRule[Shield::RULE_DESCRIPTION],
                ];

                $rulesGroupedByRuleCode[$ruleCode][] = $ruleInfo;
            }
        }

        return $rulesGroupedByRuleCode;
    }

    private function sendSlackNotification(MerchantEntity $merchant, string $ruleCode, array $rulesInfo)
    {
        $sendNotification = $this->canSendSlackNotification($merchant, $ruleCode);

        if ($sendNotification === false)
        {
            $this->trace->info(
                TraceCode::FRAUD_NOTIFICATION_TO_OPS_SKIPPED,
                [
                    'rule_code'   => $ruleCode,
                    'date'        => Carbon::now(Timezone::IST)->toFormattedDateString(),
                    'merchant_id' => $merchant->getId(),
                ]);

            return;
        }

        $message = $this->prepareSlackMessage($merchant, $ruleCode, $rulesInfo);

        $content = [
            'channel' => $this->config->get('slack.channels.risk'),
            'text'    => $message,
        ];

        $formattedResponse = $this->shieldSlackClient->sendRequest($content);

        $this->trace->info(
            TraceCode::FRAUD_NOTIFICATION_TO_OPS_COMPLETE,
            [
                'rule_code'   => $ruleCode,
                'date'        => Carbon::now(Timezone::IST)->toFormattedDateString(),
                'merchant_id' => $merchant->getId(),
                'response'    => $formattedResponse,
            ]);

        $this->trace->count(Metric::SHIELD_SLACK_ALERT_METRIC, [
            'rule_code'   => $ruleCode,
            'merchant_id' => $merchant->getId()
        ]);
    }

    private function prepareSlackMessage(MerchantEntity $merchant, string $ruleCode, array $rulesInfo): string
    {
        $merchantId = $merchant->getId();

        $header = sprintf(
            self::SLACK_HEADLINE_TPL,
            strtoupper($ruleCode),
            $merchant->getDashboardEntityLink(),
            $merchantId);

        $body = '';

        foreach ($rulesInfo as $ruleInfo)
        {
            $adminDashboardShieldRuleUrl = sprintf(self::SHIELD_RULE_ADMIN_DASHBOARD_URL, $ruleInfo[Shield::ID]);

            $ruleText = sprintf(
                self::SLACK_RULE_TPL,
                $adminDashboardShieldRuleUrl,
                $ruleInfo[Shield::ID],
                $ruleInfo[Shield::RULE_DESCRIPTION]);

            $body = $body . $ruleText;
        }

        $footer = '';

        $listOfUserIds = explode(',', $this->config->get('applications.shield.slack.cc_user_ids'));

        $listOfUserIds = array_unique(array_filter($listOfUserIds));

        if (empty($listOfUserIds) === false)
        {
            $slackCcUserList = array_map(
                function($userId)
                {
                    return sprintf(self::SLACK_USER_NOTIFY_TPL, $userId);
                },
                $listOfUserIds);

            $footer = sprintf(self::SLACK_NOTIFY_CC_TPL, implode(' ', $slackCcUserList));
        }

        $message = $header . $body . $footer;

        return $message;
    }

    private function canSendSlackNotification(MerchantEntity $merchant, string $ruleCode)
    {
        $notificationValidatorKey = sprintf(
            Constants::FRAUD_NOTIFICATION_REDIS_KEY,
            strtolower($ruleCode),
            Constants::SLACK,
            $merchant->getId());

        $currentTS = Carbon::now(Timezone::IST)->getTimestamp();
        $endOfDayTS = Carbon::now(Timezone::IST)->endOfDay()->getTimestamp();

        $expireInSecs = $endOfDayTS - $currentTS;

        $result = $this->redis->connection()->set($notificationValidatorKey, 1, 'ex', $expireInSecs, 'nx');

        $sendNotification = ($result === null) ? false: true;

        return $sendNotification;
    }

    private function isRuleCodeEligibleForOpsNotification(string $ruleCode)
    {
        $eligibleRuleCodes = explode(',', $this->config->get('applications.shield.slack.eligible_rule_codes'));

        $eligibleRuleCodes = array_unique(array_filter($eligibleRuleCodes));

        return in_array($ruleCode, $eligibleRuleCodes) === true;
    }
}
