<?php

namespace RZP\Models\AppStore\PLOnWhatsapp;

use RZP\Http\Request\Requests;
use ApiResponse;
use RZP\Diag\EventCode;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use Illuminate\Http\Request;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Constants;

class Core extends \RZP\Models\AppStore\Base\Core
{

    const APP_STORE = 'api.merchant.appstore';

    const WELCOME_MESSAGE_TEMPLATE = 'WELCOME_MESSAGE';

    const PAYMENT_LINKS_PATH = 'v1/payment_links';

    const CONTENT_TYPE_JSON = 'application/json';

    const PAYMENT_LINK_ID  = 'plink_id';

    const PAYMENT_LINK_URL = 'payment_link_url';

    const PAYMENT_LINK_REQUESTER = 'whatsapp_bot';

    /**
     * @var mixed
     */
    private $baseUrl;

    /**
     * @var mixed
     */
    private $key;

    /**
     * @var mixed
     */
    private $timeOut;

    public function __construct()
    {
        parent::__construct();

        $plinkConfig   = $this->config->get('applications.payment_links');
        $this->baseUrl = $plinkConfig['url'];
        $this->key     = $plinkConfig['username'];
        $this->secret  = $plinkConfig['secret'];
        $this->timeOut = $plinkConfig['timeout'];
    }

    /**
     * Enable Whatsapp App for Merchant,
     *
     * @param string $mobileNumber
     * @param string $merchantId
     *
     * @return null |null
     */
    public function NotifyMerchant(string $mobileNumber, string $merchantId)
    {
        try
        {
            //OptInUser for Whatsapp Notifications
            $this->optInForWhatsapp($mobileNumber);

            //Send Message
            $this->sendMessage($merchantId, Templates::PL_WELCOME_MESSAGE_TEMPLATE, $mobileNumber);

        }
        catch (\Exception $e)
        {
            return null;
        }

    }

    /**
     * Creates PaymentLink with payment-link service and sends url to user on whatsapp
     *
     * @param Entity $merchant
     * @param string $amount
     * @param string $mobileNumber
     */
    public function sendPaymentLinkOnWhatsapp(Entity $merchant, string $amount, string $mobileNumber)
    {
        $this->trace->info(TraceCode::APPSTORE_CREATE_PL_ON_WHATSAPP_REQUEST,
                           [
                               'amount'     => $amount,
                               'merchantId' => $merchant->getMerchantId()
                           ]);

        $paymentLinkDetails = $this->createPaymentLink($merchant, $amount);

        $templateName = Templates::PL_CREATION_FAILED_MESSAGE_TEMPLATE;

        $params = [];

        if (empty($paymentLinkDetails) === false)
        {
            $templateName = Templates::PL_CREATION_SUCCESS_MESSAGE_TEMPLATE;

            $params =
                [
                    'paymentLinkUrl' => $paymentLinkDetails[self::PAYMENT_LINK_URL],
                ];
            //Using Onboarding Events, later this will be moved its respective.

            $eventProperties = [
                Entity::MERCHANT_ID     => $merchant->getMerchantId(),
                self::PAYMENT_LINK_ID   => $paymentLinkDetails[self::PAYMENT_LINK_ID]
                ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIPS_APPSTORE_WA_PL_CREATED,
                                                     $merchant, null, $eventProperties);
        }
        else
        {
            $eventProperties = [
                Entity::MERCHANT_ID     => $merchant->getMerchantId(),
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIPS_APPSTORE_WA_PL_FAILED,
                $merchant, null, $eventProperties);
        }

        $this->sendMessage($merchant->getId(), $templateName, $mobileNumber, $params);
    }

    public function sendMessageForWrongTemplate(string $merchantId, string $mobileNumber)
    {
        $this->sendMessage($merchantId, Templates::PL_WRONG_MESSAGE_TEMPLATE, $mobileNumber);
    }

    /**
     * Marks that user has given consent to Razorpay to send WhatsApp messages
     *
     * @param string $mobileNumber
     *
     * @return mixed
     */
    protected function optInForWhatsapp(string $mobileNumber)
    {
        $input = [
            'source'               => self::APP_STORE,
            'send_welcome_message' => false,
        ];

        return app('stork_service')->optInForWhatsapp($this->mode, $mobileNumber, $input);
    }

    /**
     * Sends Whatsapp Message to given MobileNumber
     *
     * @param string $merchantId
     * @param string $templateName
     * @param string $mobileNumber
     * @param array  $params
     */
    protected function sendMessage(string $merchantId, string $templateName, string $mobileNumber, array $params = [])
    {
        $payload = [
            'ownerId'       => $merchantId,
            'ownerType'     => Constants::MERCHANT,
            'params'        => $params,
            'template_name' => strtolower($templateName ?? ''),
        ];
        (new Stork)->sendWhatsappMessage(
            $this->mode,
            $this->getTemplateMessage($templateName),
            $mobileNumber,
            $payload
        );
    }

    /**
     * Creates a Payment Link with PaymentLink Service
     *
     * @param Entity $merchant
     * @param string $amount
     *
     * @return false|mixed
     */
    protected function createPaymentLink(Entity $merchant, string $amount)
    {
        try
        {
            $url = $this->baseUrl . self::PAYMENT_LINKS_PATH;

            $headers = $this->getHeaders($merchant);

            $amountInPaise = number_format((float)$amount*100, 0, '.', '');

            $data = [
                'amount' => (int) $amountInPaise,
            ];

            $options = [
                'timeout'          => $this->timeOut,
                'auth'             => [$this->key, $this->secret],
                'follow_redirects' => false,
            ];

            $params = [
                'url'     => $url,
                'headers' => $headers,
                'data'    => json_encode($data),
                'options' => $options,
                'method'  => Request::METHOD_POST,
            ];

            $response = Requests::request(
                $params['url'],
                $params['headers'],
                $params['data'],
                $params['method'],
                $params['options']);

            return $this->parseAndReturnPaymentLinkUrl($response);
        }
        catch (\Throwable $e)
        {
            return "";
        }

    }

    protected function getTemplateMessage(string $templateName)
    {
        return Templates::WHATSAPP_TEMPLATES[$templateName];
    }


    protected function getHeaders(Entity $merchant): array
    {
        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'X-Razorpay-Requester' => self::PAYMENT_LINK_REQUESTER,
        ];

        $headers['X-Razorpay-MerchantId'] = $merchant->getMerchantId();

        $headers['X-Razorpay-Mode'] = $this->mode;

        $enabledFeatures = $merchant->getEnabledFeatures();

        $headers['X-Razorpay-Merchant-Features'] = json_encode($enabledFeatures);

        return $headers;
    }

    /**
     * parses the response and returns paymentLink short-url
     *
     * @param $res
     *
     * @return false|mixed
     */
    protected function parseAndReturnPaymentLinkUrl($res)
    {
        $code = $res->status_code;

        $contentType = $res->headers['content-type'];

        $this->trace->info(TraceCode::APPSTORE_CREATE_PL_RESPONSE, [
            'status-code'   => $code,
            'response-body' => json_decode($res->body, true)
        ]);

        if ((str_contains($contentType, self::CONTENT_TYPE_JSON) === false) and ($code != 200))
        {
            return false;
        }

        $res = json_decode($res->body, true);

        return [
            self::PAYMENT_LINK_URL => $res['short_url'],
            self::PAYMENT_LINK_ID  => $res['id'],
            ];
    }

}
