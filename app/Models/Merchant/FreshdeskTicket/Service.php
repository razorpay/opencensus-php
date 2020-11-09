<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use Illuminate\Support\Str;
use RZP\Base\JitValidator;
use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Payment\Refund;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskTicketService;
use RZP\Models\Merchant\FreshdeskTicket\Validator as FreshdeskTicketValidator;

class Service extends Base\Service
{
    protected $createRules = [
        'email'                               => 'required|email',
        'otp'                                 => 'required|string|min:4|max:6',
        'name'                                => 'required|string|max:100',
        'phone'                               => 'sometimes|contact_syntax',
        'description'                         => 'required|string|max:1000',
        'subject'                             => 'required|string|max:500',
        'attachments'                         => 'sometimes',
        'custom_fields'                       => 'required|array',
        'custom_fields.cf_requester_category' => 'required|string|max:50',
        'custom_fields.cf_transaction_id'     => 'required_if:custom_fields.cf_requester_category,Customer|string|min:8|max:50',
    ];

    protected $otpRules = [
        'email'     =>     'required|email',
    ];

    protected $grievanceRules = [
        'id'                                  => 'required',
        'description'                         => 'required|string|max:1000',
        'attachments'                         => 'sometimes',
        'custom_fields'                       => 'sometimes|array',
    ];

    /*
     * Default ticket properties
     * Priority = 1 (Low)
     * Status = 2 (Open)
     */
    const STATUS_FIELDS = [
        'priority' => 1,
        'status'   => 2,
    ];

    const FRESKDESK_INSTANCES = [
        Constants::RZP    => Constants::URL,
        Constants::RZPSOL => Constants::URL2
    ];

    const TECH_SUBCATEGORIES = ['Technical support'];

    public function getTicketStatus(array $response)
    {
        return TicketStatus::$ticketStatusMapping[$response['status']];
    }

    public function getReserveBalanceTicketStatus() : array
    {
        $ticketId = $this->getReserveBalanceTicketId();

        $response = $this->app['freshdesk_client']->getReserveBalanceTicketStatus($ticketId);

        $response = [
            'ticket_id'      => $response['id'],
            'ticket_status'  => (new FreshdeskTicketService)->getTicketStatus($response)
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

        throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_RESERVE_BALANCE_TICKET_NOT_FOUND,
            'No Reserve Balance ticket exists for merchant.',
            ['merchant_id' => $merchantId]
        );
    }

