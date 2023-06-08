<?php

namespace RZP\Models\CyberCrimeHelpDesk;

use View;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use Illuminate\Support\Str;
use RZP\lib\TemplateEngine;
use RZP\Base\ConnectionType;
use RZP\Models\Payment\Fraud;
use RZP\Models\Admin\Permission;
use RZP\Models\BankAccount\Type;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Fraud\BankCodes;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Transaction\Service as TransactionService;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Payment\Fraud\Entity as PaymentFraudEntity;
use RZP\Models\Payment\Fraud\Service as PaymentFraudService;
use RZP\Models\Payment\Fraud\Constants as PaymentFraudConstants;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FdConstants;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshDeskService;
use RZP\Models\Merchant\Fraud\BulkNotification\Freshdesk as FreshdeskNotification;


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

        $currentDateTime = epoch_format(time() + Constants::IST_DIFF_IN_SEC, 'Y-m-d h:i:sa');;

        $mailSubject = (new TemplateEngine)->render(Constants::LEA_ACKNOWLEDGEMENT_MAIL_SUBJECT, []);

        $mailSubject = sprintf($mailSubject, $currentDateTime);

        $mailBody = \View::make(Constants::LEA_ACKNOWLEDGEMENT_MAIL_TEMPLATE, [
            'currentDateTime'  => $currentDateTime,
            'payment_requests' => $input[Constants::PAYMENT_REQUESTS]
        ])->render();

        $freshDeskConfig = $this->app['config']->get('applications.freshdesk');

        $fdOutboundEmailRequest = [
            'subject'         => $mailSubject,
            'description'     => $mailBody,
            'email'           => $input['requester_mail'],
            'status'          => 2, // Create ticket with open status
            'priority'        => 1,
            'type'            => 'Incident',
            'email_config_id' => (int) $freshDeskConfig['email_config_ids']['cybercrime_helpdesk']['acknowledgement'],
            'group_id'        => (int) $freshDeskConfig['group_ids']['cybercrime_helpdesk']['acknowledgement'],
            'custom_fields'   => [
                'cf_ticket_queue' => 'Thirdparty',
                'cf_category'     => 'Fraud',
                'cf_subcategory'  => Constants::FRESHDESK_EMAIL_CYBER_CELL_SUB_CATEGORY,
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
                '$mailBody'          => $mailBody,
                '$mailSubject'       => $mailSubject,
                'freshdesk_response' => $response,
            ]);

        return [Constants::FD_TICKET_ID => (string) $response['id'] ?? null];
    }

    /**
     * @throws Exception\LogicException
     */
    public function postCyberCrimeWorflowCreateAction($inputs)
    {
        (new Validator)->validateInput('cyber_crime_helpdesk_workflow_action_create', $inputs);

        $freshdeskTicketId = $inputs[Constants::TICKET_DATA][Constants::FD_TICKET_ID];

        $maker = $this->getCyberCrimeWorkflowMaker($inputs[Constants::REQUESTER_EMAIL]);

        $this->app['workflow']
            ->setPermission(Permission\Name::CREATE_CYBER_HELPDESK_WORKFLOW)
            ->setController(Constants::CYBER_CRIME_HELPDESK_WORKFLOW_CONTROLLER)
            ->setMakerFromAuth(false)
            ->setWorkflowMaker($maker)
            ->setWorkflowMakerType(MakerType::ADMIN)
            ->setEntityAndId('freshdesk_ticket', $freshdeskTicketId)
            ->handle([], $inputs);
    }

    /**
     * @throws Exception\LogicException
     */
    protected function getCyberCrimeWorkflowMaker($requesterEmail)
    {
        // This is to be handled correctly, for now hardcoding the org_id for the maker_email used in config
        $makerOrg   = Org\Entity::RAZORPAY_ORG_ID;
        $makerEmail = $this->app['config']->get('applications.cyber_crime_helpdesk.maker_email');

        if ($this->isRazorPayEmailId($requesterEmail) === true)
        {
            $makerEmail = $requesterEmail;
        }

        if (empty($makerEmail) === true)
        {
            throw new Exception\LogicException('Cyber Crime Workflow Maker is not initialized');
        }

        $maker = $this->repo->admin->findByOrgIdAndEmail($makerOrg, $makerEmail);

        return $maker;
    }

    /**
     * @throws Exception\LogicException
     * @throws \Throwable
     */
    public function postCyberCrimeWorkflowApproval($ticketDetails)
    {
        $this->updateTicketDataAsPerApprovedDataFromComment($ticketDetails);

        $this->shareFetchedDetailsWithLEA($ticketDetails);

        $this->notifyMerchantViaFreshdeskOutboundMail($ticketDetails);

        $this->putSettlementOnHoldIfRequired($ticketDetails);

        $this->createFraudPaymentEntries($ticketDetails);
    }

    /**
     * @throws Exception\LogicException
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    protected function createFraudPaymentEntries($ticketDetails)
    {
        $requesterEmail = $ticketDetails[Constants::REQUESTER_EMAIL];

        $fdTicketId = $ticketDetails[Constants::TICKET_DATA][Constants::FD_TICKET_ID];

        $reportedToRazorpayAt = $this->reportedToRazorpayAt($requesterEmail, $fdTicketId);

        $approvedDetails = $this->getApprovedDetailsFromWorkflowActionComments($fdTicketId);

        foreach ($ticketDetails[Constants::TICKET_DATA][Constants::TICKET] as $ticketDetailData)
        {
            foreach ($approvedDetails as $approvedDetail)
            {
                if ($ticketDetailData[Constants::REQUEST][Constants::ID] === $approvedDetail[Constants::REQUEST_ID])
                {
                    $paymentId = $ticketDetailData[Constants::DETAILS][Constants::PAYMENT][Constants::ID];

                    $fraudType =  BankCodes::FRAUD_CODE_3;

                    (new Fraud\Validator())->validTypeForCyberCrimeFraudPaymentEntityCreation($fraudType);

                    $payment = $this->repo->payment->findOrFail($paymentId);

                    $this->createPaymentFraudEntity($payment, $reportedToRazorpayAt, $fraudType);

                    $this->pushSegmentEventForCyberHelpDeskFraudPayments($paymentId, $fdTicketId,
                        Constants::SEGMENT_EVENT_CYBER_CRIME_FRAUD_PAYMENTS);
                }
            }
        }
    }

    protected function createPaymentFraudEntity(Payment\Entity $payment, int $reportedToRazorpayAt, string $type)
    {
        $this->trace->info(TraceCode::CREATING_PAYMENT_FRAUD_ENTRY_FOR_CYBER_CRIME_HELPDESK, [
            PaymentFraudEntity::PAYMENT_ID => $payment->getId(),
            PaymentFraudEntity::TYPE => $type,
            PaymentFraudEntity::AMOUNT => $payment->getAmount()/100.0,
            PaymentFraudEntity::REPORTED_TO_RAZORPAY_AT => $reportedToRazorpayAt,
        ]);

        $input = [
            PaymentFraudEntity::PAYMENT_ID => $payment->getId(),
            PaymentFraudEntity::TYPE => $type,
            PaymentFraudEntity::REPORTED_TO_ISSUER_AT => $payment->getCreatedAt(),
            PaymentFraudEntity::REPORTED_TO_RAZORPAY_AT => $reportedToRazorpayAt,
            PaymentFraudConstants::HAS_CHARGEBACK => "0",
            PaymentFraudEntity::IS_ACCOUNT_CLOSED => "0",
            PaymentFraudEntity::AMOUNT => $payment->getAmount()/100.0,
            PaymentFraudEntity::CURRENCY => Currency::INR,
            PaymentFraudEntity::REPORTED_BY => PaymentFraudConstants::REPORTED_BY_CYBERCELL,
            PaymentFraudConstants::SKIP_MERCHANT_EMAIL => "1",
        ];

        (new PaymentFraudService())->savePaymentFraud($input);
    }

    /**
     * @throws Exception\LogicException
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestException
     */
    protected function reportedToRazorpayAt($requesterEmail, $fdTicketId) : int
    {
        // if workflow raised by admin dashboard : fd ticket creation time
        if ($this->isRazorPayEmailId($requesterEmail) === true)
        {
            $response = $this->app['freshdesk_client']->fetchTicketById($fdTicketId);

            (new FreshDeskService())->validateTicketResponse($response, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);

            $ticketCreatedAt = $response[Common::CREATED_AT];

            return strtotime($ticketCreatedAt);
        }

        // if workflow raised by LEA dashboard : current time
        return Carbon::now()->getTimestamp();
    }

    /**
     * @throws Exception\LogicException
     */
    protected function isRazorPayEmailId(string $email) : bool
    {
        $email = strtolower($email);

        // make sure we've got a valid email
        if (filter_var($email, FILTER_VALIDATE_EMAIL) )
        {
            $domainName = substr(strrchr($email, "@"), 1);

            if ($domainName === Constants::RZP_EMAIL_DOMAIN)
            {
                return true;
            }

            return false;
        }

        throw new Exception\LogicException('Invalid Requester Email: ' . $email);
    }

    protected function putSettlementOnHoldIfRequired($ticketDetails)
    {
        $txnIdsToPutOnHold = [];

        $this->app['trace']->info(TraceCode::CYBER_CRIME_MODIFIED_TICKET_DETAILS,
                                  [
                                      'ticket_detail' => $ticketDetails,
                                  ]);

        foreach ($ticketDetails['ticket_data']['ticket'] as $request)
        {
            if ($request['hold_settlement'] === 1)
            {
                if (array_key_exists(Constants::DETAILS,$request) &&
                    array_key_exists(Constants::TRANSACTION,$request[Constants::DETAILS]) &&
                    array_key_exists(Constants::ID, $request[Constants::DETAILS][Constants::TRANSACTION])
                ) {
                    $txnIdsToPutOnHold[] = $request[Constants::DETAILS][Constants::TRANSACTION][Constants::ID];
                }
            }
        }

        $this->app['trace']->info(TraceCode::CYBER_CRIME_PUT_PAYMENTS_ON_HOLD,
                                  [
                                      'transaction_ids' => $txnIdsToPutOnHold,
                                  ]);

        if (empty($txnIdsToPutOnHold) === false)
        {
            (new TransactionService())->toggleTransactionHold([
                                                                  'transaction_ids' => $txnIdsToPutOnHold,
                                                                  'reason'          => "Payment on hold as requested by lea"
                                                              ]);
        }
    }


    /**
     * @throws \Throwable
     * @throws Exception\LogicException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\BadRequestException
     */
    protected function updateTicketDataAsPerApprovedDataFromComment(&$ticketDetails)
    {
        $freshdeskTicket = $ticketDetails[Constants::TICKET_DATA][Constants::FD_TICKET_ID];

        $approvedDetails = $this->getApprovedDetailsFromWorkflowActionComments($freshdeskTicket);

        $this->app['trace']->info(
            TraceCode::CYBER_HELPDESK_REQUEST_DETAILS_APPROVED,
            [
                'approved_details' => $approvedDetails,
                'freshdesk_id'     => $freshdeskTicket
            ]);

        $this->app['trace']->info(TraceCode::CYBER_CRIME_TICKET_DETAILS,
                                  [
                                      'ticket_detail' => $ticketDetails,
                                  ]);
        foreach ($approvedDetails as $approvedDetail)
        {
            $this->updateRequestDetailsAccordingToApprovedDetails($ticketDetails, $approvedDetail);
        }

        $this->app['trace']->info(TraceCode::CYBER_CRIME_MODIFIED_TICKET_DETAILS,
                                  [
                                      'ticket_detail' => $ticketDetails,
                                  ]);
    }

    /**
     * @throws \Throwable
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\BadRequestException
     */
    protected function updateRequestDetailsAccordingToApprovedDetails(&$ticketDetails, $approvedDetail)
    {
        for ($i = 0; $i <= count($ticketDetails[Constants::TICKET_DATA][Constants::TICKET]); $i++)
        {
            $query = $ticketDetails[Constants::TICKET_DATA][Constants::TICKET][$i];

            if ($query[Constants::REQUEST]['id'] === $approvedDetail[Constants::REQUEST_ID])
            {
                if (isset($query['details']) === true && empty($query['details']) === false)
                {
                    //if the details were fetched by cyber_helpdesk service then it might be possible that settlement status is changed b/w workflow creation and approval
                    $ticketDetails[Constants::TICKET_DATA][Constants::TICKET][$i][Constants::DETAILS][Constants::PAYMENT][Payment\Entity::STATUS] =
                        $this->repo->payment->findOrFail($query['details']['payment']['id'])->getStatus();

                    $this->pushSegmentEventForCyberHelpDeskPayment($approvedDetail[Constants::PAYMENT_ID] ,$ticketDetails[Constants::TICKET_DATA][Constants::FD_TICKET_ID], Constants::SEGMENT_EVENT_CYBER_CRIME_FETCHED_PAYMENTS);
                }
                else
                {
                    //if the details weren't fetched by cyber_helpdesk and payment_id was provided by workflow approver then fetch the details
                    $ticketDetails[Constants::TICKET_DATA][Constants::TICKET][$i][Constants::DETAILS] = $this->getDetailsUsingPaymentId($approvedDetail[Constants::PAYMENT_ID], $query[Constants::REQUEST]);

                    $this->pushSegmentEventForCyberHelpDeskPayment($approvedDetail[Constants::PAYMENT_ID] ,$ticketDetails[Constants::TICKET_DATA][Constants::FD_TICKET_ID], Constants::SEGMENT_EVENT_CYBER_CRIME_NON_FETCHED_PAYMENTS);
                }

                $ticketDetails[Constants::TICKET_DATA][Constants::TICKET][$i][Constants::HOLD_SETTLEMENT]                   = $approvedDetail[Constants::PUT_SETTLEMENT_ON_HOLD];
                $ticketDetails[Constants::TICKET_DATA][Constants::TICKET][$i][Constants::SHARE_BENEFICIARY_ACCOUNT_DETAILS] = $approvedDetail[Constants::SHARE_BENEFICARY_ACCOUNT_DETAILS];

                break;
            }
        }
    }

    protected function pushSegmentEventForCyberHelpDeskPayment($paymentId, $fdTicketId, $event)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchant = $payment->merchant;

        $workflowActionId = $this->getOpenWorkflowActionIdForFreshdeskTicket($fdTicketId);

        $eventData = [
            'payment_id' => $paymentId,
            'workflow_action_id' => $workflowActionId,
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant,
            $eventData,
            $event,
        );
    }

    protected function pushSegmentEventForCyberHelpDeskFraudPayments($paymentId, $fdTicketId, $event)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchant = $payment->merchant;

        $workflowActionId = $this->getOpenWorkflowActionIdForFreshdeskTicket($fdTicketId);

        $eventData = [
            'payment_id' => $paymentId,
            'workflow_action_id' => $workflowActionId,
            'method' => $payment->getMethod(),
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant,
            $eventData,
            $event,
        );
    }

    /**
     * @throws \Throwable
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\BadRequestException
     */
    protected function getDetailsUsingPaymentId($paymentId, $request): array
    {
        $this->stripSign($paymentId);
        $payment = $this->repo->payment->findOrFail($paymentId);

        (new Validator)->validateApprovedPaymentWithQueryData($payment, $request['data']);

        $merchantDetails  = $payment->merchant->merchantDetail;
        $bankAccount      = $this->repo->bank_account->getBankAccount($merchantDetails, Type::MERCHANT);
        $transaction      = $this->repo->transaction->findOrFail($payment->getTransactionId());
        $paymentAnalytics = $this->repo->payment_analytics->fetch(['payment_id' => $payment->getId()], null, ConnectionType::REPLICA)[0];

        return [
            Constants::PAYMENT           => [
                Constants::ID        => $payment->getId(),
                Payment\Entity::METHOD      => $payment->getMethod(),
                Payment\Entity::BASE_AMOUNT => $payment->getBaseAmount(),
                Payment\Entity::CREATED_AT  => $payment->getCreatedAt(),
                Payment\Entity::EMAIL       => $payment->getEmail(),
                Payment\Entity::CONTACT     => $payment->getContact(),
                Payment\Entity::STATUS      => $payment->getStatus(),
            ],
            Constants::MERCHANT_DETAILS  => [
                Constants::MERCHANT_ID      => $merchantDetails->getMerchantId(),
                Constants::MERCHANT_NAME    => $merchantDetails->getBusinessName(),
                Constants::CONTACT_NAME     => $merchantDetails->getContactName(),
                Constants::CONTACT_EMAIL   => $merchantDetails->getContactEmail(),
                Constants::CONTACT_MOBILE   => $merchantDetails->getContactMobile(),
                Constants::BUSINESS_WEBSITE => $merchantDetails->getWebsite(),
            ],
            Constants::TRANSACTION       => [
                Constants::ID      => $transaction->getId(),
                Constants::SETTLED => $transaction->isSettled(),
            ],
            Constants::BANK_ACCOUNT      => [
                Constants::ID               => $bankAccount->getId(),
                Constants::BENEFICIARY_NAME => $bankAccount->getBeneficiaryName(),
                Constants::ACCOUNT_NUMBER   => $bankAccount->getAccountNumber(),
                Constants::IFSC_CODE        => $bankAccount->getIfscCode(),
            ],
            Constants::PAYMENT_ANALYTICS => [
                Constants::IP => $paymentAnalytics !== null ? $paymentAnalytics->getIp() : ""
            ],
        ];
    }

    protected function notifyMerchantViaFreshdeskOutboundMail($ticketDetails)
    {
        $data       = $ticketDetails[Constants::TICKET_DATA]['ticket'];
        $merchantId = $data[0]['details']['merchant_details']['merchant_id'];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $fdOutboundEmailRequest = $this->getOutboundEmailBody($merchant, $data);

        $response = $this->app['freshdesk_client']->sendOutboundEmail($fdOutboundEmailRequest);

        $response['body']                               = null;
        $fdOutboundEmailRequest['description']          = null;
        $fdOutboundEmailRequest[FdConstants::CC_EMAILS] = null;

        $this->app['trace']->info(
            TraceCode::CYBER_HELPDESK_MERCHANT_NOTIFICATION_SENT,
            [
                'freshdesk_request'  => $fdOutboundEmailRequest,
                'freshdesk_response' => $response,
            ]);
    }

    protected function shareFetchedDetailsWithLEA($ticketDetails)
    {
        $currentDateTime = epoch_format(time() + Constants::IST_DIFF_IN_SEC, 'Y-m-d h:i:sa');

        $data                              = $ticketDetails[Constants::TICKET_DATA][Constants::TICKET];
        $merchantDetails                   = $data[0][Constants::DETAILS][Constants::MERCHANT_DETAILS];
        $bankAccount                       = $data[0][Constants::DETAILS][Constants::BANK_ACCOUNT];
        $share_beneficiary_account_details = $this->shouldShareBeneficiaryAccountDetails($ticketDetails);
        $mailBody                          = \View::make(Constants::SEND_DETAILS_WITH_LEA_MAIL_TEMPLATE, [
            Constants::FD_TICKET_ID                     => $ticketDetails[Constants::TICKET_DATA][Constants::FD_TICKET_ID],
            Constants::CURRENT_DATE_TIME                => $currentDateTime,
            Constants::DATA                             => $data,
            Constants::MERCHANT_DETAILS                 => $merchantDetails,
            Constants::BANK_ACCOUNT                     => $bankAccount,
            Constants::SHARE_BENEFICIARY_ACCOUNT_DETAILS => $share_beneficiary_account_details,
            Constants::IST_DIFF                         => Constants::IST_DIFF_IN_SEC,
        ])->render();

        $replyInputs = ['body' => $mailBody];

        $response = $this->app['freshdesk_client']->postTicketReply((int) $ticketDetails[Constants::TICKET_DATA][Constants::FD_TICKET_ID], $replyInputs);

        $response['body']      = null;
        $response['body_text'] = null;

        $this->app['trace']->info(
            TraceCode::CYBER_HELPDESK_DETAILS_TO_LEA_POSTED,
            [
                'freshdesk_response' => $response
            ]);
    }

    protected function shouldShareBeneficiaryAccountDetails($ticketDetails)
    {
        $data = $ticketDetails[Constants::TICKET_DATA][Constants::TICKET];
        $bankAccountDetails = $data[0][Constants::DETAILS][Constants::BANK_ACCOUNT];

        if ($ticketDetails[Constants::ENABLE_SHARE_BENEFICIARY_DETAILS_CHECKBOX] === false
            || (empty($bankAccountDetails)===false && Str::startsWith($bankAccountDetails[Constants::ACCOUNT_NUMBER], Constants::VIRTUAL_ACCOUNT_PREFIXES) === true))
        {
            return 0;
        }

        return $data[0][Constants::SHARE_BENEFICIARY_ACCOUNT_DETAILS];
    }

    /**
     * @throws Exception\LogicException
     */
    protected function getApprovedDetailsFromWorkflowActionComments($fresdeskTicket)
    {
        $workflowActionId = $this->getOpenWorkflowActionIdForFreshdeskTicket($fresdeskTicket);

        $comments = $this->repo->comment->fetchByActionId($workflowActionId);

        foreach ($comments as $comment)
        {
            $requestDetails = $this->getJsonDecodeDataFromComments($comment);

            if (empty($requestDetails) === false)
            {
                return $requestDetails;
            }
        }

        throw new Exception\LogicException('Payment Details comment not found on workflow');
    }

    protected function getOpenWorkflowActionIdForFreshdeskTicket($freshdeskTicket)
    {
        $actions = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperationWithPermissionList(
            $freshdeskTicket, 'freshdesk_ticket', [PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW], Org\Constants::RZP);

        if (empty($actions) === true)
        {
            throw new Exception\LogicException('Workflow is not present for entity id ' . $freshdeskTicket);
        }

        return $actions[0]->getId();
    }

    protected function getJsonDecodeDataFromComments($commentDetails)
    {
        $comment = $commentDetails['comment'];

        if (str_contains($comment, Constants::PREFIX_CYBER_CRIME_PAYMENT_DETAILS_COMMENT))
        {
            return json_decode(substr($comment, strlen(Constants::PREFIX_CYBER_CRIME_PAYMENT_DETAILS_COMMENT), strlen($comment)), true);
        }

        return null;
    }

    protected function stripSign(&$id)
    {
        $ix = strpos($id, '_');

        if ($ix !== false)
        {
            $id = substr($id, $ix + 1);
        }
    }

    /**
     * @param Base\Entity $merchant
     * @param             $data
     *
     * @return array
     */
    protected function getOutboundEmailBody(Base\Entity $merchant, $data): array
    {
        $emailIds = (new FreshdeskNotification(null, null))->getEmailIdsWithSalesPOC($merchant);

        $primaryEmailId = array_shift($emailIds);

        $mailSubject = sprintf(Constants::NOTIFY_MERCHANT_ABOUT_FRAUD_MAIL_SUBJECT, $merchant->getName(), $merchant->getId(), date('Y-m-d'));

        $mailBody = \View::make(Constants::NOTIFY_MERCHANT_ABOUT_FRAUD_MAIL_TEMPLATE, [
            Constants::MERCHANT_NAME     => $merchant->getName(),
            Constants::MERCHANT_ID       => $merchant->getId(),
            Constants::CURRENT_DATE_TIME => epoch_format(time() + Constants::IST_DIFF_IN_SEC, 'Y-m-d h:i:sa'),
            Constants::RESPOND_BY        => date('d F Y', time() + Constants::MERCHANT_RESPOND_BY_IN_SECONDS + Constants::IST_DIFF_IN_SEC),
            Constants::DATA              => $data,
            Constants::IST_DIFF          => Constants::IST_DIFF_IN_SEC
        ])->render();

        $fdOutboundEmailRequest = [
            FdConstants::SUBJECT       => $mailSubject,
            FdConstants::DESCRIPTION   => $mailBody,
            FdConstants::STATUS        => 6,
            FdConstants::TYPE          => 'Service request',
            FdConstants::PRIORITY      => 3,
            FdConstants::EMAIL         => $primaryEmailId,
            FdConstants::TICKET_TAGS   => ['bulk_fraud_email'],
            FdConstants::GROUP_ID      => (int) $this->app['config']->get('applications.freshdesk')['group_ids']['rzpind']['byers_risk'],
            'email_config_id'          => (int) $this->app['config']->get('applications.freshdesk')['email_config_ids']['cybercrime_helpdesk']['notify_merchant'],
            FdConstants::CUSTOM_FIELDS => [
                FdConstants::CF_TICKET_QUEUE => 'Merchant',
                FdConstants::CF_MERCHANT_ID  => $merchant->getId(),
                FdConstants::CF_CATEGORY     => 'Risk Report_Merchant',
                FdConstants::CF_SUBCATEGORY  => 'Fraud alerts',
                FdConstants::CF_PRODUCT      => 'Payment Gateway',
            ],
        ];

        if (empty($emailIds) === false)
        {
            $fdOutboundEmailRequest[FdConstants::CC_EMAILS] = $emailIds;
        }

        return  $fdOutboundEmailRequest;
}
}
