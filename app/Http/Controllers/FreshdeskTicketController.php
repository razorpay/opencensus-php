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

    public function internalFetchMerchantFreshdeskTickets()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->internalFetchMerchantFreshdeskTickets($input);

        return ApiResponse::json($response);
    }

    public function postTicketForAccountRecovery()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postTicketForAccountRecovery($input);

        return ApiResponse::json($response);
    }

    /**
     * Create Freshdesk Ticket
     *
     * @return mixed
     */
    public function postTicket()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postTicket($input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    /**
     * Post Freshdesk Otp Send
     *
     * @return mixed
     */
    public function postOtp()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postOtp($input);

        return ApiResponse::json($response);
    }

    public function fetchCustomerTickets()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->fetchCustomerTickets($input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function postCustomerTicketReply($id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postCustomerTicketReply($id, $input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function fetchCustomerTicketsConversations($id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getCustomerTicketConversations($id, $input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function raiseGrievance()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->raiseGrievance($input);

        $response = ApiResponse::json($response);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function postCustomerDispute()
    {
        $input = Request::all();

        $data = (new CustomerDisputeFreshdeskTicketService())->handleFreshdeskTicket($input);

        return ApiResponse::json($data);
    }

    public function postTicketV2($type)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postTicketV2($type, $input);

        return ApiResponse::json($response);
    }

    public function insertIntoDB()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->insertIntoDB($input);

        return ApiResponse::json($response);
    }

    public function internalPostTicketV2()
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->internalPostTicketV2($input);

        return ApiResponse::json($response);
    }

    public function getTickets($type)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getTickets($input, $type);

        return ApiResponse::json($response);
    }

    public function getAgents($type)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getAgents($input, $type);

        return ApiResponse::json($response);
    }

    public function getConversations($type, $id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getConversations($id, $input, $type);

        return ApiResponse::json($response);
    }

    public function getTicket($type, $id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->getTicket($id, $input, $type);

        return ApiResponse::json($response);
    }

    public function getAgentDetailForFreshdeskTicket($id)
    {
        $response = (new FreshdeskTicketService)->getAgentDetailForFreshdeskTicket($id);

        return ApiResponse::json($response);
    }

    public function addNoteToTicket($id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->addNoteToTicket($id, $input);

        return ApiResponse::json($response);
    }

    public function postTicketReply($type, $id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->postTicketReply($id, $input, $type);

        return ApiResponse::json($response);
    }

    public function postTicketGrievance($type, $id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService())->postGrievance($id, $input, $type);

        return ApiResponse::json($response);
    }

    public function postWebhook($event)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->processWebhook($event, $input);

        return ApiResponse::json($response);
    }

    public function patchTicketInternal($id)
    {
        $input = Request::all();

        $response = (new FreshdeskTicketService)->patchTicketInternal($id, $input);

        return ApiResponse::json($response);

    }

    private function addCorsHeaders(& $response)
    {
        $response->headers->set('Access-Control-Allow-Credentials' , 'true');

        $response->headers->set('Access-Control-Allow-Headers', '*');
    }
}
