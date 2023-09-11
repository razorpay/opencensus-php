<?php

namespace RZP\Models\Payment\Fraud\Notifications;

use App;
use Mail;

use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\lib\TemplateEngine;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\RiskMobileSignupHelper;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Fraud\Constants\Notification as Constants;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;
use RZP\Models\Merchant\FreshdeskTicket\TicketStatus as FreshdeskTicketStatus;

abstract class Base
{
    /**
     * @var MerchantEntity
     */
    protected $merchant;

    /**
     * @var PaymentEntity
     */
    protected $payment;

    protected $app;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var Trace
     */
    protected $trace;

    protected $config;

    protected $merchantFreshdeskTicket;

    /**
     * @param MerchantEntity $merchant
     * @param PaymentEntity $payment
     * @param Config $config
     */
    public function __construct(MerchantEntity $merchant, PaymentEntity $payment, Config $config)
    {
        $this->app = App::getFacadeRoot();
        $this->mode = $this->app['rzp.mode'];
        $this->trace = $this->app['trace'];

        $this->merchant = $merchant;
        $this->payment = $payment;

        $this->config = $config;
    }

    public function notifyMerchant()
    {
        if ($this->shouldNotify(Constants::FRESHDESK_TICKET) === true)
        {
            $this->createFreshdeskTicketForMerchant();
        }

        if ($this->shouldNotify(Constants::EMAIL) === true)
        {
            $this->emailMerchant();
        }

        if ($this->shouldNotify(Constants::SMS) === true)
        {
            $this->smsMerchant();
        }

        if ($this->shouldNotify(Constants::WHATSAPP) === true)
        {
            $this->sendWhatsappToMerchant();
        }
    }

    private function createFreshdeskTicketForMerchant()
    {
        [$mailBody, $mailSubjectTemplate, $emailPayload, $requestParams] = $this->getFreshdeskTicketData();

        $experimentEnabled = $this->isSplitzExperimentEnable(
            $requestParams[FreshdeskConstants::CF_MERCHANT_ID],
            Constants::URL_MISMATCH_REPLY_ON_TICKET_ID_KEY,
            Constants::VARIANT_ENABLE
        );

        if ($experimentEnabled === true)
        {
            $latestTicket = $this->fetchFreshdeskExistingTicketsForUrlMismatch($emailPayload, $requestParams);

            $createNewTicket = $this->checkIfCreateNewFreshdeskTicket($latestTicket);

            if ($createNewTicket === false)
            {
                return $this->freshdeskNotificationReplyOnExistingFreshdeskTicket($mailBody, $emailPayload, $requestParams, $latestTicket);
            }
            else
            {
                return $this->freshdeskNotificationCreateNewFreshdeskTicket($mailBody, $mailSubjectTemplate, $emailPayload, $requestParams);
            }
        }
        else
        {
            return $this->freshdeskNotificationCreateNewFreshdeskTicket($mailBody, $mailSubjectTemplate, $emailPayload, $requestParams);
        }
    }

    private function freshdeskNotificationReplyOnExistingFreshdeskTicket($mailBody, $emailPayload, $requestParams, $latestTicket)
    {
        $replyPayload = $this->getFdRequestPayloadForTicketReply($mailBody, $emailPayload, $requestParams);

        $response = $this->app[FreshdeskConstants::FRESHDESK_CLIENT]->postTicketReply($latestTicket[FreshdeskConstants::ID], $replyPayload, FreshdeskConstants::URLIND);

        (new FreshDeskService())->validateTicketResponse($response, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);

        $this->trace->info(TraceCode::URL_MISMATCH_REPLY_ON_EXISTING_TICKET_SUCCESS, [
            'merchant_id'    => $requestParams[FreshdeskConstants::CF_MERCHANT_ID],
            'ticket_id'      => $latestTicket[FreshdeskConstants::ID],
            'website_domain' => $requestParams[FreshdeskConstants::CF_WEBSITE_URL],
            'channel'        => Constants::FRESHDESK_TICKET
        ]);

        $this->trace->count(Metrics::FRAUD_NOTIFICATION_REPLY_ON_EXISTING_TICKET, [
            Constants::CHANNEL => Constants::FRESHDESK_TICKET
        ]);

        return $response;
    }

