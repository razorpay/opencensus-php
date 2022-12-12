<?php

namespace RZP\Models\CyberCrimeHelpDesk;

use View;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Permission;
use RZP\Trace\TraceCode;
use RZP\lib\TemplateEngine;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Admin\Org;
use RZP\Models\BankAccount\Type;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Transaction\Service as TransactionService;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;

class Service extends Base\Service
{
    private $freshdeskConfig;

    public function __construct()
    {
        parent::__construct();

        $this->freshdeskConfig = $this->app['config']->get('applications.freshdesk');
    }

    public function sendMailToLEAFromCyberCrimeHelpdesk($input)
    {
        (new Validator)->validateInput('sendMailToLEAFromCyberCrimeHelpdesk', $input);

        $currentDateTime     = date('Y-m-d H:i:s');

        $mailSubject         = (new TemplateEngine)->render(Constants::MAIL_TO_LEA_FROM_CYBRERSOURCE_HELPDESK_EMAIL_SUBJECT, []);

        $mailSubject         = sprintf($mailSubject, $currentDateTime);

        $mailBody            = \View::make(Constants::MAIL_TO_LEA_FROM_CYBRERSOURCE_HELPDESK_EMAIL_TEMPLATE, [
            'currentDateTime'     => $currentDateTime,
            'payment_requests'    => $input[Constants::PAYMENT_REQUESTS]
        ])->render();

        $freshDeskConfig     =  $this->app['config']->get('applications.freshdesk');

        $fdOutboundEmailRequest = [
            'subject'         => $mailSubject,
            'description'     => $mailBody,
            'email'           => $input['requester_mail'],
            'status'          => 2, // Create ticket with open status
            'priority'        => 1,
            'type'            => 'Incident',
            'email_config_id' => (int)$freshDeskConfig['email_config_ids']['cybercrime_helpdesk']['acknowledgement'],
            'group_id'        => (int)$freshDeskConfig['group_ids']['cybercrime_helpdesk']['acknowledgement'],
            'custom_fields'   => [
                'cf_ticket_queue' => 'Thirdparty',
                'cf_category'     =>  'Fraud',
                'cf_subcategory'  =>  Constants::FRESHDESK_EMAIL_CYBER_CELL_SUB_CATEGORY,
                'cf_product'      => 'Payment Gateway',
            ]
        ];

        if (empty($ccEmails) === false)
        {
            $fdOutboundEmailRequest['cc_emails'] = $ccEmails;
        }

        $response = $this->app['freshdesk_client']->sendOutboundEmail($fdOutboundEmailRequest);

        $this->app['trace']->info(
            TraceCode::MAIL_TO_LEA_FROM_CYBER_CRIME_HELPDESK_SENT,
            [
                '$mailBody'           => $mailBody,
                '$mailSubject'        => $mailSubject,
                'freshdesk_response'  => $response,
            ]);

        return [ Constants::FD_TICKET_ID =>  (string) $response['id'] ?? null ];
    }

    public function postCyberCrimeWorflowCreateAction($inputs)
    {
        (new Validator)->validateInput('cyber_crime_helpdesk_workflow_action_create', $inputs);

        $freshdeskTicketId = $inputs[Constants::TICKET_DATA][Constants::FD_TICKET_ID];

        $maker = $this->getCyberCrimeWorkflowMaker();

        $this->app['workflow']
            ->setPermission(Permission\Name::CREATE_CYBER_HELPDESK_WORKFLOW)
            ->setController(Constants::CYBER_CRIME_HELPDESK_WORKFLOW_CONTROLLER)
            ->setMakerFromAuth(false)
            ->setWorkflowMaker($maker)
            ->setWorkflowMakerType(MakerType::ADMIN)
            ->setEntityAndId('freshdesk_ticket', $freshdeskTicketId)
            ->handle([], $inputs);
    }

