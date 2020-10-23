<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskTicketService;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;


class Service extends Base\Service
{
    const FRESKDESK_INSTANCES = [
        Constants::RZP    => Constants::URL,
        Constants::RZPSOL => Constants::URL2
    ];

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
}
