<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;

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

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content, FreshdeskConstants::URLIND);
    }

    private function changeTicketGroupToCspWithRelevantTags(int $status, array $tags) : array
    {
        $agentId = (int) $this->freshdeskCustomerDisputeConfig['automation_agent_id'];

        $groupId = (int) $this->freshdeskCustomerDisputeConfig['customer_support_group_id'];

        $response = $this->app['freshdesk_client']->fetchTicketById($this->freshdeskTicket->getTicketId());

        $existingTicketTags = $response['tags'] ?? [];

        $ticketTags = array_merge($existingTicketTags, $tags);

        $content = [
            'group_id'     => $groupId,
            'status'       => $status,
            'responder_id' => $agentId,
            'tags'         => $ticketTags,
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content, FreshdeskConstants::URLIND);
    }

	private function changeTicketGroupToCustomerSupport() : array
    {
        $groupId = (int) $this->freshdeskCustomerDisputeConfig['customer_support_group_id'];

        $content = [
            'group_id'     => $groupId,
            'responder_id' => null, // unsetting automation agent
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content, FreshdeskConstants::URLIND);
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

        return $this->app['freshdesk_client']->updateTicketV2($ticketId, $content, FreshdeskConstants::URLIND);
    }

	private function replyToTicket(string $renderedBody) : array
    {
        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->postTicketReply(
            $ticketId, ['body' => $renderedBody], FreshdeskConstants::URLIND);
    }
}