    protected function getCyberCrimeWorkflowMaker()
    {
        // This is to be handled correctly, for now hardcoding the org_id for the maker_email used in config
        $makerOrg = Org\Entity::RAZORPAY_ORG_ID;
        $makerEmail = $this->app['config']->get('applications.cyber_crime_helpdesk.maker_email');

        if (empty($makerEmail) === true)
        {
            throw new Exception\LogicException('Cyber Crime Workflow Maker is not initialized');
        }

        $maker = $this->repo->admin->findByOrgIdAndEmail($makerOrg, $makerEmail);

        return $maker;
    }

    public function postCyberCrimeWorkflowApproval($inputs)
    {
        $entityId = $inputs[Constants::TICKET_DATA][Constants::FD_TICKET_ID];

        $actions  = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperationWithPermissionList(
            $entityId, 'freshdesk_ticket', [PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW], Org\Constants::RZP);

        if ( empty($actions) === true )
        {
            throw new Exception\LogicException('Workflow is not present for entity id ' . $entityId);
        }

        $workflowAction = $actions[0];

        $requestsDetails  = $this->getCyberCrimeMerchantsPaymentData($workflowAction);

        $this->app['trace']->info(
            TraceCode::PAYMENT_DETAILS_USED_FOR_CYBER_CRIME,
            [
                'request_details'         => $requestsDetails,
                'freshdesk_id'            => $entityId
            ]);

        $paymentIdsToPutOnHold = [];

        foreach ($requestsDetails as $requestDetail)
        {
            $paymentId = $requestDetail['payment_id'];

            $payment   = $this->repo->payment->findOrFail($paymentId);

            if ( empty($requestDetail[Constants::PUT_SETTLEMENT_ON_HOLD]) === false )
            {
                $txn                     =  $payment->transaction;

                $paymentIdsToPutOnHold[] = $txn->getId();
            }

            if ( empty($requestDetail[Constants::SHARE_BENEFICARY_ACCOUNT_DETAILS]) === false )
            {
                $this->sendFreshdeskOutboundMailToMerchantAboutSharingMerchantDetails($payment);
            }

            $this->sendFreshdeskOutboundMailReplyToLEA($payment, $entityId, $requestDetail[Constants::SHARE_BENEFICARY_ACCOUNT_DETAILS]);
        }

        $this->app['trace']->info(TraceCode::CYBER_CRIME_PUT_PAYMENTS_ON_HOLD,
            [
                'payment_ids'        => $paymentIdsToPutOnHold,
            ]);

        if (empty($paymentIdsToPutOnHold) === false)
        {
            (new TransactionService())->toggleTransactionHold([
                'transaction_ids' => $paymentIdsToPutOnHold,
                'reason'          => "Payment on hold as requested by lea"
            ]);
        }
    }

    protected function sendFreshdeskOutboundMailToMerchantAboutSharingMerchantDetails($paymentDetails)
    {
        $merchant            = $paymentDetails->merchant;

        $mailSubject         = sprintf(Constants::MAIL_TO_MERCHANT_ABOUT_CYBER_CRIME_EMAIL_SUBJECT, $merchant->getName(), $merchant->getId(), date('Y-m-d'));

        $mailBody            = \View::make(Constants::MAIL_TO_MERCHANT_ABOUT_CYBER_CRIME_EMAIL_TEMPLATE, [
            'merchant_name'     => $merchant->getName(),
            'mid'               => $merchant->getId(),
            'date_time_stamp'   => date('Y-m-d H:i:s'),
            'amount'            => $paymentDetails->getBaseAmount(),
            'payment_id'        => $paymentDetails->getId(),
            'payment_created_at' => epoch_format($paymentDetails->created_at + Constants::IST_DIFF),
            'respond_by'        => date('d F Y', time()+Constants::MERCHANT_RESPOND_BY_IN_SECONDS + Constants::IST_DIFF),
        ])->render();

        $fdOutboundEmailRequest = [
            'subject'         => $mailSubject,
            'description'     => $mailBody,
            'status'          => 6,
            'type'            => 'Service request',
            'priority'        => 3,
            'email'           => $merchant->getEmail(),
            'tags'            => ['bulk_fraud_email'],
            'group_id'        => (int) $this->app['config']->get('applications.freshdesk')['group_ids']['rzpind']['byers_risk'],
            'email_config_id' => (int) $this->app['config']->get('applications.freshdesk')['email_config_ids']['rzpind']['risk_notification'],
            'custom_fields'   => [
                'cf_ticket_queue' => 'Merchant',
                'cf_merchant_id'  => $merchant->getId(),
                'cf_category'     => 'Risk Report_Merchant',
                'cf_subcategory'  => 'Fraud alerts',
                'cf_product'      => 'Payment Gateway',
            ],
        ];

        $response = $this->app['freshdesk_client']->sendOutboundEmail($fdOutboundEmailRequest);

        $this->app['trace']->info(
            TraceCode::MAIL_TO_MERCHANT_ABOUT_DETAILS_SHARED_TO_LEA_SENT,
            [
                'freshdesk_response'  => $response,
            ]);
    }

