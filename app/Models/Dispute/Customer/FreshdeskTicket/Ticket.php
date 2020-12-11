<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

trait Ticket
{
	private function assignAutomationAgentToTicket() : array
    {
        $agentId = (int) $this->freshdeskCustomerDisputeConfig['automation_agent_id'];

        $groupId = (int) $this->freshdeskCustomerDisputeConfig['automation_group_id'];

        $content = [
            'group_id'     => $groupId,
            'responder_id' => $agentId,
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content);
    }

	private function changeTicketGroupToCspWithRelevantTags() : array
    {
        $agentId = (int) $this->freshdeskCustomerDisputeConfig['automation_agent_id'];

        $groupId = (int) $this->freshdeskCustomerDisputeConfig['customer_support_group_id'];

        $response = $this->app['freshdesk_client']->fetchTicketById($this->freshdeskTicket->getTicketId());

        $ticketTags = $response['tags'] ?? [];

        array_push($ticketTags,
            Constants::FD_TAGS_AUTOMATED_DISPUTE_FLOW,
            Constants::FD_TAGS_DISPUTE_CREATED,
            Constants::FD_TAGS_PENDING_WITH_DISPUTES);

        $content = [
            'group_id'     => $groupId,
            'status'       => Constants::FD_TICKET_STATUS_PENDING_WITH_THIRD_PARTY,
            'responder_id' => $agentId,
            'tags'         => $ticketTags,
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content);
    }

	private function changeTicketGroupToCustomerSupport() : array
    {
        $groupId = (int) $this->freshdeskCustomerDisputeConfig['customer_support_group_id'];

        $content = [
            'group_id'     => $groupId,
            'responder_id' => null, // unsetting automation agent
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content);
    }

	private function closeTicket() : array
    {
        // ensuring that automation agent closes the ticket
        $agentId = (int) $this->freshdeskCustomerDisputeConfig['automation_agent_id'];

        $groupId = (int) $this->freshdeskCustomerDisputeConfig['automation_group_id'];

        $content = [
            'status'       => Constants::FD_TICKET_STATUS_CLOSED,
            'group_id'     => $groupId,
            'responder_id' => $agentId,
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content);
    }

	private function replyToTicket(string $renderedBody) : array
    {
        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->postTicketReply($ticketId, ['body' => $renderedBody]);
    }
}
