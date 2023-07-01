<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use Carbon\Carbon;
use Lib\PhoneBook;
use Illuminate\Support\Str;
use RZP\Base\JitValidator;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstants;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Admin\Org;
use RZP\Models\Payment\Refund;
use RZP\Exception;
use RZP\Models\User;
use RZP\Notifications;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskTicketService;
use RZP\Models\Merchant\FreshdeskTicket\Validator as FreshdeskTicketValidator;
use RZP\Models\Merchant\FreshdeskTicket\Processor as FreshdeskWebhookProcessor;

class Service extends Base\Service
{
    /*
     * Default ticket properties
     * Priority = 1 (Low)
     * Status = 2 (Open)
     */
    const STATUS_FIELDS = [
        'priority' => 1,
        'status'   => 2,
    ];

    const X_SALESFORCE_EMAIL_ID         = 'X-Salesforce-Email-Id';

    const FD_INSTANCE_VS_SUBCATEGORIES = [
        Constants::RZPSOL => ['Technical support', 'Integrations'],
        Constants::RZPCAP => ['Corporate card related','Instant Settlements', 'Cash Advance', 'Working Capital Loan','Corporate Cards'],
    ];

    public function getTicketStatusForCustomer(array $response)
    {
        if (array_key_exists($response['status'], TicketStatus::$ticketStatusMapping) === true)
        {
            return TicketStatus::$ticketStatusMapping[$response['status']];
        }

        return TicketStatus::$ticketStatusMapping[2];
    }

    public function getTicketStatusForCustomerNodalStructure(array $response)
    {
        if (array_key_exists($response['status'], TicketStatus::$ticketStatusMappingForNodalStructure) === true)
        {
            return TicketStatus::$ticketStatusMappingForNodalStructure[$response['status']];
        }

        return TicketStatus::$ticketStatusMappingForNodalStructure[2];
    }

    public function getReserveBalanceTicketStatus(): array
    {
        $ticketId = $this->getReserveBalanceTicketId();

        if ($ticketId === "")
        {
            return [
                'ticket_exists' => false,
            ];
        }

        $response = $this->app['freshdesk_client']->getReserveBalanceTicketStatus($ticketId, Constants::URLIND);

        $response = [
            'ticket_id'     => $response['id'],
            'ticket_status' => (new FreshdeskTicketService)->getTicketStatusForCustomer($response),
            'ticket_exists' => true,
        ];

        return $response;
    }

    public function postReserveBalanceTicketDetails(array $input) : Entity
    {
        $merchantId = $this->auth->getMerchantId();

        $this->trace->info(TraceCode::MISC_TRACE_CODE, $input);

        $ticket = (new Core)->create($input, $merchantId);

        return $ticket;
    }

    private function getReserveBalanceTicketId() : string
    {
        $merchantId = $this->auth->getMerchantId();

        $type = Type::RESERVE_BALANCE_ACTIVATE;

        $params = ['type' => $type];

        $tickets = $this->repo->merchant_freshdesk_tickets->fetch($params, $merchantId);

        if ($tickets->count() !== 0)
        {
            return $tickets->first()->getTicketId();
        }

        return "";
    }

    protected function makeInputForPostTicketForAccountRecovery($input)
    {
        $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_CATEGORY] = Constants::MERCHANT;

