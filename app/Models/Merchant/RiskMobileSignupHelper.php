<?php

namespace RZP\Models\Merchant;

use App;
use View;
use RZP\Models\Dispute;
use RZP\Trace\TraceCode;
use RZP\lib\TemplateEngine;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\FreshdeskTicket;

class RiskMobileSignupHelper
{
    protected $app;

    protected $repo;

    protected $elfin;

    protected $trace;

    protected $freshdeskConfig;

    const SUPPORT_TICKET_URL_TPL = 'https://%s/app/ticket-support/%s/%s/agent/conversation';
    const SUPPORT_TICKETS_URL_TPL    = 'https://%s/app/ticket-support/tickets';

    public function __construct()
    {
        $this->app   = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->repo  = $this->app['repo'];
        $this->elfin = $this->app['elfin'];
        $this->freshdeskConfig = $this->app['config']->get('applications.freshdesk');
    }

    protected static function isEmptyEntity($entity): bool {
        return empty($entity) === true
            or (is_array($entity) and sizeof($entity) === 1 and empty($entity[0]));
    }

    public function getSupportTicketsUrl($merchant) {
        return $this->elfin->shorten(self::SUPPORT_TICKETS_URL_TPL, $merchant->org->getPrimaryHostName());
    }

    public static function isEligibleForMobileSignUp($merchant): bool
    {
        return (
            $merchant->isSignupViaEmail() === false
            and self::isEmptyEntity((new Dispute\Service)->getDefaultDisputeEmails($merchant->getId())) === true
        );
    }

    public function createFdTicket($merchant, $viewTemplate, $subject, $data, $requestParams, $mailBody = null)
    {
        try {
            try
            {
                $mailSubject = (new TemplateEngine)->render($subject, $data);
            }
            catch (\Throwable $e)
            {
                $mailSubject = $subject;
            }

            if (isset($mailBody) === false)
            {
                $mailBody =  View::make($viewTemplate, $data)->render();
            }

            $postTicketRequest = [
                'subject'                               => $mailSubject,
                'description'                           => $mailBody,
                'phone'                                 => $merchant->merchantDetail->getContactMobile(),
                'name'                                  => $merchant->getName(),
                'type'                                  => $requestParams['type'] ?? FreshdeskTicket\Constants::QUESTION_TICKET_TYPE,
                'tags'                                  => $requestParams['tags'] ?? [],
                'status'                                => $requestParams['status'] ?? 6,
                'group_id'                              => $requestParams['groupId'] ?? (int) $this->freshdeskConfig['group_ids']['rzpind']['merchant_risk'],
                'priority'                              => $requestParams['priority'] ?? 1,
                FreshdeskTicket\Constants::FD_INSTANCE  => FreshdeskTicket\Constants::RZPIND,
                'custom_fields'                         => [
                    'cf_ticket_queue'           => 'Merchant',
                    'cf_category'               => $requestParams['category'] ?? 'Risk Report_Merchant',
                    'cf_subcategory'            => $requestParams['subCategory'] ?? 'Fraud alerts',
                    'cf_product'                => 'Payment Gateway',
                    'cf_created_by'             => 'agent',
                ],
            ];

            if (isset($requestParams['attachments']) === true)
            {
                $postTicketRequest['attachments'] = $requestParams['attachments'];
            }

            $response = (new FreshdeskTicket\Service())->postTicketOnMerchantBehalf(
                $postTicketRequest, $merchant->getId(), true);

            $this->app['trace']->info(
                TraceCode::MERCHANT_RISK_FD_TICKET_CREATED,
                [
                    'merchant_id'       => $merchant->getId(),
                    'fd_ticket_id'         => $response['id'],
                ]);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_RISK_FD_CREATE_TICKET_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    public function getSupportTicketLink($fdTicket, $merchant)
    {
        $supportTicketUrl = sprintf(self::SUPPORT_TICKET_URL_TPL, $merchant->org->getPrimaryHostName(), $fdTicket['fd_instance'], $fdTicket['id']);

        return $this->elfin->shorten($supportTicketUrl);
    }
}
