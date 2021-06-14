<?php

namespace RZP\Models\Merchant;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;

class MerchantActionNotification
{
    protected $app;
    /**
     * @var Trace
     */
    protected $trace;
    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var Core
     */
    private $core;

    public function __construct()
    {
        $this->app   = App::getFacadeRoot();
        $this->repo  = $this->app['repo'];
        $this->core  = new Core();
        $this->mode  = ($this->mode ?? $this->app['rzp.mode']) ?? Mode::LIVE;
        $this->trace = $this->app['trace'];
    }

    public function updateNotificationTag($merchantId, array $input)
    {
        $action = $this->getNotifyActionFromInput($input);

        $this->app['trace']->info(
            TraceCode::MERCHANT_RISK_ACTIONS_CRON_TAG_TRIGGERED,
            [
                'merchant_id' => $merchantId,
                'action'      => $action,
                'input'       => $input,
            ]
        );

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        if(empty($action) === false)
        {
            if (isset(Constants::MERCHANT_RISK_ACTION_CRON_ADD_TAG_MAP[$action]) === true)
            {
                $this->core->appendTag($merchant, Constants::MERCHANT_RISK_ACTION_CRON_ADD_TAG_MAP[$action]);
            }
            else if (isset(Constants::MERCHANT_RISK_ACTION_CRON_REMOVE_TAG_MAP[$action]) === true)
            {
                $this->removeNotificationTag($merchant, $action);
            }
        }
    }

    public function removeNotificationTag(Entity $merchant, String $action)
    {
        $merchantId = $merchant->getId();

        if (isset(Constants::MERCHANT_RISK_ACTION_CRON_REMOVE_TAG_MAP[$action]) === true)
        {
            $this->core->deleteTag($merchantId, Constants::MERCHANT_RISK_ACTION_CRON_REMOVE_TAG_MAP[$action]);
        }

        if (isset(Constants::MERCHANT_RISK_ACTION_DASHBOARD_TAG[$action]) === true)
        {
            $this->core->deleteTag($merchantId, Constants::MERCHANT_RISK_ACTION_DASHBOARD_TAG[$action]);
        }
    }

    /**
     * Get the action on merchant to set/unset the tag
     * @param array $input
     * @return mixed|string|null
     */
    private function getNotifyActionFromInput(array $input)
    {
        if(isset($input['attributes']))
        {
            $attributes = $input['attributes'];
            if(isset($attributes['hold_funds']))
            {
                if ($attributes['hold_funds'] == 1) {
                    return Merchant\Action::HOLD_FUNDS;
                } else if ($attributes['hold_funds'] == 0) {
                    return Merchant\Action::RELEASE_FUNDS;
                }
            }
            return null;
        }

        $action = $input['action'];
        if(
            isset(Constants::MERCHANT_RISK_ACTION_CRON_ADD_TAG_MAP[$action]) === true ||
            isset(Constants::MERCHANT_RISK_ACTION_CRON_REMOVE_TAG_MAP[$action]) === true
        )
        {
            return $action;
        }

        return null;
    }

    public function handleMerchantActionNotificationCron()
    {
        $this->trace->info(TraceCode::MERCHANT_RISK_ACTIONS_NOTIFICATIONS_CRON_START);

        foreach (array_values(Constants::MERCHANT_RISK_ACTION_CRON_ADD_TAG_MAP) as $tag)
        {
            $merchants = $this->repo->merchant->fetchMerchantsWithTag($tag);

            $this->trace->info(TraceCode::MERCHANT_RISK_ACTIONS_NOTIFICATIONS_CRON,[
                'tag'            => $tag,
                'merchant_count' => count($merchants),
            ]);

            foreach ($merchants as $merchant)
            {
                try
                {
                    $this->sendMerchantActionNotifications($merchant, $tag);
                }
                catch (\Throwable $e)
                {
                    $this->app['trace']->traceException($e,
                        Trace::CRITICAL,
                        TraceCode::MERCHANT_RISK_ACTIONS_NOTIFICATIONS_CRON_FAILED
                    );
                }
            }
        }

        $this->trace->info(TraceCode::MERCHANT_RISK_ACTIONS_NOTIFICATIONS_CRON_END);
    }

    public function sendMerchantActionNotifications(Entity $merchant, String $cronTag)
    {
        $merchantId = $merchant->getId();

        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $this->app['trace']->info(
            TraceCode::MERCHANT_RISK_ACTIONS_SEND_NOTIFICATIONS,
            [
                'merchantId'  => $merchantId,
                'tag'         => $cronTag
            ]
        );

        $params = [
            'merchant_id'   => $merchantId,
            'business_name' => $merchantDetail->getBusinessName(),
            'merchant_name' => $merchant->getName(),
            'merchantName'  => $merchant->getName()
        ];

        $templates = Constants::MERCHANT_RISK_ACTIONS_CRON_TAG_TEMPLATE_MAP[$cronTag];

        $this->sendSms($merchant, $templates[Constants::SMS_TEMPLATE], $params);

        $this->sendWhatsappMessage($merchant, $templates[Constants::WHATSAPP_TEMPLATE_NAME],
            $templates[Constants::WHATSAPP_TEMPLATE], $params);

        $this->sendDashboardNotification($merchant, $templates[Constants::DASHBOARD_TEMPLATE_TAG]);

        //remove cron tag after sending notifications
        $this->core->deleteTag($merchantId, $cronTag);
    }

    private function sendSms($merchant, $smsTemplate, $params)
    {
        $receiver = $merchant->merchantDetail->getContactMobile();

        if (empty($receiver) === true)
        {
            return;
        }

        $payload = [
            'receiver' => $receiver,
            'template' => $smsTemplate,
            'source'   => Constants::SMS_SOURCE,
            'params'   => $params
        ];

        $this->app['raven']->sendSms($payload);
    }

    /**
     * Adding the tag to show the announcement on the Merchant Dashboard
     * @param $merchant
     * @param $templateTag
     */
    private function sendDashboardNotification($merchant, $templateTag)
    {
        if(empty($templateTag) === true)
        {
            return;
        }

        $this->core->appendTag($merchant, $templateTag);
    }

    private function sendWhatsappMessage($merchant, $whatsappTemplateName, $whatappTemplate, $params)
    {
        $receiver = $merchant->merchantDetail->getContactMobile();

        $whatsAppPayload = [
            'ownerId'       => $merchant->getId(),
            'ownerType'     => Constants::MERCHANT,
            'template_name' => $whatsappTemplateName,
            'params'        => $params
        ];

        (new Stork)->sendWhatsappMessage(
            $this->mode,
            $whatappTemplate,
            $receiver,
            $whatsAppPayload
        );
    }
}