    private function freshdeskNotificationCreateNewFreshdeskTicket($mailBody, $mailSubjectTemplate, $emailPayload, $requestParams)
    {
        $mailSubject = sprintf($mailSubjectTemplate, $requestParams[FreshdeskConstants::CF_MERCHANT_ID]);

        $response = (new RiskMobileSignupHelper())->createFdTicket($this->merchant,
            $mailBody,
            $mailSubject,
            $emailPayload,
            $requestParams);

        (new FreshDeskService())->validateTicketResponse($response, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);

        $this->trace->info(TraceCode::URL_MISMATCH_NEW_TICKET_CREATED_SUCCESS, [
            'merchant_id'    => $requestParams[FreshdeskConstants::CF_MERCHANT_ID],
            'website_domain' => $requestParams[FreshdeskConstants::CF_WEBSITE_URL],
            'channel'        => Constants::FRESHDESK_TICKET
        ]);

        $this->trace->count(Metrics::FRAUD_NOTIFICATION_NEW_TICKET_CREATED, [
            Constants::CHANNEL => Constants::FRESHDESK_TICKET
        ]);

        return $response;
    }

    private function sendWhatsappToMerchant()
    {
        $whatsAppPayload = [
            'ownerId'       => $this->merchant->getId(),
            'ownerType'     => 'merchant',
            'template_name' => $this->config->getWhatsappTemplateName(),
            'params'        => $this->getWhatsappData(),
        ];

        (new Stork)->sendWhatsappMessage(
            $this->mode,
            $this->config->getWhatsappTemplate(),
            $this->merchant->merchantDetail->getContactMobile(),
            $whatsAppPayload
        );
    }

    private function emailMerchant()
    {
        $data = $this->getEmailData();

        $experimentEnabled = $this->isSplitzExperimentEnable(
            $this->merchant->getId(),
            Constants::URL_MISMATCH_REPLY_ON_TICKET_ID_KEY,
            Constants::VARIANT_ENABLE
        );

        if ($experimentEnabled === true)
        {
            $latestTicket = $this->fetchFreshdeskExistingTicketsForUrlMismatch($data);

            $createNewTicket = $this->checkIfCreateNewFreshdeskTicket($latestTicket);

            if ($createNewTicket === false)
            {
                return $this->emailNotificationReplyOnExistingFreshdeskTicket($data, $latestTicket, $requestParams);
            }
            else
            {
                return $this->emailNotificationCreateNewFreshdeskTicket($data);
            }
        }
        else
        {
            return $this->emailNotificationCreateNewFreshdeskTicket($data);
        }
    }

    private function emailNotificationReplyOnExistingFreshdeskTicket($data, $latestTicket, $requestParams)
    {
        $replyPayload = [
            FreshdeskConstants::BODY      => $data[FreshdeskConstants::DESCRIPTION],
            FreshdeskConstants::CC_EMAILS => $data[FreshdeskConstants::CC_EMAILS],
        ];

        $response =  $this->app[FreshdeskConstants::FRESHDESK_CLIENT]->postTicketReply($latestTicket[FreshdeskConstants::ID],
            $replyPayload,
            FreshdeskConstants::URLIND);

        (new FreshDeskService())->validateTicketResponse($response, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);

        $this->trace->info(TraceCode::URL_MISMATCH_REPLY_ON_EXISTING_TICKET_SUCCESS, [
            'merchant_id'    => $data[FreshdeskConstants::CUSTOM_FIELDS][FreshdeskConstants::CF_MERCHANT_ID],
            'ticket_id'      => $latestTicket[FreshdeskConstants::ID],
            'website_domain' => $data[FreshdeskConstants::CUSTOM_FIELDS][FreshdeskConstants::CF_WEBSITE_URL],
            'channel'        => Constants::EMAIL
        ]);

        $this->trace->count(Metrics::FRAUD_NOTIFICATION_REPLY_ON_EXISTING_TICKET, [
            Constants::CHANNEL => Constants::EMAIL
        ]);

        return $response;
    }

