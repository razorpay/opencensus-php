import {
  getResponseArrivalType,
  getTicketStatus,
  getFormattedDate,
  getEscalationType,
} from '../utils';
import Popover, { PopoverBody } from 'common/ui/Popover';

export default function TicketBriefMessage(props) {
  // let ticketStatus, ticketResponseArrivalType, expectedResponseDate, isEscalated;
  const ticketStatus = getTicketStatus(props.ticket);
  const ticketResponseArrivalType = getResponseArrivalType(props.ticket);
  const expectedResponseDate = getFormattedDate(new Date(props.ticket.fr_due_by));
  const isEscalated = getEscalationType(props.ticket) == 'escalated';
  return (
    <div class="Ticket-Brief-Message">
      {ticketStatus === 'Work In Progress' || ticketStatus === 'Active' ? (
        isEscalated ? (
          <div class="Ticket-Brief-Message-Status-Desc">
            <span>
              <i class="i i-forward ticket-escalated-icon" />
              <Popover align="right" theme="dark">
                <PopoverBody>
                  <div>Follow-up requested on this query.</div>
                </PopoverBody>
              </Popover>
            </span>{' '}
            Response expected before <b>{expectedResponseDate}</b>
          </div>
        ) : (
          <div class="Ticket-Brief-Message-Status-Desc">
            <i class="i i-clock" /> Response expected before <b>{expectedResponseDate}</b>
          </div>
        )
      ) : ticketResponseArrivalType === 'waiting-for-customer' ? (
        <div>Support agent is waiting for your reply.</div>
      ) : null}
    </div>
  );
}
