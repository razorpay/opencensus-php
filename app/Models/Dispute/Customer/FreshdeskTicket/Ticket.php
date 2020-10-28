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

        return $this->app['freshdesk_client']->updateTicket($ticketId, $content);
    }

	private function changeTicketGroupToDispute() : array
    {
        $groupId = (int) $this->freshdeskCustomerDisputeConfig['dispute_group_id'];

        $content = [
            'group_id'     => $groupId,
            'responder_id' => null, // unsetting automation agent
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicket($ticketId, $content);
    }

	private function changeTicketGroupToCustomerSupport() : array
    {
        $groupId = (int) $this->freshdeskCustomerDisputeConfig['customer_support_group_id'];

        $content = [
            'group_id'     => $groupId,
            'responder_id' => null, // unsetting automation agent
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicket($ticketId, $content);
    }

	private function closeTicket() : array
    {
        // ensuring that automation agent closes the ticket
        $agentId = (int) $this->freshdeskCustomerDisputeConfig['automation_agent_id'];

        $groupId = (int) $this->freshdeskCustomerDisputeConfig['automation_group_id'];

        $content = [
            'status'       => 5,
            'group_id'     => $groupId,
            'responder_id' => $agentId,
        ];

        $ticketId = strval($this->freshdeskTicket->getTicketId());

        return $this->app['freshdesk_client']->updateTicket($ticketId, $content);
    }
}