    private function emailNotificationCreateNewFreshdeskTicket($data)
    {
        if (is_null($data) === false)
        {
            $mailer = $this->config->getEmailHandler();

            $provider = $this->config->getEmailProvider();

            $this->trace->info(
                TraceCode::FRAUD_NOTIFICATION_EMAIL_SENDING,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'payment_id'  => $this->payment->getId(),
                    'fraud_type'  => $this->config->getFraudType(),
                ]);

            if ($provider === Constants::FRESHDESK)
            {
                $response = $this->app[FreshdeskConstants::FRESHDESK_CLIENT]->sendOutboundEmail(
                    $data, FreshdeskConstants::URLIND);

                (new FreshDeskService())->validateTicketResponse($response, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);

                $this->trace->info(TraceCode::URL_MISMATCH_NEW_TICKET_CREATED_SUCCESS, [
                    'merchant_id'    => $requestParams[FreshdeskConstants::CF_MERCHANT_ID],
                    'website_domain' => $requestParams[FreshdeskConstants::CF_WEBSITE_URL],
                    'ticket_id'      => $response[FreshdeskConstants::ID],
                    'channel'        => Constants::EMAIL
                ]);

                $this->trace->count(Metrics::FRAUD_NOTIFICATION_NEW_TICKET_CREATED, [
                    Constants::CHANNEL => Constants::EMAIL
                ]);

                return $response;
            }
            else
            {
                Mail::queue(new $mailer($data));
            }
        }
    }

    private function smsMerchant()
    {
        $data = $this->getSmsData();

        if (is_null($data) === false)
        {
            $data['template'] = $this->config->getSmsTemplate();

            $this->trace->info(
                TraceCode::FRAUD_NOTIFICATION_SMS_SENDING,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'payment_id'  => $this->payment->getId(),
                    'fraud_type'  => $this->config->getFraudType(),
                ]);

            $this->app->raven->sendSms($data);
        }
    }

    private function shouldNotify(string $channel): bool
    {
        $notifyIntervalInSecs = 0;

        $config = $this->config;

        $fraudType = $config->getFraudType();

        switch ($channel)
        {
            case Constants::EMAIL:
                if ($config->isEmailEnabled() === false)
                {
                    return false;
                }

                if ($config->emailInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getEmailInterval();

                break;

            case Constants::SMS:
                if ($config->isSmsEnabled() === false)
                {
                    return false;
                }

                if ($config->smsInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getSmsInterval();

                break;

            case Constants::WHATSAPP:
                if ($config->isWhatsappEnabled() === false)
                {
                    return false;
                }

                if ($config->whatsappInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getWhatsappInterval();

                break;

            case Constants::FRESHDESK_TICKET:
                if ($config->isFreshdeskTicketEnabled() === false)
                {
                    return false;
                }

                if ($config->freshdeskTicketInstantly() === true)
                {
                    return true;
                }

                $notifyIntervalInSecs = $config->getFreshdeskTicketInterval();

                break;

            default:
                return false;
        }

        $redis = $this->app['redis']->connection();

        $key = sprintf(Constants::FRAUD_NOTIFICATION_REDIS_KEY, $fraudType, $channel, $this->merchant->getId());

        $redisRes = $redis->set($key, 1, 'ex', $notifyIntervalInSecs, 'nx');

        if ($redisRes === null)
        {
            // if this key was set in last notifyIntervalInSecs seconds, redis will return null. In that case do not trigger event.
            return false;
        }

        return true;
    }

    private function checkIfCreateNewFreshdeskTicket($latestTicket) : bool
    {
        $createNewTicket = true;

        if (empty($latestTicket) === false)
        {
            $ticketStatus = (new FreshdeskService)->getTicketStatusForCustomer($latestTicket);

            $createNewTicket = (in_array($ticketStatus, FreshdeskTicketStatus::FRESHDESK_TICKET_STATUS_FOR_NEW_TICKET_CREATION)) ? true : false;
        }

        return $createNewTicket;
    }

    private function fetchFreshdeskExistingTicketsForUrlMismatch(array $emailPayload, array $requestParams = []) : array
    {
        $latestTicket = [];

        $filters = [
            FreshdeskConstants::CF_MERCHANT_ID  => $requestParams[FreshdeskConstants::CF_MERCHANT_ID] ?? $emailPayload[FreshdeskConstants::CUSTOM_FIELDS][FreshdeskConstants::CF_MERCHANT_ID],
            FreshdeskConstants::CF_WEBSITE_URL  => $requestParams[FreshdeskConstants::CF_WEBSITE_URL] ?? $emailPayload[FreshdeskConstants::CUSTOM_FIELDS][FreshdeskConstants::CF_WEBSITE_URL],
            FreshdeskConstants::CF_SUBCATEGORY  => FreshdeskConstants::FD_SUB_CATEGORY_WEBSITE_MISMATCH,
        ];

        $queryString = $this->buildQueryStringForGetFreshdeskTickets($filters);

        $queryParams = [
            FreshdeskConstants::QUERY => $queryString,
            FreshdeskConstants::PAGE => 1,
        ];

        $urlMismatchTickets = $this->app[FreshdeskConstants::FRESHDESK_CLIENT]->getTickets($queryParams, FreshdeskConstants::URLIND);

        if ((isset($urlMismatchTickets[FreshdeskConstants::TOTAL]) === true) and
            (isset($urlMismatchTickets[FreshdeskConstants::RESULTS]) === true) and
            ($urlMismatchTickets[FreshdeskConstants::TOTAL] !== 0))
        {
            // for sanity
            (new FreshdeskService())->sortTicketsInDescendingOrderOfCreatedAt($urlMismatchTickets[FreshdeskConstants::RESULTS]);

            $latestTicket = $urlMismatchTickets[FreshdeskConstants::RESULTS][0];

            $this->trace->info(TraceCode::URL_MISMATCH_FETCH_EXISTING_TICKET_DETAILS, [
                'ticket_id' => $latestTicket[FreshdeskConstants::ID],
                'status'    => $latestTicket[FreshdeskConstants::STATUS],
            ]);
        }

        return $latestTicket;
    }

    private function getFdRequestPayloadForTicketReply($mailTemplate, $requestPayload, $requestParams) : array
    {
        $data = array_merge($requestPayload, $requestParams);

        $mailBody = \View::make($mailTemplate)->with($data)->render();

        $ticketPayload = [
            FreshdeskConstants::BODY => $mailBody,
            FreshdeskConstants::CC_EMAILS => $requestParams[FreshdeskConstants::CC_EMAILS],
        ];

        return $ticketPayload;
    }

    private function buildQueryStringForGetFreshdeskTickets($input): string
    {
        $status = $input[FreshdeskConstants::STATUS] ?? null;

        $customStringsListForQuery = FreshdeskConstants::CUSTOM_FIELDS_LIST_FOR_FETCH_TICKETS;

        $customStringsPresent = (new FreshDeskService())->getCustomFieldsFromInput($customStringsListForQuery, $input);

        // Adding status filter if necessary
        if (empty($status) === false)
        {
            $queryString .= '"(';

            if (is_array($status) === true)
            {
                foreach ($status as $index => $value)
                {
                    $queryString .= ($index === 0) ? 'status:' . $value : ' OR status:' . $value;
                }
            }
            else
            {
                $queryString .= 'status:' . $status;
            }

            $queryString .= ')';
        }

        if (empty($customStringsPresent) === false)
        {
            if (empty($status) === false)
            {
                foreach ($customStringsPresent as $key => $values)
                {
                    $queryString .= ' AND custom_string:\'' . $customStringsPresent[$key] . '\'';
                }
            }
            else
            {
                $firstKey = array_shift($customStringsPresent);

                $queryString .= '"custom_string:\'' . $firstKey . '\'';

                // Adding custom fields in filter
                foreach ($customStringsPresent as $key => $values)
                {
                    $queryString .= ' AND custom_string:\'' . $customStringsPresent[$key] . '\'';
                }
            }
        }

        if (array_key_exists('tags', $input) === true)
        {
            // adding tags in the filter
            foreach ($input['tags'] as $tag)
            {
                $queryString .= ' AND tag:\'' . $tag . '\'';
            }
        }

        $queryString .= '"';

        return $queryString;
    }

    private function isSplitzExperimentEnable(string $merchantId, string $experimentName, string $checkVariant): bool
    {
        $variant = $this->getSplitzResponse($merchantId, $experimentName);

        if ($variant === $checkVariant)
        {
            return true;
        }

        return false;
    }

    private function getSplitzResponse(string $merchantId, string $experimentName)
    {
        try
        {
            $experimentId = $this->app['config']->get($experimentName);

            $response = $this->app['splitzService']->evaluateRequest([
                'id'            => $merchantId,
                'experiment_id' => $experimentId,
            ]);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'merchant_id'   => $merchantId,
                'experiment_id' => $experimentId,
                'result'        => $response
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id'   => $merchantId,
                'experiment_id' => $this->config->get($experimentName) ?? null
            ]);
        }

        return $response['response']['variant']['name'] ?? '';
    }

    abstract protected function getSmsData();

    abstract protected function getEmailData();

    abstract protected function getWhatsappData();

    abstract protected function getFreshdeskTicketData();
}