        $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY] = Constants::ACCOUNT_LOCKED;

        if (empty ($input[Constants::TICKET_STATUS]) === true)
        {
            $input[Constants::TICKET_STATUS] = TicketStatus::getStatusMappingForStatusString(TicketStatus::PROCESSING);
        }

        $input[Constants::SUBJECT] = Constants::ACCOUNT_LOCKED;

        return $input;
    }

    public function verifyInputForAccountRecovery($input)
    {
        $request[Constants::PAN] = $input[Constants::PAN];

        if (array_key_exists(Constants::OLD_PHONE, $input))
        {
            $request[Constants::PHONE] = $input[Constants::OLD_PHONE];

            unset($input[Constants::OLD_PHONE]);
        }
        else
        {
            $request[Constants::EMAIL] = $input[Constants::OLD_EMAIL];

            unset($input[Constants::OLD_EMAIL]);
        }

        try
        {
            $merchantId = (new User\Core())->getOwnerMidsWithEmailOrMobileAndPan($request);

            if (empty($merchantId) === true)
            {
                throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_ACCOUNT_RECOVERY_PAN_DID_NOT_MATCH, 'pan');
            }

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $input[Constants::NAME] = $merchant->getName();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException($exception);

            $input[Constants::DESCRIPTION] = Constants::DESCRIPTION_ERROR_MESSAGE . "<b style='color:red;'>" .$exception->getMessage() . "</b>";

            $input[Constants::NAME] = Constants::NAME_NOT_PROVIDED;
        }

        return $input;
    }

    public function verifyOtpForAccountRecoveryAndAddDescription($input)
    {
        if (empty($input[Constants::PHONE]) === false)
        {
            $this->verifyPhoneNumber($input[Constants::PHONE], $input[Constants::OTP]);

            if(empty($input[Constants::DESCRIPTION]) === true)
            {
                $input[Constants::DESCRIPTION] = Constants::DESCRIPTION_CONTACT_DETAILS . $input[Constants::PHONE];
            }
            else
            {
                $input[Constants::DESCRIPTION] .= '<br>' . Constants::DESCRIPTION_CONTACT_DETAILS . $input[Constants::PHONE];
            }
        }
        else
        {
            (new Core)->verifyOtp($input[Constants::EMAIL], $input[Constants::OTP]);

            if(empty($input[Constants::DESCRIPTION]) === true)
            {
                $input[Constants::DESCRIPTION] = Constants::DESCRIPTION_CONTACT_DETAILS . $input[Constants::EMAIL];
            }
            else
            {
                $input[Constants::DESCRIPTION] .= '<br>' . Constants::DESCRIPTION_CONTACT_DETAILS . $input[Constants::EMAIL];
            }
        }

        return $input;
    }

    public function postTicketForAccountRecovery(array $input)
    {
        $input = $this->makeInputForPostTicketForAccountRecovery($input);

        (new Validator)->validateInput('create_merchant_account_recovery_ticket', $input);

        unset($input['captcha']);

        $input = $this->verifyInputForAccountRecovery($input);

        $input = $this->verifyOtpForAccountRecoveryAndAddDescription($input);

        $fdInstance = $this->getFdInstanceWhileCreatingTickets($input);

        $url = Constants::FRESHDESK_INSTANCES[Type::SUPPORT_DASHBOARD][$fdInstance];

        unset($input[Constants::OTP]);

        unset($input[Constants::PAN]);

        $input = $this->addRemoveValuesBeforeTicketCreation($input, $fdInstance);

        $ticketCreateResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicket($input, $url);

        $ticketCreateResponse[Constants::FD_INSTANCE] = $fdInstance;

        return $ticketCreateResponse;
    }

    /**
     * @param array $input
     * @param array $return
     * @throws Exception\BadRequestValidationFailureException
     */
    public function postTicket(array $input): array
    {
        (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('email_compulsory', $input);

        $isNodalStructureFeatureEnabled = $this->isNodalStructureEnabled($input[Constants::IS_PA_PG_ENABLED] ?? false);

        if ($isNodalStructureFeatureEnabled === false)
        {
            (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('create_customer_ticket', $input);

            (new Core)->verifyOtp($input['email'], $input['otp']);

            unset($input[Constants::OTP]);
        }
        else
        {
            $this->checkEmailVerifiedOrNot($input['email']);

            $this->checkDuplicateTicket($input);

            (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('create_customer_ticket_nodal_structure', $input);
        }

        if (empty ($input[Constants::TICKET_STATUS]) === true)
        {
            $input[Constants::TICKET_STATUS] = TicketStatus::getStatusMappingForStatusString(TicketStatus::PROCESSING);
        }

        $this->updateStatusFields($input);

        $this->populateCustomFields($input);

        $fdInstance = $this->getFdInstanceWhileCreatingTickets($input);

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, $fdInstance);

        unset($input['g_recaptcha_response']);

        unset($input[Constants::IS_PA_PG_ENABLED]);

        unset($input[Constants::FD_INSTANCE]);

        unset($input[Constants::OTP]);

        $input = $this->addRemoveValuesBeforeTicketCreation($input, $fdInstance);

        $ticketCreateResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicket($input, $url);

        $ticketCreateResponse[Constants::FD_INSTANCE] = $fdInstance;

        return $ticketCreateResponse;
    }

    protected function getFdInstanceWhileCreatingTickets($input)
    {
        return Constants::RZPIND;
    }

    /**
     * @param array $input
     * @param array $return
     * @throws BadRequestException
     */
    public function postOtp(array $input): array
    {
        (new Validator)->validateInput(__FUNCTION__, $input);

        $action = $input[Constants::ACTION] ?? null;

        unset($input[Constants::G_RECAPTCHA_RESPONSE]);

        if (empty($input[Constants::PHONE]) === false)
        {
            $phoneNumber = $input[Constants::PHONE];

            $phoneNumber = new PhoneBook($phoneNumber);

            $phoneNumber = $phoneNumber->format(PhoneBook::E164);

            (new Core)->generateAndSendCustomerOtpForMobile($phoneNumber, $action);
        }
        else
        {
            (new Core)->generateAndSendCustomerOtpForEmail($input[Constants::EMAIL]);
        }

        return [
            'success' => true,
        ];
    }

    public function fetchCustomerTickets($input)
    {
        $freshdeskTicketValidator = new FreshdeskTicketValidator;

        $freshdeskTicketValidator->validateInput('fetch_customer_tickets', $input);

        $isNodalStructureFeatureEnabled = $this->isNodalStructureEnabled($input[Constants::IS_PA_PG_ENABLED]??false);

        $skipOtpVerification = false;

        if ($isNodalStructureFeatureEnabled === true)
        {
            try
            {
                $this->checkEmailVerifiedOrNot($input[Constants::EMAIL]);

                $skipOtpVerification = true;
            }
            catch (BadRequestException $ex)
            {
                $this->trace->info(TraceCode::FRESHDESK_CUSTOMER_FLOW_EMAIL_NOT_VERIFIED, ['email' => $input[Constants::EMAIL]]);
            }
        }

        $email = $input[Constants::EMAIL];

        if ($skipOtpVerification === false)
        {
            $otp = $input['otp'];

            (new Core)->verifyOtp($email, $otp);

            $this->markOtpVerified($email);
        }

        $count = $input['count'] ?? 5;

        $queryParams = 'email=' . urlencode($email);

        $fdInstance = Constants::FD_INSTANCES_LIST_FOR_FETCHING_CUSTOMER_TICKETS;

        $mergedResponse = [];

        foreach ($fdInstance as $key => $value)
        {
            $url = $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD,$fdInstance[$key]);

            $tickets = $this->app[Constants::FRESHDESK_CLIENT]->getCustomerTickets($queryParams, $url);

            if (is_array($tickets) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_TICKET_FETCH_FAILED);
            }

            $response = $this->createTicketResponseFromReceivedArrays($tickets, $count, $isNodalStructureFeatureEnabled);

            $mergedResponse = array_merge($mergedResponse, $response);
        }

        if ($isNodalStructureFeatureEnabled === true)
        {
            $this->addTicketIdsToSession($mergedResponse);
        }

        return $mergedResponse;
    }

    /**
     * @param  $id
     * @param  $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function postCustomerTicketReply($id, $input)
    {
        $freshdeskTicketValidator = new FreshdeskTicketValidator;

        $freshdeskTicketValidator->validateInput('create_customer_ticket_reply', $input);

        unset($input['g_recaptcha_response']);

        unset($input[Constants::IS_PA_PG_ENABLED]);

        $this->checkTicketBelongsToEmail($id);

        $input[Constants::USER_ID] = $this->getRequesterIdFromSession($id);

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, Constants::RZPIND);

        $ticketReplyResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicketReply($id, $input, $url);

        return $this->filterCustomerResponse($ticketReplyResponse);
    }

    /**
     * @param  $id
     * @param  $input
     * @throws BadRequestValidationFailureException
     */
    public function getCustomerTicketConversations($id, $input)
    {
        $input[Constants::PAGE] = $input[Constants::PAGE] ?? 1;

        $input[Constants::PER_PAGE] = $input[Constants::PER_PAGE] ?? 10;

        (new Validator)->validateInput('get_customer_ticket_conversations', $input);

        $this->checkTicketBelongsToEmail($id);

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, Constants::RZPIND);

        $queryParams = [
            Constants::PAGE     => $input[Constants::PAGE],
            Constants::PER_PAGE => $input[Constants::PER_PAGE]
        ];

        $conversations = $this->app[Constants::FRESHDESK_CLIENT]->getTicketConversations($id, $queryParams, $url);

        $conversations = $this->rewriteFreshdeskConversationsForCustomerTicket($conversations);

        return [
            'count' => count($conversations),
            'items' => $this->rewriteFreshdeskConversationsForCustomerTicket($conversations)
        ];
    }

    protected function createTicketResponseFromReceivedArrays(array $tickets, $count, $isNodalStructureFeatureEnabled = false)
    {

        $response = [];

        $counter = 1;

        foreach ($tickets as $ticket)
        {
            if (isset($ticket['id']) === false)
            {
                continue;
            }

            $ticketResponse = [
                'number'         => $ticket['id'],
                'status'         => $this->getTicketStatusForCustomer($ticket),
                'subject'        => $ticket['subject'],
                'source'         => $ticket['source'],
                'type'           => $ticket['type'],
                'payment_id'     => $ticket['custom_fields']['cf_razorpay_payment_id'],
                'refund_id'      => $ticket['custom_fields']['cf_refund_id'],
                'order_id'       => $ticket['custom_fields']['cf_order_id'],
                'transaction_id' => $ticket['custom_fields']['cf_transaction_id'],
                'created_at'     => $ticket['created_at'],
                'updated_at'     => $ticket['updated_at'],
            ];

            if ($isNodalStructureFeatureEnabled === true)
            {
                $ticketResponse[Constants::ACTION] = $this->getActionApplicableForNodalFlow($ticket);

                $ticketResponse[Constants::STATUS] = $this->getTicketStatusForCustomerNodalStructure($ticket);

                $ticketResponse[Constants::TICKET_TAGS] = $ticket[Constants::TICKET_TAGS];

                $ticketResponse[Constants::REQUESTER_ID] = $ticket[Constants::REQUESTER_ID];

                $ticketResponse[Constants::DESCRIPTION] = $ticket['description_text'];

                $ticketResponse[Constants::CF_REQUESTER_CONTACT_RAZORPAY_REASON] = $ticket[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTER_CONTACT_RAZORPAY_REASON] ?? '';

                $ticketResponse[Constants::DUE_BY] = $ticket[Constants::DUE_BY];

                if(empty($ticket[Constants::CUSTOM_FIELDS][Constants::PAYMENT_ID]) === false)
                {
                    $response[] = $ticketResponse;
                }
            }
            else
            {
                $response[] = $ticketResponse;
            }

            if ($counter >= $count)
            {
                break;
            }

            $counter++;
        }

        if (count($response) === 0)
        {
            return [];
        }

        return $response;
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    public function raiseGrievance($input)
    {
        (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('email_compulsory', $input);

        $isNodalStructureFeatureEnabled = $this->isNodalStructureEnabled($input[Constants::IS_PA_PG_ENABLED]??false);

        if ($isNodalStructureFeatureEnabled === false)
        {
            (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('raise_grievance', $input);
        }
        else
        {
            (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('raise_grievance_nodal_structure', $input);

            unset($input['g_recaptcha_response']);

            unset($input[Constants::IS_PA_PG_ENABLED]);

            $this->checkTicketBelongsToEmail($input[Entity::ID]);

            $this->checkActionAllowed($input[Entity::ID], $input[Constants::ACTION]);

            $function = 'perform' . studly_case($input[Constants::ACTION]) . 'Task';

            $input = $this->$function($input);
        }

        $ticketId = $input['id'];

        $customerDescription = trim($input['description']);

        $email = $input['email'];

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, Constants::RZPIND);

        $ticketFound = false;

        $currentTicket = $this->app[Constants::FRESHDESK_CLIENT]->fetchTicketById($ticketId, $url);

        if (isset($currentTicket['id']) === true)
        {
            $ticket = $currentTicket;

            $ticketFound = true;

            $input['group_id'] = $this->getGrievanceGroupIdForCustomerTicket($isNodalStructureFeatureEnabled, $input[Constants::ACTION] ?? null);

            $input[Constants::TICKET_TAGS] = $this->getTagsForCustomerTicket($currentTicket , $input[Constants::ACTION] ?? null);
        }

        if ($ticketFound === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);
        }

        if ($this->validateTicketBelongsToEmail($ticket, $email) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);
        }

        if ($this->getTicketStatusForCustomer($ticket) === TicketStatus::CLOSED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_ALREADY_CLOSED);
        }

        $data = $input;

        unset($data[Entity::ID]);
        unset($data[Constants::DESCRIPTION]);
        unset($data[Constants::EMAIL]);
        unset($data[Constants::ACTION]);
        unset($data[Constants::CONTACT]);
        unset($data[Constants::OTP]);
        unset($input['g_recaptcha_response']);
        unset($input[Constants::IS_PA_PG_ENABLED]);
        $this->trace->info(TraceCode::FRESHDESK_CREATE_TICKET_INPUT_LOG, $data);
        $data['status']   = 2;
        $data['priority'] = 4;

        if (isset($data['group_id']) === true)
        {
            $data['group_id'] = (int) $data['group_id'];
        }

        $ticket = $this->app[Constants::FRESHDESK_CLIENT]->updateTicketV2($ticketId, $data, $url);

        $this->validateGrievanceResponse($ticket);

        $noteData = [
            'body'    => $customerDescription,
            'private' => false,
        ];

        $noteResponse = $this->app[Constants::FRESHDESK_CLIENT]->addNoteToTicket($ticketId, $noteData, $url);

        $this->validateNoteResponse($noteResponse);

        return [
            'number'         => $ticket['id'],
            'status'         => $this->getTicketStatusForCustomer($ticket),
            'subject'        => $ticket['subject'],
            'source'         => $ticket['source'],
            'type'           => $ticket['type'],
            'tags'           => $ticket['tags'],
            'due_by'         => $ticket['due_by'],
            'description'    => $ticket['description'],
            'payment_id'     => $ticket['custom_fields']['cf_razorpay_payment_id'],
            'refund_id'      => $ticket['custom_fields']['cf_refund_id'],
            'order_id'       => $ticket['custom_fields']['cf_order_id'],
            'transaction_id' => $ticket['custom_fields']['cf_transaction_id'],
            'created_at'     => $ticket['created_at'],
            'updated_at'     => $ticket['updated_at'],
        ];
    }

    public function internalFetchMerchantFreshdeskTickets($input)
    {
        (new Validator)->validateInput(__FUNCTION__, $input);

        $tickets = $this->repo->merchant_freshdesk_tickets->fetch($input);

        return $tickets->toArrayPublic();
    }

    public function internalPostTicketV2($input)
    {
        $input = $this->makeInputForInternalPostTicket($input);

        $fdInstance = $input[Constants::FD_INSTANCE];

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, $fdInstance);

        $input = $this->addActivationStatusToInput($input, $fdInstance);

        unset($input[Constants::FD_INSTANCE]);

        $input = $this->addRemoveValuesBeforeTicketCreation($input, $fdInstance);

        $freshdeskTicketResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicket($input, $url);

        $this->validateTicketResponse($freshdeskTicketResponse);

        $freshdeskTicketResponse[Entity::ID] = "".$freshdeskTicketResponse[Entity::ID];

        return $freshdeskTicketResponse;
    }

    public function addNoteToTicket($ticketId, $input)
    {

        // verify input body
        (new FreshdeskTicketValidator)->setStrictFalse()->validateInput('add_note', $input);

        $isPrivate = (boolean) $input['private'];

        $customerDescription = trim($input['description']);

        $noteData = [
            'body'    => $customerDescription,
            'private' => $isPrivate,
        ];

        // add note to ticket
        $noteResponse = $this->app[Constants::FRESHDESK_CLIENT]->addNoteToTicket($ticketId, $noteData);


        // validate note response
        $this->validateNoteResponse($noteResponse, true);

        return $noteResponse;
    }

    public function getAgentDetailForFreshdeskTicket($ticketId)
    {
        $freshdeskTicketResponse = $this->app[Constants::FRESHDESK_CLIENT]->fetchTicketById($ticketId);

        $this->validateTicketResponse($freshdeskTicketResponse, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);

        $responderID = $freshdeskTicketResponse[Constants::RESPONDER_ID];

        if (empty($responderID) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_ASSIGNED);
        }

        $agentDetail = $this->app[Constants::FRESHDESK_CLIENT]->fetchAgentById($responderID);

        $this->validateFetchAgentDetailResponse($agentDetail);

        // replacing the below implementation by findByOrgIdAndEmail since one email can be part of multiple orgs
        // using Razorpay Org id as default org for now,
        // TODO: this needs to be fixed by the code owner to get the right org for the email based on the flow
        // $admin =  (new \RZP\Models\Admin\Admin\Repository)->findByEmail($agentDetail[Constants::CONTACT][Constants::EMAIL]);
        $admin = $this->repo->admin->findByOrgIdAndEmail(
            Org\Entity::RAZORPAY_ORG_ID,
            $agentDetail[Constants::CONTACT][Constants::EMAIL]);

        return [
            Constants::AGENT_ID             => $admin->getPublicId(),
            Constants::FRESHDESK_AGENT_ID   => $responderID,
            Constants::AGENT_NAME           => $admin->getName(),
        ];
    }

    public function checkEligibilityForPostTicket($type)
    {
        $variant = $this->app['razorx']->getTreatment($this->app['basicauth']->getMerchantId(),
                                                      Constants::RAZORX_FLAG_TO_LIMIT_NO_OF_OPEN_FRESHDESK_TICKETS,
                                                      $app['rzp.mode'] ?? Mode::LIVE);

        $this->trace->info(TraceCode::FRESHDESK_LIMIT_OPEN_TICKETS_EXPERIMENT_STATUS, [
            'variant'                           => $variant,
        ]);

        if ($variant === 'on')
        {
            $payload = [
                Constants::STATUS => 2,
            ];

            $openTickets = $this->getTickets($payload, $type);

            $openMerchantTicketCount = 0;

            foreach ($openTickets[Constants::RESULTS] as $ticket)
            {
                if ($ticket[Constants::CUSTOM_FIELDS][Constants::CF_CREATED_BY] != Constants::AGENT)
                {
                    $openMerchantTicketCount++;
                }
            }

            $this->trace->info(TraceCode::FRESHDESK_EXISTING_OPEN_TICKETS, ['count' => $openMerchantTicketCount]);

            if ($openMerchantTicketCount >= Constants::MAX_OPEN_TICKETS_FOR_MERCHANT)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_OPEN_TICKETS_LIMIT_EXCEEDED);
            }
        }
    }

    public function addRemoveValuesBeforeTicketCreation($input, $fdInstance)
    {
        if ($fdInstance === Constants::RZPIND)
        {
            if (array_key_exists(Constants::CF_CATEGORY, $input[Constants::CUSTOM_FIELDS]) === false)
            {
                $input[Constants::CUSTOM_FIELDS][Constants::CF_CATEGORY] = Constants::DEFAULT_CF_CATEGORY;
            }
            else
            {
                if ($input[Constants::CUSTOM_FIELDS][Constants::CF_CATEGORY] === '' or $input[Constants::CUSTOM_FIELDS][Constants::CF_CATEGORY] === null)
                {
                    $input[Constants::CUSTOM_FIELDS][Constants::CF_CATEGORY] = Constants::DEFAULT_CF_CATEGORY;
                }
            }
        }

        $cf_new_category = $input[Constants::CUSTOM_FIELDS][Constants::CF_NEW_CATEGORY] ?? null;
        $cf_category = $input[Constants::CUSTOM_FIELDS][Constants::CF_CATEGORY] ?? null;

        $category = $cf_new_category ?? $cf_category;

        if ($category === Constants::ACTIVATIONS_DOCUMENT_REVIEW)
        {
            $leadScore = optional($this->auth->getMerchant()->merchantBusinessDetail)->getTotalLeadScore() ?? 0;
            $input[Constants::CUSTOM_FIELDS][Constants::LEAD_SCORE] = $leadScore;

            $input[Constants::TICKET_PRIORITY]  = Priority::getValueForPriorityString(Priority::MEDIUM);

            if ($leadScore > 55)
            {
                if (in_array($input[Constants::CUSTOM_FIELDS][Constants::CF_CASE_TRIGGER],
                             [EscalationConstants::CMMA_SOFT_LIMIT_BREACH, EscalationConstants::CMMA_HARD_LIMIT_BREACH]))
                {
                    $input[Constants::TICKET_PRIORITY]  = Priority::getValueForPriorityString(Priority::URGENT);
                }
                elseif (in_array($input[Constants::CUSTOM_FIELDS][Constants::CF_CASE_TRIGGER],
                                 [EscalationConstants::AMP, EscalationConstants::AUTO_KYC_FAILURE_TRIGGER]))
                {
                    $input[Constants::TICKET_PRIORITY]  = Priority::getValueForPriorityString(Priority::HIGH);
                }
            }

        }

        return $input;
    }

    public function addActivationStatusToInput($input, $fdInstance): array
    {
        if ($fdInstance === Constants::RZPIND)
        {
            $activationStatus = $this->merchant->merchantDetail->getActivationStatus();

            if ($activationStatus === null)
            {
                $input[Constants::CUSTOM_FIELDS][Constants::CF_MERCHANT_ACTIVATION_STATUS] = Constants::DEFAULT_ACTIVATION_STATUS;
            }
            else
            {
                $input[Constants::CUSTOM_FIELDS][Constants::CF_MERCHANT_ACTIVATION_STATUS] = $activationStatus;
            }
        }

        return $input;
    }

    public function insertIntoDB($input)
    {
        (new Validator)->validateInput('insert_into_db', $input);

        $ticketEntity = new Entity;

        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $ticketEntity->merchant()->associate($merchant);

        $ticketEntity->setId($input[Entity::ID]);

        $ticketEntity->setTicketId($input[Entity::TICKET_ID]);

        $ticketEntity->setTicketType($input[Entity::TYPE]);

        $ticketEntity->setTicketDetails($input[Entity::TICKET_DETAILS]);

        $this->repo->saveOrFail($ticketEntity);

        return ['success' => true];
    }

    public function postTicketV2($type, $input, $keepHtmlTags = false)
    {
        $function = 'makeInputFor' . studly_case($type) . 'PostTicket';

        if ($keepHtmlTags === true and $type == Type::SUPPORT_DASHBOARD)
        {
            $input = $this->$function($input, true);
        }
        else
        {
            $input = $this->$function($input);
        }

        $this->checkEligibilityForPostTicket($type);

        if($this->merchant->isSignupViaEmail() === true)
        {
            (new Validator)->validateInput('create_' . studly_case($type) . '_ticket', $input);
        }
        else
        {
            (new Validator)->validateInput('create_' . studly_case($type) . '_ticket_mobile_signup', $input);
        }

        $fdInstance = $this->getFdInstanceFromTypeAndInput($type, $input);

        $input = $this->addActivationStatusToInput($input, $fdInstance);

        $input = $this->appendUserEmailToCCEmails($input);

        $url = $this->getFreshdeskUrlType($type, $fdInstance);

        $log = [Constants::FD_INSTANCE => $fdInstance];

        $log = $this->freshdeskCreateTicketInputLog($log, $input);

        $this->trace->info(TraceCode::FRESHDESK_CREATE_TICKET_INPUT_LOG, $log);

        unset($input[Constants::FD_INSTANCE]);

        $input = $this->addRemoveValuesBeforeTicketCreation($input, $fdInstance);

        $ticketCreateResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicket($input, $url);

        $ticketCreateResponse[Constants::FD_INSTANCE] = $fdInstance;

        $this->validateTicketResponse($ticketCreateResponse);

        $ticketDetails = [
            Constants::FD_INSTANCE   => $fdInstance,
            Constants::FR_DUE_BY     => $this->getExpectedFirstResponseDueBy($ticketCreateResponse),
        ];

        $ticketEntity = (new Core)->create([
            Entity::TICKET_ID       => stringify($ticketCreateResponse['id']),
            Entity::TICKET_DETAILS  => $ticketDetails,
            Entity::TYPE            => $type,
        ], $this->merchant->getId(), true);

        $this->trace->info(TraceCode::TICKET_DETAILS, $this->getRedactedTicket($ticketEntity));

        try
        {
            $this->notifyMerchantIfApplicable($ticketEntity, Notifications\Support\Events::TICKET_CREATED, $this->extractRequesterItem($ticketCreateResponse));
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException($throwable);
        }

        return $this->rewriteFreshdeskTicket($ticketCreateResponse, $ticketEntity, $type);
    }

    protected function freshdeskCreateTicketInputLog($log, $input)
    {
        if (array_key_exists(Constants::CF_REQUESTOR_CATEGORY, $input[Constants::CUSTOM_FIELDS]))
        {
            $log[Constants::CF_REQUESTOR_CATEGORY] = $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_CATEGORY];
        }

        if (array_key_exists(Constants::CF_REQUESTOR_SUBCATEGORY, $input[Constants::CUSTOM_FIELDS]))
        {
            $log[Constants::CF_REQUESTOR_SUBCATEGORY] = $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY];
        }

        if (array_key_exists(Constants::CF_REQUESTOR_ITEM, $input[Constants::CUSTOM_FIELDS]))
        {
            $log[Constants::CF_REQUESTOR_ITEM] = $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM];
        }

        if (array_key_exists(Constants::CF_NEW_REQUESTOR_CATEGORY, $input[Constants::CUSTOM_FIELDS]))
        {
            $log[Constants::CF_NEW_REQUESTOR_CATEGORY] = $input[Constants::CUSTOM_FIELDS][Constants::CF_NEW_REQUESTOR_CATEGORY];
        }

        if (array_key_exists(Constants::CF_NEW_REQUESTOR_SUBCATEGORY, $input[Constants::CUSTOM_FIELDS])) {
            $log[Constants::CF_NEW_REQUESTOR_SUBCATEGORY] = $input[Constants::CUSTOM_FIELDS][Constants::CF_NEW_REQUESTOR_SUBCATEGORY];
        }

        if (array_key_exists(Constants::CF_NEW_REQUESTOR_ITEM, $input[Constants::CUSTOM_FIELDS]))
        {
            $log[Constants::CF_NEW_REQUESTOR_ITEM] = $input[Constants::CUSTOM_FIELDS][Constants::CF_NEW_REQUESTOR_ITEM];
        }

        return $log;
    }

    public function getTicketRzpEnitity($ticketId, $type, $merchantId, $fdInstance)
    {
        $tickets = $this->repo->merchant_freshdesk_tickets->fetch([
            Entity::TYPE => $type,
            Entity::TICKET_ID => $ticketId,
        ], $merchantId);

        foreach ($tickets as $ticket)
        {
            if ($ticket->getFdInstance() == $fdInstance)
            {
                return $ticket;
            }
        }

        return null;
    }

    public function getTicket($id, array $input, $type): array
    {
        $ticketEntity = $this->repo->merchant_freshdesk_tickets->fetch([
            Entity::TYPE        => $type,
            Entity::ID          => $id,
        ], $this->merchant->getId())->firstOrFail();

        $fdInstance = $ticketEntity->getFdInstance();

        $url = $this->getFreshdeskUrlType($type, $fdInstance);

        $ticketWithStats = $this->app[Constants::FRESHDESK_CLIENT]->getTicketWithStats($ticketEntity->getTicketId(), $url);

        if (empty($ticketWithStats) === false)
        {
            $ticketWithStats[Constants::FD_INSTANCE] = $fdInstance;
        }

        $response = $ticketWithStats ?? [];

        if(array_key_exists(Constants::REQUESTER_ID, $response) === true) {
            $this->app['cache']->set('freshdesk_ticket' .'_'. $ticketEntity->getId() .'_'.'requester_id', $response[Constants::REQUESTER_ID], 86400);
        }

        return $this->rewriteFreshdeskTicket($response, $ticketEntity, $type);
    }

    public function getTickets(array $input, $type)
    {
        $input[Constants::PAGE] = $input[Constants::PAGE] ?? 1;

        $input[Constants::STATUS] = $input[Constants::STATUS] ?? null;

        (new Validator)->validateInput('get_' . studly_case($type) . '_tickets' , $input);

        if (array_key_exists(Constants::CF_CREATED_BY, $input) and
            $input[Constants::CF_CREATED_BY] === TicketCreatedBy::AGENT
        )
        {
            $tickets = $this->repo->merchant_freshdesk_tickets->fetch([
                                                                          Entity::TYPE       => $type,
                                                                          Entity::CREATED_BY => $input[Constants::CF_CREATED_BY],
                                                                          Entity::STATUS     => TicketStatus::getDatabaseStatusMappingForStatusString(TicketStatus::OPEN),
                                                                      ], $this->merchant->getId());

            if ($tickets->count() === 0)
            {
                return [
                    Constants::RESULTS => [],
                    Constants::TOTAL   => 0
                ];
            }
        }

        $queryString = $this->buildQueryStringForGetTickets($input);

        $queryParams = [
            Constants::QUERY => $queryString,
            Constants::PAGE  => $input[Constants::PAGE],
        ];

        $allTickets = $this->getTicketsFromTypeOrFdInstances($queryParams, $type);

        $allTickets = $this->applyAdditionalFilter($allTickets, $input);

        // Sorting the tickets in descending order of created_at
        $this->sortTicketsInDescendingOrderOfCreatedAt($allTickets);

        //
        // Order tickets by the following buckets
        // 1. Awaiting your response - MERCHANT_ACTION_STATUSES
        // 2. Active & Work in Progress - ACTIVE_STATUSES
        // 3. All other tickets
        //
        $statusGroupedAndOrderedTickets = $this->groupTicketsInBucketsAndOrderFinalList($allTickets);

        $rewrittenTickets = $this->rewriteFreshdeskTicketsBulk($statusGroupedAndOrderedTickets, $type);

        $ticketsResponse = [
            Constants::RESULTS => $rewrittenTickets,
            Constants::TOTAL   => count($rewrittenTickets)
        ];

        return $ticketsResponse;
    }

    protected function applyAdditionalFilter($allTickets, $input)
    {
        // Filter on category
        $allTickets = $this->additionalFilterOnKey($allTickets, $input[Constants::CF_REQUESTOR_CATEGORY] ?? "", Constants::CUSTOM_FIELDS . '.' . Constants::CF_REQUESTOR_CATEGORY);

        // Filter on subcategory
        $allTickets = $this->additionalFilterOnKey($allTickets, $input[Constants::CF_REQUESTOR_SUBCATEGORY] ?? "", Constants::CUSTOM_FIELDS . '.' . Constants::CF_REQUESTOR_SUBCATEGORY);

        // Filter on new_category
        $allTickets = $this->additionalFilterOnKey($allTickets, $input[Constants::CF_NEW_REQUESTOR_CATEGORY] ?? "", Constants::CUSTOM_FIELDS . '.' . Constants::CF_NEW_REQUESTOR_CATEGORY);

        // Filter on new_subcategory
        $allTickets = $this->additionalFilterOnKey($allTickets, $input[Constants::CF_NEW_REQUESTOR_SUBCATEGORY] ?? "", Constants::CUSTOM_FIELDS . '.' . Constants::CF_NEW_REQUESTOR_SUBCATEGORY);

        return $allTickets;
    }

    public function getConversations($id, array $input, $type): array
    {
        $input[Constants::PAGE] = $input[Constants::PAGE] ?? 1;

        $input[Constants::PER_PAGE] = $input[Constants::PER_PAGE] ?? 10;

        (new Validator)->validateInput('get_' . studly_case($type) . '_conversations', $input);

        $ticketEntity = $this->repo->merchant_freshdesk_tickets->fetch([
            Entity::TYPE        => $type,
            Entity::ID          => $id,
        ], $this->merchant->getId())->firstOrFail();

        $fdInstance = $ticketEntity->getFdInstance();

        $url = $this->getFreshdeskUrlType($type, $fdInstance);

        $queryParams = [
            Constants::PAGE     => $input[Constants::PAGE],
            Constants::PER_PAGE => $input[Constants::PER_PAGE]
        ];

        $queryParams = $type === Type::SUPPORT_DASHBOARD_X ? [] : $queryParams;

        $conversations = $this->app[Constants::FRESHDESK_CLIENT]->getTicketConversations($ticketEntity->getTicketId(), $queryParams, $url);

        return $this->rewriteFreshdeskConversations($conversations, $ticketEntity);
    }

    public function postTicketReply($id, array $input, $type): array
    {
        $input[Constants::USER_ID] = $this->app['cache']->get('freshdesk_ticket_'. $id .'_requester_id');

        $this->trace->info(TraceCode::FRESHDESK_TICKET_REQUESTER_ID_FROM_CACHE, ['user_id' => $input[Constants::USER_ID]]);

        $ticketEntity = $this->repo->merchant_freshdesk_tickets->fetch([
            Entity::TYPE        => $type,
            Entity::ID          => $id,
        ], $this->merchant->getId())->firstOrFail();

        $fdInstance = $ticketEntity->getFdInstance();

        $url = $this->getFreshdeskUrlType($type, $fdInstance);

        if ($input[Constants::USER_ID] == null)
        {
            $ticket = $this->app[Constants::FRESHDESK_CLIENT]->fetchTicketById($ticketEntity->getTicketId(), $url);
            if (array_key_exists(Constants::REQUESTER_ID, $ticket))
            {
                $input[Constants::USER_ID] = $ticket[Constants::REQUESTER_ID];
            }
        }
        // Converting user id to int
        if (isset($input[Constants::USER_ID]) === true)
        {
            $input[Constants::USER_ID] += 0;
        }

        (new Validator)->validateInput('create_' . studly_case($type) . '_ticket_reply', $input);

        $ticketReplyResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicketReply($ticketEntity->getTicketId(), $input, $url);

        $ticketReplyResponse[Constants::FD_INSTANCE] = $fdInstance;

        return $this->rewriteFreshdeskTicketReply($ticketReplyResponse, $ticketEntity);
    }

    public function postGrievance($id, $input, $type)
    {
        $ticketEntity = $this->repo->merchant_freshdesk_tickets->fetch([
            Entity::TYPE        => $type,
            Entity::ID          => $id,
        ], $this->merchant->getId())->firstOrFail();

        $fdInstance = $ticketEntity->getFdInstance();

        (new Validator)->validateInput('create_' . studly_case($type) . '_grievance', $input);

        $url = $this->getFreshdeskUrlType($type, $fdInstance);;

        $tags = $this->appendTagsToTicket($ticketEntity->getTicketId(), $fdInstance, Constants::GRIEVANCE_TAGS);

        $data = [
            Constants::TICKET_STATUS    => TicketStatus::getStatusMappingForStatusString(TicketStatus::PROCESSING),
            Constants::TICKET_PRIORITY  => Priority::getValueForPriorityString(Priority::URGENT),
            Constants::TICKET_TAGS      => $tags,
        ];

        $ticket = $this->app[Constants::FRESHDESK_CLIENT]->updateTicketV2($ticketEntity->getTicketId(), $data, $url);

        $this->validateGrievanceResponse($ticket);


        $replyRequest[Constants::BODY] = $input[Constants::DESCRIPTION];

        $replyRequest[Constants::ATTACHMENTS] = $input[Constants::ATTACHMENTS] ?? [];

        $this->app[Constants::FRESHDESK_CLIENT]->postTicketReply($ticketEntity->getTicketId(), $replyRequest, $url);


        return $this->rewriteFreshdeskTicket($ticket, $ticketEntity, $type);
    }

    public function processWebhook($event, $input)
    {
        return FreshdeskWebhookProcessor\Base::getProcessor($event)->process($input);
    }

    public function validateTicketResponse($response, string $errorCode= ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_CREATION_FAILED)
    {
        if (isset($response['id']) === true)
        {
            return;
        }

        if (isset($response['errors']))
        {
            throw new BadRequestException($errorCode, null, $response['errors']);
        }

        throw new Exception\ServerErrorException(null, ErrorCode::SERVER_ERROR_FRESHDESK_INTEGRATION_ERROR, $response);
    }

    protected function validateFetchAgentDetailResponse($response, string $errorCode= ErrorCode::BAD_REQUEST_FRESHDESK_AGENT_NOT_FOUND)
    {
        if (isset($response['id']) === true)
        {
            return;
        }

        if (isset($response['errors']))
        {
            throw new BadRequestException($errorCode, null, $response['errors']);
        }

        throw new Exception\ServerErrorException(null, ErrorCode::SERVER_ERROR_FRESHDESK_INTEGRATION_ERROR, $response);
    }

    protected function validateGrievanceResponse($response)
    {
        if ((isset($response['status']) === false) or ($response['status'] !== 2))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED);
        }

        if ((isset($response['priority']) === false) and ($response['priority'] !== 4))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED);
        }
    }

    protected function validateNoteResponse($response, $ignorePrivate=false)
    {
        // check for errors in response
        if (isset($response['errors']) !== false)
        {
            $this->trace->error(
                TraceCode:: FAILED_NOTE_RESPONSE_BODY,[
                    'error' => $response['errors']
                ]
            );

            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_ADD_NOTE_FAILED);
        }

        // no need to validate further if private note addition validation is ignored
        if ($ignorePrivate === true)
        {
            return;
        }

        if ((isset($response['private']) === false) or ($response['private'] !== false))
        {
            if(isset($response['private']) !== false)
            {
                if($response['private'] !== false)
                {
                    $this->trace->error(
                        TraceCode::FAILED_NOTE_RESPONSE_BODY, [
                        'error' => "response[private] !== false",
                        'response' => $response['private'],
                    ]);
                }
            }

            if(isset($response['private']) === false)
            {
                $this->trace->error(
                    TraceCode::  FAILED_NOTE_RESPONSE_BODY, [
                        'error' => "Missing private in response"
                    ]
                );
            }
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_ADD_NOTE_FAILED);
        }
    }

    protected function validateTicketBelongsToEmail($ticket, $email)
    {
        if ((isset($ticket['requester']) === true) and
            (isset($ticket['requester']['email']) === true) and
            ($ticket['requester']['email'] === $email))
        {
            return true;
        }

        return false;
    }

    protected function getQueryParamMerchantIdForSearchAPI($merchant = null): string
    {
        if ($merchant === null)
        {
            $merchantId = $this->auth->getMerchantId();
        }
        else
        {
            $merchantId = $merchant->getId();
        }

        //
        // We are now querying the new ticket field `cf_merchant_id_dashboard`
        // which is going to be filled with the prefix `merchant_dashboard`.
        // Example :
        // if MID : DdTVH1TtVoVyLO
        // then cf_merchant_id_dashboard : merchant_dashboard_DdTVH1TtVoVyLO
        //

        $midWithPrefix = Constants::MERCHANT_DASHBOARD . '_' . $merchantId;

        return $midWithPrefix;
    }

    protected function sortTicketsInDescendingOrderOfCreatedAt(array &$allTickets)
    {
        // Sorting the tickets in descending order of created_at
        usort($allTickets, function($a, $b){
            if ((empty($a[Constants::CREATED_AT]) === false) and
                (empty($b[Constants::CREATED_AT]) === false))
            {
                return strtotime($b[Constants::CREATED_AT]) - strtotime($a[Constants::CREATED_AT]);
            }

            // Default behaviour - in place
            return 0;
        });
    }

    protected function groupTicketsInBucketsAndOrderFinalList(array $allTickets): array
    {
        //
        // Order tickets by the following buckets
        // 1. Awaiting your response - MERCHANT_ACTION_STATUSES
        // 2. Active & Work in Progress - ACTIVE_STATUSES
        // 3. All other tickets
        //
        $merchantActionTickets = [];

        $activeTickets = [];

        $otherTickets = [];

        $statusGroupedAndOrderedTickets = [];

        array_map(function($ticket) use (&$merchantActionTickets, &$activeTickets, &$otherTickets) {
            $status = $ticket['status'] ?? null;

            if (in_array($status, Constants::ACTIVE_STATUSES, true) === true)
            {
                $activeTickets[] = $ticket;
            }
            else if (in_array($status, Constants::MERCHANT_ACTION_STATUSES, true) === true)
            {
                $merchantActionTickets[] = $ticket;
            }
            else
            {
                $otherTickets[] = $ticket;
            }
        }, $allTickets);

        $statusGroupedAndOrderedTickets = array_merge($statusGroupedAndOrderedTickets, $merchantActionTickets);

        $statusGroupedAndOrderedTickets = array_merge($statusGroupedAndOrderedTickets, $activeTickets);

        $statusGroupedAndOrderedTickets = array_merge($statusGroupedAndOrderedTickets, $otherTickets);

        return $statusGroupedAndOrderedTickets;
    }


    protected function getTicketsFromTypeOrFdInstances(array $queryParams, $type, $fdInstances = []): array
    {
        $instances = [];

        if (empty($type) === true && empty($fdInstances) === false)
        {
            $instances = $fdInstances;
        }
        else
        {
            $instances = Constants::FRESHDESK_INSTANCES[$type];
        }

        $allTickets = [];

        foreach ($instances as $fdInstance => $url)
        {
            $response = $this->app[Constants::FRESHDESK_CLIENT]->getTickets($queryParams, $url);

            $results = $response[Constants::RESULTS] ?? [];

            // Adding FD instance in each ticket
            array_walk(
                $results,
                function (&$value, $key, $fdInstanceKey) {
                    $value[Constants::FD_INSTANCE] = $fdInstanceKey;
                },
                $fdInstance
            );

            $allTickets = array_merge($allTickets, $results);
        }
        return $allTickets;
    }


    protected function preProcessInputCustomer(array &$input): string
    {
        $transactionId = $input[Constants::CUSTOM_FIELDS][Constants::TRANSACTION_ID];

        if (Str::startsWith($transactionId, 'pay_'))
        {
            $input[Constants::CUSTOM_FIELDS][Constants::PAYMENT_ID] = $transactionId;

            $idType = Constants::PAYMENT;
        }
        else if (Str::startsWith($transactionId, 'rfnd_'))
        {
            $input[Constants::CUSTOM_FIELDS][Constants::REFUND_ID] = $transactionId;

            $idType = Constants::REFUND;
        }
        else if (Str::startsWith($transactionId, 'order_'))
        {
            $input[Constants::CUSTOM_FIELDS][Constants::ORDER_ID] = $transactionId;

            $idType = Constants::ORDER;
        }
        else
        {
            $input[Constants::CUSTOM_FIELDS][Constants::TRANSACTION_ID] = $transactionId;

            $idType = Constants::TRANSACTION;
        }

        return $idType;
    }

    protected function updateStatusFields(array &$input)
    {
        $input = array_merge($input, self::STATUS_FIELDS);
    }

    protected function getFdInstanceFromTypeAndInput($type, array &$input)
    {
        return $this->getFdInstance($type,$input);
    }

    protected function getFdInstance($type,array &$input): string
    {
        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZPIND;

        $subCategoryFound = false;

        if (isset($input[Constants::CUSTOM_FIELDS]) === true)
        {
            if ( isset($input[Constants::CUSTOM_FIELDS][Constants::CF_QUERY]) === true ) {
                $subQuery = trim($input[Constants::CUSTOM_FIELDS][Constants::CF_QUERY]);

                if ($subQuery === Constants::CAPITAL_QUERY) {

                    $fdInstance = Constants::RZPCAP;

                    return $fdInstance;
                }
            }
            if (isset($input[Constants::CUSTOM_FIELDS][Constants::CF_PRODUCT]) === true )
            {
                $subProduct = trim($input[Constants::CUSTOM_FIELDS][Constants::CF_PRODUCT]);

                if ($subProduct === Constants::CAPITAL_QUERY) {

                    $fdInstance = Constants::RZPCAP;

                    return $fdInstance;
                }

            }

            if (isset($input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY]) === true)
            {

                $subCategory = $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY];

                foreach (self::FD_INSTANCE_VS_SUBCATEGORIES as $fdInstanceForSubcategories => $subcategories)
                {
                    if (in_array($subCategory, $subcategories) === true)
                    {
                        $fdInstance = $fdInstanceForSubcategories;
                        $subCategoryFound = true;
                    }
                }
            }
        }
        if ($subCategoryFound === false && $type === Type::SUPPORT_DASHBOARD_X)
        {
            return Constants::RZPX;
        }

        $fdInstance = $this->getMigratedFdInstanceIfApplicable($fdInstance, $input);

        $fdInstance = $this->getNewFdInstanceIfRzpSolAndRzpMerged($fdInstance, $this->merchant->getId());

        return $fdInstance;
    }

    /**
     * @param array $input
     * @param void $return
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function populateCustomFields(array &$input)
    {
        $mode = $input['mode'] ?? Mode::LIVE;

        $this->auth->setModeAndDbConnection($mode);

        unset($input['mode']);

        $category = $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_CATEGORY];

        switch ($category)
        {
            case Constants::CUSTOMER:

                $paymentId = $input[Constants::CUSTOM_FIELDS][Constants::PAYMENT_ID];

                $customFields = $this->getCustomFieldsFromPaymentId($paymentId);

                if (empty($customFields) === true)
                {
                    throw new BadRequestValidationFailureException(ErrorCode::FRESHDESK_TICKET_INVALID_ID,
                        Constants::TRANSACTION_ID,
                        [Constants::TRANSACTION_ID => $input[Constants::CUSTOM_FIELDS][Constants::TRANSACTION_ID]]
                    );
                }

                $input[Constants::CUSTOM_FIELDS] = array_merge($input[Constants::CUSTOM_FIELDS], $customFields);

                break;

            default:

                break;
        }
    }

    /**
     * @param string $paymentId
     * @param array $return
     */
    protected function getCustomFieldsFromPaymentId($paymentId): array
    {
        $customFields = [];

        Payment\Entity::stripSignWithoutValidation($paymentId);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_ID, ['payment_id' => $paymentId]);

        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        if (empty($payment) === false)
        {
            $customFields[Constants::PAYMENT_ID]     = $payment->getPublicId();
            $customFields[Constants::CF_MERCHANT_ID] = $payment->getMerchantId();

            $data = $payment->toArrayPublic();

            if($data['order_id'] !== null)
            {
                $customFields[Constants::ORDER_ID] = $data['order_id'];
            }
            if ($payment->getEmail() !== null)
            {
                $customFields[Constants::PAYMENT_CUSTOMER_EMAIL] = $payment->getEmail();
            }
            if ($payment->getContact() !== null)
            {
                $customFields[Constants::PAYMENT_CUSTOMER_PHONE] = $payment->getContact();
            }

            $refunds = $payment->refunds;

            $refundIds = isset($refunds) ? $refunds->getPublicIds() : [];

            if (empty($refundIds) === false)
            {
                $customFields[Constants::REFUND_ID] = implode(', ', $refundIds);
            }
        }

        return $customFields;
    }

    protected function buildQueryStringForGetTickets($input): string
    {
        $status = $input[Constants::STATUS];

        $customStringsListForQuery = Constants::CUSTOM_FIELDS_LIST_FOR_QUERY;

        $customStringsPresent = $this->getCustomFieldsFromInput($customStringsListForQuery, $input);

        // Adding Merchant ID in query with required prefix
        $queryString = '"custom_string:' . $this->getQueryParamMerchantIdForSearchAPI();

        // Adding status filter if necessary
        if (empty($status) === false) {
            $queryString .= ' AND (';

            if (is_array($status) === true) {
                foreach ($status as $index => $value) {
                    $queryString .= ($index === 0) ? 'status:' . $value : ' OR status:' . $value;
                }
            } else {
                $queryString .= 'status:' . $status;
            }

            $queryString .= ')';
        }

        // Adding custom fields in filter
        foreach ($customStringsPresent as $key => $values)
        {
            $queryString .= ' AND custom_string:\'' . $customStringsPresent[$key] . '\'';
        }

        if (array_key_exists(Constants::TICKET_TAGS, $input) === true)
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

    protected function getCustomFieldsFromInput(array $fields, $input)
    {
        //This list will contain custom fields received from input
        $finalFields = [];

        //Creating list of keys from input values
        $keys = array_keys($input);

        //Converting values of array to string
        $stringKeys = array_map('strval', $keys);

        foreach($fields as $key => $value)
        {
            if ((in_array($value, $stringKeys) === true) and
                (strlen($input[$value]) != 0))
            {
                array_push($finalFields, $input[$value]);
            }
        }
        return $finalFields;
    }

    protected function rewriteFreshdeskTicket(array $response, Entity $ticket, $type = Type::SUPPORT_DASHBOARD)
    {
        if (isset($response[Entity::ID]) === true)
        {
            $response[Entity::ID] = $ticket->getId();

            $response[Entity::TICKET_ID] = $ticket->getTicketId();

            $response[Constants::FR_DUE_BY] = $ticket->getTicketDetails()[Constants::FR_DUE_BY] ?? $response[Constants::FR_DUE_BY];

            if( $type === Type::SUPPORT_DASHBOARD && $ticket->getFdInstance() === Constants::RZPCAP)
            {
                if ( $this->isCapitalExperimentEnabled($this->auth->getMerchantId()) ===  false )
                {
                    $response[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM] = $response[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY];
                    $response[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY] = Constants::SUBCATEGORY_CAPITAL;
                }
                else
                {
                    if (empty($response[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM]) === true)
                    {
                        $response[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM] = Constants::SUBCATEGORY_CAPITAL;
                    }
                }
            }
        }

        return $response;
    }

    protected function rewriteFreshdeskConversations($conversations, $ticketEntity)
    {
        return array_values(array_filter(array_map(function ($conversation) use ($ticketEntity) {
            return $this->rewriteFreshdeskConversation($conversation, $ticketEntity);
        }, $conversations)));
    }

    protected function rewriteFreshdeskConversationsForCustomerTicket($conversations)
    {
        return array_values(array_filter(array_map(function ($conversation)  {
            return $this->filterCustomerResponse($conversation);
        }, $conversations)));
    }

    protected function rewriteFreshdeskConversation($conversation, $ticketEntity)
    {
        if (isset($conversation['id']) === true)
        {
            $conversation['id'] = 'redacted';
        }

        if (isset($conversation['ticket_id']) === true && empty($ticketEntity) === false)
        {
            $conversation['ticket_id'] = $ticketEntity->getId();
        }

        if (isset($conversation['private']) === true and $conversation['private'] == true) return null;

        return $conversation;
    }

    protected function rewriteFreshdeskTicketReply($ticketReplyResponse, $ticketEntity)
    {
        if (isset($ticketReplyResponse['id']) === true)
        {
            $ticketReplyResponse['id'] = 'redacted';
        }

        if (isset($ticketReplyResponse['ticket_id']) === true && empty($ticketEntity) === false)
        {
            $ticketReplyResponse['ticket_id'] = $ticketEntity->getId();
        }

        return $ticketReplyResponse;
    }

    /**
     * @param $status
     * @return string
     */


    protected function rewriteFreshdeskTicketsBulk(array $ticketsResponse, $type)
    {
        $freshdeskTicketIds = [];

        foreach ($ticketsResponse as $ticket)
        {
            if(empty($ticket[Constants::CUSTOM_FIELDS][Constants::CF_TICKET_QUEUE]) === false)
            {
                if ($ticket[Constants::CUSTOM_FIELDS][Constants::CF_TICKET_QUEUE] !== Constants::TICKET_QUEUE_INTERNAL)
                {
                    array_push($freshdeskTicketIds, $ticket['id']);
                }
            }
            else
            {
                array_push($freshdeskTicketIds, $ticket['id']);
            }
        }

        $freshdeskTicketIds = array_values(array_unique($freshdeskTicketIds));

        if (count($freshdeskTicketIds) === 0)
        {
            return [];
        }


        $tickets = $this->repo->merchant_freshdesk_tickets->fetch([
            Entity::TICKET_ID   => $freshdeskTicketIds,
            Entity::TYPE        => $type,
        ], $this->merchant->getId());



        $freshdeskTicketIdRazorpayTicketMap = [];

        foreach($tickets as $ticket)
        {
            $freshdeskTicketIdRazorpayTicketMap[$ticket->getTicketId()] = $ticket;

        }

        $response = [];

        foreach ($ticketsResponse as $ticket)
        {
            if (isset($ticket['id']) === false)
            {
                continue;
            }

            $ticket['id'] = stringify($ticket['id']);

            if (array_key_exists($ticket['id'], $freshdeskTicketIdRazorpayTicketMap) === false)

            {
                continue;
            }

            $rewrittenTicket = $this->rewriteFreshdeskTicket($ticket, $freshdeskTicketIdRazorpayTicketMap[$ticket['id']], $type);


            array_push($response, $rewrittenTicket);
        }

        return $response;
    }


    protected function makeInputForSupportDashboardPostTicket($input, $keepHtmlTags = false)
    {
        $input = $this->addMerchantDetailsToInput($input);

        $input = $this->modifyInputForPluginMerchants($input, $this->auth->getMerchantId(), Type::SUPPORT_DASHBOARD);

        $input['custom_fields'][Constants::CF_MERCHANT_ID_DASHBOARD] = $this->getQueryParamMerchantIdForSearchAPI();

        $input['custom_fields'][Constants::CF_MERCHANT_ID] = $this->auth->getMerchantId();

        $input['priority'] = 1;

        if (empty ($input[Constants::TICKET_STATUS]) === true)
        {
            $input[Constants::TICKET_STATUS] = TicketStatus::getStatusMappingForStatusString(TicketStatus::PROCESSING);
        }

        $input = $this->modifyRequestForCapital($input);

        $this->getGroupIdForTicketInput($input);

        if ($keepHtmlTags === false)
        {
            //Removing HTML tags in description
            $input['description'] = strip_tags($input['description'], '<b><br>');
        }

        return $input;
    }

    protected function makeInputForInternalPostTicket($input)
    {
        $input['custom_fields'][Constants::CF_MERCHANT_ID] = $this->auth->getMerchantId();

        if (empty ($input[Constants::TICKET_STATUS]) === true)
        {
            $input[Constants::TICKET_STATUS] = TicketStatus::getStatusMappingForStatusString(TicketStatus::PROCESSING);
        }

        return $input;
    }

    protected function getGroupIdForTicketInput(&$input)
    {
        if ($this->validateFdInstanceFromTicketInput($input, Constants::RZPSOL) === true)
        {
            /* fdInstance will become rzpind if merge(rzpind-rzpsol) is 'on' for this account */
            $fdInstance = $this->getNewFdInstanceIfRzpSolAndRzpMerged(Constants::RZPSOL, $this->merchant->getId());

            $input[Constants::GROUP_ID] = $this->getGroupIdForFdInstanceAndTicketInput($input, $fdInstance);
        }
        if ($this->validateFdInstanceFromTicketInput($input, Constants::RZPCAP) === true)
        {
            $input[Constants::GROUP_ID] = $this->getGroupIdForFdInstanceAndTicketInput($input, Constants::RZPCAP);
        }
    }

    protected function makeInputForSupportDashboardXPostTicket($input)
    {
        if ($this->merchant->isSignupViaEmail() === true)
        {
            $input['email'] = $input['email'] ?? $this->merchant->getEmail();
        }
        else
        {
            $input['name'] = $this->merchant->getName() ?? '';
        }

        $input['phone'] = $input['phone'] ?? $this->merchant->merchantDetail->getContactMobile();

        $input['custom_fields']['cf_merchant_id_dashboard'] = $this->getQueryParamMerchantIdForSearchAPI();

        $input['priority'] = 1;

        $input['status'] = 2;

        return $input;
    }

    protected function getExpectedFirstResponseDueBy($freshdeskTicket)
    {
        $dimensions = $this->getFirstResponseTimeDimensions($freshdeskTicket);

        $cacheKey = $this->getFirstResponseTimeAverageCacheKey($dimensions);

        $averageFrResponseTime = $this->app['cache']->get($cacheKey);

        if ($averageFrResponseTime !== null)
        {
            return $this->getTimeInFreshdeskFormat(time() + $averageFrResponseTime);
        }

        return $freshdeskTicket[Constants::FR_DUE_BY];
    }

    protected function getFirstResponseTimeAverageCacheKey($dimensions)
    {
        return sprintf(Constants::CACHE_KEY_FIRST_RESPONSE_TIME_AVERAGE, $dimensions[Constants::CF_REQUESTOR_SUBCATEGORY], $dimensions[Constants::PRIORITY]);
    }

    protected function getFirstResponseTimeDimensions($freshdeskTicket)
    {
        $dimensions = [
            Constants::CF_REQUESTOR_SUBCATEGORY => $freshdeskTicket[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY] ?? $freshdeskTicket[Constants::CUSTOM_FIELDS][Constants::CF_SUBCATEGORY] ?? 'default',
            Constants::PRIORITY                 => $freshdeskTicket[Constants::PRIORITY],
        ];

        if (is_int($dimensions[Constants::PRIORITY]) === true)
        {
            $dimensions[Constants::PRIORITY] = Priority::getPriorityStringForValue($dimensions[Constants::PRIORITY]);
        }

        return $dimensions;
    }

    protected function getTimeInFreshdeskFormat($time)
    {
        return strftime(Constants::FRESHDESK_TIME_FORMAT, $time);
    }

    protected function validateFdInstanceFromTicketInput($input, $fd_instance): bool
    {
        if (isset($input[Constants::CUSTOM_FIELDS]) === false)
        {
            return false;
        }

        $customFields = $input[Constants::CUSTOM_FIELDS];

        if (isset($customFields[Constants::CF_REQUESTOR_SUBCATEGORY]) === false)
        {
            return false;
        }

        $category = $customFields[Constants::CF_REQUESTOR_SUBCATEGORY];

        return (in_array($category, self::FD_INSTANCE_VS_SUBCATEGORIES[$fd_instance], true) === true);
    }

    protected function getGroupIdForFdInstanceAndTicketInput($input, $fd_instance): int
    {
        $groupIds = $this->app['config']->get('applications.freshdesk.instance_subcategory_group_ids');

        $groupIdsForFDInstance = $groupIds[$fd_instance];

        if (array_key_exists("*", $groupIdsForFDInstance) === true)
        {
            $input['group_id'] = (int)$groupIdsForFDInstance["*"];
        }
        else
        {
            $input['group_id'] = (int)$groupIdsForFDInstance[$input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY]];
        }

        return $input['group_id'];
    }

    protected function getGroupIdForPluginMerchants(): int
    {
        return $this->app['config']->get('applications.freshdesk.group_ids.plugin_merchant');
    }

        public function patchTicketInternal($id, $content)
    {
        if(empty($this->merchant) === true)
        {
            unset($content["account_id"]);

            $fdInstance = Constants::RZPIND;

            $ticketId = $id;
        }
        else
        {
            $ticket = $this->repo->merchant_freshdesk_tickets->findByIdAndMerchant(
                $id,
                $this->merchant);

            $fdInstance = $ticket->getFdInstance();

            $ticketId = $ticket->getTicketId();
        }

        $this->trace->info(TraceCode::FRESHDESK_OLD_INSTANCE, [
            'content to fd'    =>  $content,
        ]);

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, $fdInstance);;

        $response = $this->app['freshdesk_client']->updateTicketV2($ticketId, $content, $url);

        $this->validateTicketResponse($response, ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED);

        return $response;
    }

    public function resolveAndAddAutomatedResolvedTagToTicket($fdInstance , $ticketId) : array
    {
        if (empty($ticketId) === true || empty($fdInstance) === true)
        {
            return [];
        }

        $tags = $this->appendTagsToTicket($ticketId, $fdInstance, Constants::AUTOMATED_WORKFLOW_RESOLVE_TAGS);

        $content = [
            Constants::STATUS       => TicketStatus::getStatusMappingForStatusString(TicketStatus::RESOLVED),
            Constants::TICKET_TAGS  => $tags
        ];

        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, $fdInstance);;

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content, $url);
    }

    public function postTicketReplyOnAgentBehalf($freshdeskTicketId, $replyBody, $fdInstance, $merchantId)
    {
        $type = $this->getTypeFromFdInstance($fdInstance);

        $agentIdToReply = $this->getAgentId($fdInstance);

        if (empty($this->merchant) === true && empty($merchantId) === false)
        {
            $this->merchant = $this->repo->merchant->findByPublicId($merchantId);
        }

        $input = [
            Constants::USER_ID     =>   (int)$agentIdToReply,
            Constants::BODY        =>   $replyBody
        ];

        $url = $this->getFreshdeskUrlType($type, $fdInstance);

        return $this->app[Constants::FRESHDESK_CLIENT]->postTicketReply($freshdeskTicketId, $input, $url);
    }

    protected function getAgentId ($fdInstance)
    {
        $agentIds = $this->app['config']->get('applications.freshdesk.instance_agent_id');

        return $agentIds[$fdInstance];
    }

    protected function getTypeFromFdInstance ($fdInstance)
    {
        foreach (Constants::FRESHDESK_INSTANCES as $typeName => $fdInstanceArray)
        {
            if (array_key_exists($fdInstance, $fdInstanceArray) === true)
            {
                return $typeName;
            }
        }

        throw new BadRequestValidationFailureException(ErrorCode::FRESHDESK_TICKET_INVALID_ID,
            'FD Instance is Invalid ',
            [Constants::FD_INSTANCE => $fdInstance]
        );
    }

    protected function appendUserEmailToCCEmails($input) : array
    {
        $user = $this->app['basicauth']->getUser();

        $emailId = "";

        if (empty($user) === false && $user->isSignupViaEmail() === true)
        {
            $emailId  = $user->getEmail();
        }

        if (empty($emailId) === false && $user->isSignupViaEmail() === true)
        {
            if(empty($input['email']) || $input['email'] !== $emailId)
            {
                if (empty($input[Constants::CC_EMAILS]) === false)
                {
                    $input[Constants::CC_EMAILS][] = $emailId;
                }
                else
                {
                    $input[Constants::CC_EMAILS] = [$emailId];
                }
            }
        }

        return $input;
    }


    public function postTicketOnMerchantBehalf($input, $merchantId, $keepHtmlTags = false)
    {
        if (empty($this->merchant) === true)
        {
            [$merchant, $merchantDetails] = (new Merchant\Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

            $this->merchant = $merchant;
        }

        return $this->postTicketV2(Type::SUPPORT_DASHBOARD, $input, $keepHtmlTags);
    }

    protected function appendTagsToTicket($ticketId, $fdInstance, array $tagsToAdd)
    {
        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD,$fdInstance);

        $ticket = $this->app[Constants::FRESHDESK_CLIENT]->fetchTicketById($ticketId, $url);

        if (empty($ticket['id']) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);
        }

        $tags = $ticket[Constants::TICKET_TAGS] ?? [];

        return array_merge($tags,$tagsToAdd);

    }

    protected function getRedactedTicket(Entity $ticketEntity) : array
    {
        $salesforceAgentEmailId = $this->app['request']->header(self::X_SALESFORCE_EMAIL_ID);

        $ticketToLog = [
            Entity::ID          =>  $ticketEntity->getId(),
            Entity::TICKET_ID   =>  $ticketEntity->getTicketId(),
        ];

        if (empty($salesforceAgentEmailId) == false)
        {
            $ticketToLog['salesforce_agent_email_id'] = $salesforceAgentEmailId;
        }

        return $ticketToLog;
    }



    /**
     * @param $fdInstance -> for migrating ticket for razorpay.freshdesk.com to razorpay-ind.freshdesk.com
     * on subcategory basis, controlled via razorx experiment
     * @param $input
     * @return string
     */
    protected function getMigratedFdInstanceIfApplicable($fdInstance, $input)
    {
        if ($fdInstance !== Constants::RZP)
        {
            return $fdInstance;
        }

        if ((isset($input[Constants::CUSTOM_FIELDS]) === false) or
            (isset($input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY]) === false))
        {
            return $fdInstance;
        }

        return Constants::RZPIND;
    }

    protected function notifyMerchantIfApplicable(Entity $ticketEntity, string $event, string $ticketRequesterItem)
    {
        (new Notifications\Support\Handler([
            'ticket' => $ticketEntity,
            'ticketNewRequesterItem' => $ticketRequesterItem
        ]))->sendForEvent($event);
    }

    protected function extractRequesterItem($input)
    {
        if (array_key_exists(Constants::CF_NEW_REQUESTOR_ITEM, $input[Constants::CUSTOM_FIELDS]))
        {
            return $input[Constants::CUSTOM_FIELDS][Constants::CF_NEW_REQUESTOR_ITEM];
        }

        return "";
    }

    protected function addMerchantDetailsToInput($input) : array
    {
        if ($this->merchant->isSignupViaEmail() === true)
        {
            $input['email'] = $this->merchant->getEmail();
        }

        $input['name'] = $this->merchant->getName() ?? '';

        $input['phone'] = $this->merchant->merchantDetail->getContactMobile();

        return $input;
    }

    protected function additionalFilterOnKey(array $allTickets, $valueToVerify, $key)
    {
        if (empty($valueToVerify) === true)
        {
            return $allTickets;
        }

        $allTickets = array_filter($allTickets, function ($ticket) use ($valueToVerify, $key)
        {
            return $valueToVerify === array_get($ticket,$key,"");
        });

        return $allTickets;
    }

    protected function getNewFdInstanceIfRzpSolAndRzpMerged($fdInstance, $merchantId)
    {
        if ($fdInstance !== Constants::RZPSOL)
        {
            return $fdInstance;
        }

        return Constants::RZPIND;
    }

    protected function modifyRequestForCapital(array $input)
    {
        if (empty($input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY]) === false and
            $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY] === 'Capital' and
            empty($input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM]) === false
        ){

            $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_SUBCATEGORY] = $input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM];

            unset($input[Constants::CUSTOM_FIELDS][Constants::CF_REQUESTOR_ITEM]);
        }

        return $input;
    }
    protected function getFreshdeskUrlType(string $type, string $fdInstance)
    {
        if ($fdInstance === Constants::RZP)
        {
            $this->trace->info(TraceCode::FRESHDESK_OLD_INSTANCE, [
                'route_name'    =>  $this->app['request.ctx']->getRoute(),
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
        else
        {
            return Constants::FRESHDESK_INSTANCES[$type][$fdInstance];
        }

    }

    protected function updateStatusForTicket($type, $freshdeskTicketId, $merchant_id, $fd_instance, $status)
    {
        $tickets = $this->repo->merchant_freshdesk_tickets->fetch([
                                                                      Entity::TYPE      => $type,
                                                                      Entity::TICKET_ID => $freshdeskTicketId,
                                                                  ], $merchant_id);

        $ticketsProcessed = 0;

        if ($tickets->count() !== 0)
        {
            foreach ($tickets as $ticket)
            {
                if ($ticket->getFdInstance() === $fd_instance)
                {
                    $ticketsProcessed++;

                    $ticket->edit(array('status' => $status));

                    $this->repo->merchant_freshdesk_tickets->saveOrFail($ticket);
                }
            }
        }

        if ($ticketsProcessed == 0)
        {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);
        }
    }

    protected function setCfMerchantIdDashboardForTicket(Entity $ticket)
    {
        $url = $this->getFreshdeskUrlType(Type::SUPPORT_DASHBOARD, $ticket->getFdInstance());;

        $data = [
            Constants::CUSTOM_FIELDS => [
                Constants::CF_MERCHANT_ID_DASHBOARD => $this->getQueryParamMerchantIdForSearchAPI($ticket->merchant),
            ],
        ];

        $this->app[Constants::FRESHDESK_CLIENT]->updateTicketV2(
            $ticket->getTicketId(),
            $data,
            $url
        );
    }

    protected function modifyInputForPluginMerchants(array $input, $merchantId, $type)
    {
        $variant = $this->app['razorx']->getTreatment($merchantId,
                                                      Constants::RAZORX_FLAG_TO_ADD_PLUGIN_MERCHANT_TAG,
                                                      $app['rzp.mode'] ?? Mode::LIVE);

        $this->trace->info(TraceCode::FRESHDESK_PLUGIN_MERCHANT_TAG_FLAG, [
            'variant'                           => $variant,
            'merchant_id'                       => $merchantId,
        ]);

        if ($variant !== 'on')
        {
            return $input;
        }

        $fdInstance = $this->getFdInstanceFromTypeAndInput($type, $input);

        if ($fdInstance !== Instance::RZPIND)
        {
            return $input;
        }

        $merchantService = new MerchantService();

        $isPluginMerchant = $merchantService->isPluginMerchant($merchantId);

        if ($isPluginMerchant)
        {
            if (empty($input[Constants::TICKET_TAGS]) === true)
            {
                $input[Constants::TICKET_TAGS] = [Constants::MERCHANT_PLUGIN_TAG];
            }
            else
            {
                $input[Constants::TICKET_TAGS] = array_push($input[Constants::TICKET_TAGS], [Constants::MERCHANT_PLUGIN_TAG]);
            }

            $input[Constants::GROUP_ID] = $this->getGroupIdForPluginMerchants();
        }

        return $input;
    }

    protected function isNodalStructureEnabled($isPaPgEnabled)
    {
        return $isPaPgEnabled === "true" OR
               $isPaPgEnabled === true;
    }

    protected function checkEmailVerifiedOrNot($email)
    {
        $isEmailVerified = $this->app['request']->session()->get(Constants::EMAIL_VERIFIED);

        $verifiedEmail = $this->app['request']->session()->get(Constants::EMAIL);

        if (true === empty($isEmailVerified) OR
            false === $isEmailVerified OR
            $verifiedEmail !== $email
        )
        {
            $this->trace->error(TraceCode:: FRESHDESK_CUSTOMER_FLOW_EMAIL_NOT_VERIFIED, [
                                                                                          'reason' => 'not_verified'
                                                                                      ]
            );

            throw new BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED);
        }

        return;
    }

    protected function checkTicketBelongsToEmail($ticketId)
    {
        $ticketIdsForTheVerifiedEmail = $this->app['request']->session()->get(Constants::TICKET_ID_ARRAY);

        if (empty($ticketIdsForTheVerifiedEmail) === true ||
            array_key_exists((int)$ticketId, $ticketIdsForTheVerifiedEmail) === false)
        {
            $this->trace->error(TraceCode:: FRESHDESK_CUSTOMER_FLOW_TICKET_DOES_NOT_BELONG_TO_EMAIL, [
                                                                                                       'ticket_array'          => $ticketIdsForTheVerifiedEmail,
                                                                                                       'ticket_being_accessed' => $ticketId
                                                                                                   ]
            );
            throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED);
        }
    }

    protected function getRequesterIdFromSession($ticketId)
    {
        $ticketIdsForTheVerifiedEmail = $this->app['request']->session()->get(Constants::TICKET_ID_ARRAY);

        return $ticketIdsForTheVerifiedEmail[(int)$ticketId][Constants::REQUESTER_ID];
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function checkActionAllowed($ticketId, $action = null)
    {
        $ticketIdsForTheVerifiedEmail = $this->app['request']->session()->get(Constants::TICKET_ID_ARRAY);

        if ($ticketIdsForTheVerifiedEmail[$ticketId][Constants::ACTION] !== $action)
        {
            $this->trace->error(TraceCode:: ACTION_NOT_ALLOWED, [
                                                                  'ticket_array'     => $ticketIdsForTheVerifiedEmail,
                                                                  'action_performed' => $action
                                                              ]
            );

            throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED);
        }
    }

    protected function markOtpVerified($email)
    {
        $this->app['request']->session()->put(Constants::EMAIL_VERIFIED, true);

        $this->app['request']->session()->put(Constants::EMAIL, $email);
    }

    protected function addTicketIdsToSession($response)
    {
        $ticketIds = [];

        foreach ($response as $ticket)
        {
            $ticketArr = [
                Constants::ACTION       => $ticket[Constants::ACTION],
                Constants::REQUESTER_ID => $ticket[Constants::REQUESTER_ID],
                Constants::PAYMENT_ID   => $ticket['payment_id']
            ];

            $ticketIds[$ticket['number']] = $ticketArr;
        }

        $this->app['request']->session()->put(Constants::TICKET_ID_ARRAY, $ticketIds);
    }

    protected function filterCustomerResponse($response)
    {
        $returnResponse = [];

        foreach ($response as $key => $value)
        {
            if (array_search($key, Constants::ALLOWED_CUSTOMER_KEYS) !== false)
            {
                $returnResponse[$key] = $value;
            }
        }

        return $returnResponse;
    }

    protected function performNodalTask($input)
    {
        return $input;
    }

    protected function performAssistantNodalTask($input)
    {
        if ($input[Constants::ACTION] === Constants::ASSISTANT_NODAL)
        {
            $input[Constants::DESCRIPTION] .= ' Call on ' . $input[Constants::CONTACT];

            $this->verifyPhoneNumber($input[Constants::CONTACT], $input[Constants::OTP], $input[Constants::ACTION]);
        }

        return $input;
    }

    protected function verifyPhoneNumber($contact, $otp, $action = null): void
    {
        $phoneNumber = $contact;

        $phoneNumber = new PhoneBook($phoneNumber);

        $phoneNumber = $phoneNumber->format(PhoneBook::E164);

        (new Core)->verifyOtp($phoneNumber, $otp, $action);
    }

    protected function getGrievanceGroupIdForCustomerTicket(bool $isNodalStructureFeatureEnabled, $action)
    {
        if ($isNodalStructureFeatureEnabled === false)
        {
            return $this->app['config']->get('applications.freshdesk.activation')[Constants::RZPIND]['groupIdGrievance'];
        }
        else
        {
            return $this->app['config']->get('applications.freshdesk.' . $action)[Constants::RZPIND]['groupIdGrievance'];
        }
    }

    protected function getActionApplicableForNodalFlow($ticket): string
    {
        $created_at_epoch = strtotime($ticket['created_at']);

        $dateDiff = Carbon::now(Timezone::IST)->getTimestamp() - $created_at_epoch;

        $dateDiffInDays = round($dateDiff / (60 * 60 * 24));

        $action = '';

        if ($dateDiffInDays > Constants::MINIMUM_DAYS_FOR_NODAL_ASSISTANT_GRIEVANCE &&
            (empty($ticket['tags']) === true ||
                ((array_search(Constants::ASSISTANT_NODAL, $ticket['tags']) === false) &&
                    array_search(Constants::NODAL, $ticket['tags']) === false)))
        {
            $action = Constants::ASSISTANT_NODAL;
        }
        if ($dateDiffInDays > Constants::MINIMUM_DAYS_FOR_NODAL_GRIEVANCE &&
            (empty($ticket['tags']) === true ||
             array_search(Constants::NODAL, $ticket['tags']) === false))
        {
            $action = Constants::NODAL;
        }

        return $action;
    }

    protected function checkDuplicateTicket($input)
    {
        $ticketIdsForTheVerifiedEmail = $this->app['request']->session()->get(Constants::TICKET_ID_ARRAY);

        foreach ($ticketIdsForTheVerifiedEmail as $id => $ticket)
        {
            if (empty($ticket[Constants::PAYMENT_ID]) === false && $ticket[Constants::PAYMENT_ID] === $input[Constants::CUSTOM_FIELDS][Constants::PAYMENT_ID])
            {
                $this->trace->error(TraceCode:: FRESHDESK_CUSTOMER_FLOW_DUPLICATE_TICKET, [
                                                                                            'reason'           => 'duplicate Ticket',
                                                                                            'old ticket'       => $id,
                                                                                            'input payment id' => $input[Constants::CUSTOM_FIELDS][Constants::PAYMENT_ID]
                                                                                        ]
                );

                throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_ALREADY_EXISTS);
            }
        }
        return;
    }

    protected function getTagsForCustomerTicket($currentTicket, $action)
    {
        return empty($action) ? $currentTicket[Constants::TICKET_TAGS] : array_merge($currentTicket[Constants::TICKET_TAGS],[$action]);
    }

    protected function isCapitalExperimentEnabled($merchantId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get(Constants::CAPITAL_MIGRATION_EXPERIMENT_ID),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        return $variant === Constants::ENABLE;
    }

    public function getAgents(array $input, $type)
    {
        (new Validator)->validateInput('get_' . studly_case($type) . '_agents', $input);

        $queryParams = 'email=' . urlencode($input[Constants::EMAIL]);

        $url = Constants::FRESHDESK_INSTANCES[$type][$input[Constants::FD_INSTANCE]];

        $agents = $this->app[Constants::FRESHDESK_CLIENT]->getAgents($queryParams, $url);

        $this->trace->info(TraceCode::FRESHDESK_GET_AGENTS_RESPONSE, [
            "size_of_agents" => sizeof($agents),
        ]);

        return $this->rewriteAgentsResponse($agents);
    }

    protected function rewriteAgentsResponse(array $response)
    {
        $filteredAgents = [];

        if (empty($response) === false)
        {
            foreach ($response as $agent)
            {
                $filteredAgent = [
                    Constants::AGENT_ID => $agent[Entity::ID],
                    Constants::EMAIL    => $agent[Constants::CONTACT][Constants::EMAIL],
                ];

                array_push($filteredAgents, $filteredAgent);
            }
        }

        return [
            'count' => sizeof($filteredAgents),
            'items' => $filteredAgents
        ];
    }
}
