import Popover, { PopoverBody } from 'common/ui/Popover';

import { TICKET_STATUS_LABELS } from './data';
import {
  getResponseArrivalType,
  getTicketStatus,
  getFormattedDate,
  getEscalationType,
} from '../utils';

export default function TicketBriefMessage(props) {
  const ticketStatus = getTicketStatus(props.ticket);
  const ticketResponseArrivalType = getResponseArrivalType(props.ticket);
  const expectedResponseDate = getFormattedDate(new Date(props.ticket.fr_due_by));
  const isEscalated = getEscalationType(props.ticket) == 'escalated';
  return (
    <div className="Ticket-Brief-Message">
      {props.ticketType === 'agent' ? (
        <div className="Ticket-Brief-Message-Status-Desc text-danger">
          <i className="i i-clock ticket-message-icn" /> Reply Before <b>{expectedResponseDate}</b>
        </div>
      ) : [
          TICKET_STATUS_LABELS.BEING_PROCESSED,
          TICKET_STATUS_LABELS.ACTIVE,
          TICKET_STATUS_LABELS.IN_PROGRESS,
        ].includes(ticketStatus) ? (
        isEscalated ? (
          <div className="Ticket-Brief-Message-Status-Desc">
            <span>
              <i className="i i-forward ticket-escalated-icon" />
              <Popover align="right" theme="dark">
                <PopoverBody>
                  <div>Follow-up requested on this query.</div>
                </PopoverBody>
              </Popover>
            </span>{' '}
            Response expected within <b>4-8 business hours</b>
          </div>
        ) : (
          <div className="Ticket-Brief-Message-Status-Desc">
            <i className="i i-clock ticket-message-icn" /> Response expected before{' '}
            <b>4-8 business hours</b>
          </div>
        )
      ) : ticketResponseArrivalType === 'waiting-for-customer' ? (
        <div>Support agent is waiting for your reply.</div>
      ) : null}
    </div>
  );
}
