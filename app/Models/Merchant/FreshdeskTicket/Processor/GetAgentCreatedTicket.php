<?php

namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Notifications\Support\Events;
use RZP\Notifications\Support\Handler;
use RZP\Models\Merchant\FreshdeskTicket\Type;
use RZP\Models\Merchant\FreshdeskTicket\Core;
use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Service;
use RZP\Models\Merchant\FreshdeskTicket\TicketStatus;

class GetAgentCreatedTicket extends Base
{

    const FIELDS_WITH_CUSTOM_STRING_IN_QUERY = [Constants::CF_CREATED_BY];

    const FIELDS_WITH_EXACT_KEYS_IN_QUERY = [Constants::CREATED_AT];

    public function processEvent($input)
    {
        $filterInput = [];

        $date = Carbon::now(Timezone::IST)->format("Y-m-d");

        foreach (Constants::SCHEDULER_VS_FD_INSTANCES[Constants::GetAgentCreatedTicket] as $fdInstance)
        {
            $queryInput[Constants::CREATED_AT] = $date;

            $queryInput[Constants::CF_CREATED_BY] = Constants::AGENT;

            $filterInput[Constants::QUERY] = $this->addCustomStringToQuery($queryInput);

            $isMapped = false;

            for ($i = 1; $isMapped === false; $i++)
            {
                $filterInput[Constants::PAGE] = $i;

                $this->trace->info(TraceCode::GET_AGENT_CREATED_TICKET_ALREADY_MAPPED,
                                   [
                                       'tickets' => $filterInput
                                   ]
                );

                $fdService = new Service();

                $fdInstances = [];

                $fdInstances [$fdInstance] = Constants::FRESHDESK_INSTANCES[Type::SUPPORT_DASHBOARD][$fdInstance];

                $tickets = $fdService->getTicketsFromTypeOrFdInstances($filterInput, null, $fdInstances);

                foreach ($tickets as $ticket)
                {
                    if (empty($ticket[Constants::CUSTOM_FIELDS][Constants::CF_MERCHANT_ID]) === false)
                    {

                        $isMapped = $this->isAlreadyMapped($ticket, $fdInstance);

                        $this->trace->info(TraceCode::GET_AGENT_CREATED_TICKET_ALREADY_MAPPED, [
                            'isMapped'  => $isMapped,
                            'ticket_id' => $ticket[Entity::ID],
                        ]);

                        if ($isMapped === false)
                        {
                            $ticket = $this->createTicketEntity($fdInstance, $ticket);

                            $this->setCfMerchantIdDashboardForTicket($ticket);

                            (new Handler(['ticket' => $ticket]))->sendForEvent(Events::AGENT_TICKET_CREATED);
                        }
                        else
                        {
                            break;
                        }
                    }
                }

                if (empty($tickets) === true || sizeof($tickets) < Constants::FRESHDESK_DEFAULT_PAGE_SIZE )
                {
                    break;
                }
            }
        }

        return [Constants::SUCCESS => true];
    }

    private function addCustomStringToQuery($queryInput)
    {
        $queryStringArray = [];

        foreach ($queryInput as $key => $values)
        {
            if (in_array($key, self::FIELDS_WITH_CUSTOM_STRING_IN_QUERY))
            {
                $queryStringArray[] = 'custom_string:\'' . $queryInput[$key] . '\'';
            }
            else
            {
                if (in_array($key, self::FIELDS_WITH_EXACT_KEYS_IN_QUERY))
                {
                    $queryStringArray[] = $key . ':\'' . $queryInput[$key] . '\'';
                }
            }
        }

        return '"' . implode(" AND ", $queryStringArray) . '"';
    }

    protected function isAlreadyMapped($ticket, $fdInstance): bool
    {
        $isMapped = false;

        $duplicateTickets = $this->repo->merchant_freshdesk_tickets->fetch([
                                                                               Entity::TYPE       => Type::SUPPORT_DASHBOARD,
                                                                               Entity::CREATED_BY => Constants::AGENT,
                                                                               Entity::TICKET_ID  => $ticket[Entity::ID],
                                                                           ]);
        if (empty($duplicateTickets) === false)
        {
            foreach ($duplicateTickets as $duplicateTicket)
            {
                if ($duplicateTicket->getFdInstance() === $fdInstance)
                {
                    $isMapped = true;

                    break;
                }
            }
        }

        return $isMapped;
    }

    protected function createTicketEntity($fdInstance, $ticket): Entity
    {
        $ticketDetails = [
            Constants::FD_INSTANCE => $fdInstance,
        ];
        /* hardcoded Open because none of the other status are kept track of
        and might lead to mapping not existing for a status added by Freshdesk
        Also While showing teh tickets of agents we add a check of status=Open.
        Which will break the flow
        */

        $ticketInput =
            [
                Entity::TICKET_ID      => stringify($ticket[Entity::ID]),
                Entity::TYPE           => Type::SUPPORT_DASHBOARD,
                Entity::MERCHANT_ID    => $ticket,
                Entity::TICKET_DETAILS => $ticketDetails,
                Entity::CREATED_BY     => Constants::AGENT,
                Entity::STATUS         => TicketStatus::getDatabaseStatusMappingForStatusString(TicketStatus::OPEN),
            ];

        $this->trace->info(TraceCode::GET_AGENT_CREATED_TICKET_ENTITY, [
            'ticket_entity' => $ticketInput,
        ]);

        return (new Core)->create($ticketInput, $ticket[Constants::CUSTOM_FIELDS][Constants::CF_MERCHANT_ID], true);
    }
}