    public function getTickets(array $input): array
    {
        $page = $input[Constants::PAGE] ?? 1;

        $status = $input[Constants::STATUS] ?? null;

        // Adding Merchant ID in query with required prefix
        $queryString = '"custom_string:' . $this->getQueryParamMerchantIdForSearchAPI();

        // Adding status filter if necessary
        if (empty($status) === false)
        {
            $queryString .= ' AND (';

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

        $queryString .= '"';

        $queryParams = [
            Constants::QUERY => $queryString,
            Constants::PAGE  => $page
        ];

        $allTickets = [];

        foreach (self::FRESKDESK_INSTANCES as $fdInstance => $url)
        {
            $response = $this->app[Constants::FRESHDESK_CLIENT]->getTickets($queryParams, $url);

            $results = $response[Constants::RESULTS] ?? [];

            // Adding FD instance in each ticket
            array_walk(
                $results,
                function(&$value, $key, $fdInstanceKey) {
                    $value[Constants::FD_INSTANCE] = $fdInstanceKey;
                },
                $fdInstance
            );

            $allTickets = array_merge($allTickets, $results);
        }

        // Sorting the tickets in descending order of created_at
        $this->sortTicketsInDescendingOrderOfCreatedAt($allTickets);

        //
        // Order tickets by the following buckets
        // 1. Awaiting your response - MERCHANT_ACTION_STATUSES
        // 2. Active & Work in Progress - ACTIVE_STATUSES
        // 3. All other tickets
        //
        $statusGroupedAndOrderedTickets = $this->groupTicketsInBucketsAndOrderFinalList($allTickets);

        $ticketsResponse = [
            Constants::RESULTS => $statusGroupedAndOrderedTickets,
            Constants::TOTAL   => count($statusGroupedAndOrderedTickets)
        ];

        return $ticketsResponse;
    }

    /**
     * @param array $input
     * @param array $return
     * @throws Exception\BadRequestValidationFailureException
     */
    public function postTicket(array $input): array
    {
        (new JitValidator)->setStrictFalse()->rules($this->createRules)->input($input)->validate();

        (new Core)->verifyOtp($input['email'], $input['otp']);

        $idType = $this->preProcessInput($input);

        $this->populateCustomFields($input, $idType);

        $fdInstance = $this->getFdInstance($input);

        $url = self::FRESKDESK_INSTANCES[$fdInstance];

        unset($input[Constants::FD_INSTANCE]);

        unset($input[Constants::OTP]);

        $ticketCreateResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicket($input, $url);

        $ticketCreateResponse[Constants::FD_INSTANCE] = $fdInstance;

        return $ticketCreateResponse;
    }

    public function getConversations(array $input): array
    {
        $page = $input[Constants::PAGE] ?? 1;

        $perPage = $input[Constants::PER_PAGE] ?? 10;

        $ticketId = $input[Constants::TICKET_ID];

        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZP;

        $url = self::FRESKDESK_INSTANCES[$fdInstance];

        $queryParams = [
            Constants::PAGE     => $page,
            Constants::PER_PAGE => $perPage
        ];

        $response = $this->app[Constants::FRESHDESK_CLIENT]->getTicketConversations($ticketId, $queryParams, $url);

        return $response;
    }

    public function getTicketWithStats($ticketId, array $input): array
    {
        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZP;

        $url = self::FRESKDESK_INSTANCES[$fdInstance];

        $ticketWithStats = $this->app[Constants::FRESHDESK_CLIENT]->getTicketWithStats($ticketId, $url);

        if (empty($ticketWithStats) === false)
        {
            $ticketWithStats[Constants::FD_INSTANCE] = $fdInstance;
        }

        return $ticketWithStats ?? [];
    }

    public function postTicketReply($ticketId, array $input): array
    {
        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZP;

        $url = self::FRESKDESK_INSTANCES[$fdInstance];

        unset($input[Constants::FD_INSTANCE]);

        // Converting user id to int - it comes as a string from FE sometimes
        if (isset($input[Constants::USER_ID]) === true)
        {
            $input[Constants::USER_ID] += 0;
        }

        $ticketReplyResponse = $this->app[Constants::FRESHDESK_CLIENT]->postTicketReply($ticketId, $input, $url);

        $ticketReplyResponse[Constants::FD_INSTANCE] = $fdInstance;

        return $ticketReplyResponse;
    }

    /**
     * @param array $input
     * @param array $return
     * @throws BadRequestException
     */
    public function postOtp(array $input): array
    {
        (new JitValidator)->rules($this->otpRules)->input($input)->validate();

        (new Core)->generateAndSendCustomerOtp($input['email']);

        return ['success' => true];
    }

    public function fetchCustomerTickets($input)
    {
        $freshdeskTicketValidator = new FreshdeskTicketValidator;

        $freshdeskTicketValidator->validateInput('fetch_customer_tickets', $input);

        $otp = $input['otp'];

        $email = $input['email'];

        (new Core)->verifyOtp($email, $otp);

        $count = $input['count'] ?? 5;

        $queryParams = 'email=' . urlencode($email);

        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZP;

        $url = self::FRESKDESK_INSTANCES[$fdInstance];

        $tickets = $this->app[Constants::FRESHDESK_CLIENT]->getCustomerTickets($queryParams, $url);

        if (is_array($tickets) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_TICKET_FETCH_FAILED);
        }

        $response = [];

        $counter = 1;

        foreach ($tickets as $ticket)
        {
            if (isset($ticket['id']) === false)
            {
                continue;
            }

            $ticketResponse = [
                'number'            => $ticket['id'],
                'status'            => $this->getTicketStatus($ticket),
                'subject'           => $ticket['subject'],
                'source'            => $ticket['source'],
                'type'              => $ticket['type'],
                'payment_id'        => $ticket['custom_fields']['cf_razorpay_payment_id'],
                'refund_id'         => $ticket['custom_fields']['cf_refund_id'],
                'order_id'          => $ticket['custom_fields']['cf_order_id'],
                'transaction_id'    => $ticket['custom_fields']['cf_transaction_id'],
                'created_at'        => $ticket['created_at'],
                'updated_at'        => $ticket['updated_at'],
            ];

            $response[] = $ticketResponse;

            if ($counter >= $count)
            {
                break;
            }

            $counter++;
        }

        if (count($response) === 0)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NO_TICKETS_FOUND_FOR_CUSTOMER);
        }

        return $response;
    }

    public function raiseGrievance($input)
    {
        (new JitValidator)->setStrictFalse()->rules($this->grievanceRules)->input($input)->validate();

        $ticketId = $input['id'];

        $customerDescription = $input['description'];

        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZP;

        $url = self::FRESKDESK_INSTANCES[$fdInstance];

        $ticket = $this->app[Constants::FRESHDESK_CLIENT]->fetchTicketById($ticketId, $url);

        if (isset($ticket['id']) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND);
        }

        if ($this->getTicketStatus($ticket) === TicketStatus::CLOSED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_ALREADY_CLOSED);
        }

        $data = $input;

        unset($data['id']);

        $data['status'] = 2;

        $data['priority'] = 4;

        $ticket = $this->app[Constants::FRESHDESK_CLIENT]->updateTicketV2($ticketId, $data, $url);

        $this->validateGrievanceResponse($ticket, $customerDescription);

        return [
            'number'            => $ticket['id'],
            'status'            => $this->getTicketStatus($ticket),
            'subject'           => $ticket['subject'],
            'source'            => $ticket['source'],
            'type'              => $ticket['type'],
            'description'       => $ticket['description'],
            'payment_id'        => $ticket['custom_fields']['cf_razorpay_payment_id'],
            'refund_id'         => $ticket['custom_fields']['cf_refund_id'],
            'order_id'          => $ticket['custom_fields']['cf_order_id'],
            'transaction_id'    => $ticket['custom_fields']['cf_transaction_id'],
            'created_at'        => $ticket['created_at'],
            'updated_at'        => $ticket['updated_at'],
        ];
    }

    protected function validateGrievanceResponse($response, $customerDescription)
    {
        if ((isset($response['status']) === false) or ($response['status'] !== 2))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED);
        }

        if ((isset($response['priority']) === false) and ($response['priority'] !== 4))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED);
        }

        if ((isset($response['description']) === false) or ($response['description'] !== $customerDescription))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED);
        }
    }

    protected function getQueryParamMerchantIdForSearchAPI(): string
    {
        $merchantId = $this->auth->getMerchantId();

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

    protected function preProcessInput(array &$input): string
    {
        $this->updateStatusFields($input);

        $category = $input[Constants::CUSTOM_FIELDS][Constants::CATEGORY];

        switch ($category)
        {
            case Constants::CUSTOMER:
                return $this->preProcessInputCustomer($input);

            default:
                return '';
        }
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

    protected function getFdInstance(array $input): string
    {
        $fdInstance = $input[Constants::FD_INSTANCE] ?? Constants::RZP;

        if ((isset($input[Constants::CUSTOM_FIELDS]) === true) and
            (isset($input[Constants::CUSTOM_FIELDS][Constants::SUB_CATEGORY]) === true))
        {
            $subCategory = $input[Constants::CUSTOM_FIELDS][Constants::SUB_CATEGORY];

            if (in_array($subCategory, self::TECH_SUBCATEGORIES) === true)
            {
                $fdInstance = Constants::RZPSOL;
            }
        }

        return $fdInstance;
    }

    /**
     * @param array $input
     * @param void $return
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function populateCustomFields(array &$input, string $idType)
    {
        $mode = $input['mode'] ?? Mode::LIVE;

        $this->auth->setModeAndDbConnection($mode);

        unset($input['mode']);

        if ($idType === '')
        {
            return;
        }

        switch ($idType)
        {
            case Constants::PAYMENT:

                $paymentId = $input[Constants::CUSTOM_FIELDS][Constants::PAYMENT_ID];

                $customFields = $this->getCustomFieldsFromPaymentId($paymentId);

                break;

            case Constants::REFUND:

                $refundId = $input[Constants::CUSTOM_FIELDS][Constants::REFUND_ID];

                $customFields = $this->getCustomFieldsFromRefundId($refundId);

                break;

            case Constants::ORDER:

                $orderId = $input[Constants::CUSTOM_FIELDS][Constants::ORDER_ID];

                $customFields = $this->getCustomFieldsFromOrderId($orderId);

                break;

            default:
                // Given id could be RZP internal id, UPI RRN, Merchant reference number (from notes)
                $id = $input[Constants::CUSTOM_FIELDS][Constants::TRANSACTION_ID];

                $customFields = $this->getCustomFieldsFromId($id);

                break;
        }

        if (empty($customFields) === true)
        {
            throw new BadRequestValidationFailureException(ErrorCode::FRESHDESK_TICKET_INVALID_ID,
                Constants::TRANSACTION_ID,
                [Constants::TRANSACTION_ID => $input[Constants::CUSTOM_FIELDS][Constants::TRANSACTION_ID]]
            );
        }

        $input[Constants::CUSTOM_FIELDS] = array_merge($input[Constants::CUSTOM_FIELDS], $customFields);
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

        $payment = $this->repo->payment->find($paymentId);

        if (empty($payment) === false)
        {
            $customFields[Constants::PAYMENT_ID] = $payment->getPublicId();
            $customFields[Constants::MERCHANT_ID] = $payment->getMerchantId();

            if($payment->getOrderId() !== false)
            {
                $customFields[Constants::ORDER_ID] = $payment->getOrderId();
            }
            if ($payment->getEmail() !== null)
            {
                $customFields[Constants::PAYMENT_CUSTOMER_EMAIL] = $payment->getEmail();
            }
            if ($payment->getContact() !== null)
            {
                $customFields[Constants::PAYMENT_CUSTOMER_PHONE] = $payment->getContact();
            }
        }

        return $customFields;
    }

    /**
     * @param string $refundId
     * @param array $return
     */
    protected function getCustomFieldsFromRefundId($refundId): array
    {
        $customFields = [];

        Refund\Entity::stripSignWithoutValidation($refundId);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_ID, ['refund_id' => $refundId]);

        $refund = $this->repo->refund->find($refundId);

        if (empty($refund) === false)
        {
            $customFields = $this->getCustomFieldsFromPaymentId($refund->getPaymentId());

            $customFields[Constants::REFUND_ID] = $refund->getPublicId();
        }

        return $customFields;
    }

    /**
     * @param string $orderId
     * @param array $return
     */
    protected function getCustomFieldsFromOrderId($orderId): array
    {
        $customFields = [];

        Order\Entity::stripSignWithoutValidation($orderId);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_ID, ['order_id' => $orderId]);

        $order = $this->repo->order->find($orderId);

        if (empty($order) === false)
        {
            $payments = $order->payments;

            foreach ($payments as $payment)
            {
                $paymentCustomFields = $this->getCustomFieldsFromPaymentId($payment->getPublicId());

                $customFields = array_merge_recursive($customFields, $paymentCustomFields);
            }

            $customFields[Constants::ORDER_ID] = $order->getPublicId();
        }

        return $customFields;
    }

    /**
     * @param string $id
     * @param array $return
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function getCustomFieldsFromId($id): array
    {
        // Since this is a direct auth route - and we do not have the merchant ID
        // we need to allow multiple fetch without merchant ID
        $merchantIdRequiredForMultipleFetch = false;

        $this->repo->payment->setMerchantIdRequiredForMultipleFetch($merchantIdRequiredForMultipleFetch);
        $this->repo->refund->setMerchantIdRequiredForMultipleFetch($merchantIdRequiredForMultipleFetch);

        $customFields = [];

        // check if RRN
        if (Refund\Service::verifyUpiRrn($id) === true)
        {
            $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_ID, ['upi_rrn' => $id]);

            $actions = [Payment\Action::AUTHORIZE, Payment\Action::REFUND];

            $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndActions($id, $actions);

            if (empty($upiEntity) === false)
            {
                $paymentId = $upiEntity->getPaymentId();

                return $this->getCustomFieldsFromPaymentId($paymentId);
            }
        }

        (new Validator)->validateCustomerFreshDeskTicketIdFromMerchantNotes($id);

        $this->trace->info(TraceCode::FRESHDESK_SUPPORT_TICKETS_ID, ['id' => $id]);

        $payment = $this->repo->payment->fetch([Payment\Entity::NOTES => $id]);

        if (empty($payment->toArray()) === false)
        {
            return $this->getCustomFieldsFromPaymentId($payment->toArray()[0][Payment\Entity::ID]);
        }
        else
        {
            $refund = $this->repo->refund->fetch([Refund\Entity::NOTES => $id]);

            if (empty($refund->toArray()) === false)
            {
                return $this->getCustomFieldsFromRefundId($refund->toArray()[0][Refund\Entity::ID]);
            }
        }

        return $customFields;
    }
}