    protected function sendFreshdeskOutboundMailReplyToLEA($paymentDetails, $freshdeskTicketId, $shareBeneficiaryAccountDetails)
    {
        $merchant                       = $paymentDetails->merchant;

        $currentDateTime                = date('Y-m-d H:i:s');

        $paymentAnalytics               = $this->repo->payment_analytics->findForPayment($paymentDetails->getId());

        $beneficiaryBankAccountDetails  = $this->repo->bank_account->getBankAccount($merchant, Type::MERCHANT);

        $customerIpAddress    = '';

        if (count($paymentAnalytics) > 0 ) {

            $paymentAnalytics  = $paymentAnalytics[0];

            $customerIpAddress = $paymentAnalytics['ip'];
        }

        $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchant->getId());

        $mailBody = \View::make(Constants::REPLY_MAIL_TO_LEA_TEMPLATE, [
            'payment_details'                  => $paymentDetails,
            'customer_ip_address'              => $customerIpAddress,
            'merchant'                         => $merchant,
            'merchant_details'                 => $merchantDetails,
            'fd_ticket_id'                     => $freshdeskTicketId,
            'current_date_time'                => $currentDateTime,
            'share_beneficary_account_details' => $shareBeneficiaryAccountDetails,
            'beneficiary_bank_account_details' => $beneficiaryBankAccountDetails
        ])->render();

        $replyInputs = ['body' => $mailBody];

        $response    = $this->app['freshdesk_client']->postTicketReply((int)$freshdeskTicketId, $replyInputs);

        $this->app['trace']->info(
            TraceCode::MAIL_TO_MERCHANT_ABOUT_DETAILS_SHARED_TO_LEA_SENT,
            [
                'freshdesk_response'  => $response,
                'body'                => $mailBody
            ]);
    }

    protected function getCyberCrimeMerchantsPaymentData($workflowAction)
    {
        $comments = $this->repo->comment->fetchByActionId($workflowAction->getId());

        foreach ($comments as $comment)
        {
            $paymentDetails = $this->getCyberCrimePaymentDetailsFromWorkflowComment($comment);

            if (empty($paymentDetails) === false)
            {
                return $paymentDetails;
            }
        }

        throw new Exception\LogicException('Payment Details comment not found on workflow');
    }

    protected function getCyberCrimePaymentDetailsFromWorkflowComment($commentDetails)
    {
        $comment = $commentDetails['comment'];

        if ( str_contains($comment, Constants::PREFIX_CYBER_CRIME_PAYMENT_DETAILS_COMMENT) )
        {
            return json_decode( substr($comment, strlen(Constants::PREFIX_CYBER_CRIME_PAYMENT_DETAILS_COMMENT), strlen($comment) ), true);
        }

        return null;
    }

}
