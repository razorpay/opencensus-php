<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\FreshdeskTicket\Service as FreshdeskTicketService;
use RZP\Models\Dispute\Customer\FreshdeskTicket\Service as CustomerDisputeFreshdeskTicketService;

/**
 * Freshdesk TicketController to get the support tickets details for Frontend
 *
 * @package RZP\Http\Controllers
 */
class FreshdeskTicketController extends Controller
{
    /**
     * Get FreshdeskTicket status for the given $ticketId
     *
     * @return mixed
     */
    public function getReserveBalanceTicketStatus()
    {
        $response = (new FreshdeskTicketService)->getReserveBalanceTicketStatus();

        return ApiResponse::json($response);
    }

    /**
     * Store ticket details in Merchant Freshdesk tickets table
     *
     * @return mixed
     */
    public function postReserveBalanceTicketDetails()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postReserveBalanceTicketDetails($input);

        return ApiResponse::json($response);
    }

    /**
     * Get Freshdesk Tickets for the given merchant
     *
     * @return mixed
     */
    public function getTickets()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getTickets($input);

        return ApiResponse::json($response);
    }

    /**
     * Get Freshdesk Ticket conversations for the given ticket id
     *
     * @return mixed
     */
    public function getConversations()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getConversations($input);

        return ApiResponse::json($response);
    }

    /**
     * Get Freshdesk Ticket with stats for the given ticket id
     *
     * @return mixed
     */
    public function getTicketWithStats($ticketId)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getTicketWithStats($ticketId, $input);

        return ApiResponse::json($response);
    }

    /**
     * Post Freshdesk Ticket conversation reply
     *
     * @return mixed
     */
    public function postTicketReply($ticketId)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postTicketReply($ticketId, $input);

        return ApiResponse::json($response);
    }

    public function postCustomerDispute()
    {
        $input = Request::all();

        $data = (new CustomerDisputeFreshdeskTicketService())->handleFreshdeskTicket($input);

        return ApiResponse::json($data);
    }
}
