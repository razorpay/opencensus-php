import Popover, { PopoverBody } from 'common/ui/Popover';

import { TICKET_STATUS_LABELS } from './data';
import {
  getResponseArrivalType,
  getTicketStatus,
  getFormattedDate,
  getEscalationType,
} from '../utils';
import { getResponseExpectedBy } from 'merchant/views/TicketSupport/getResponseExpectedBy';

import { Box, Text } from '@razorpay/blade/components';

const ResponseExpectedBy = ({ ticket, shouldShowResponseBy }) => {
  if (shouldShowResponseBy) {
    const { responseBy, prefix } = getResponseExpectedBy(ticket);

    return (
      <Text size="small" color="surface.text.gray.muted" weight="medium">
        {prefix}
        {prefix ? ' ' : ''}
        <b>{responseBy}</b>
      </Text>
    );
  }

  return (
    <>
      Response expected within&nbsp;<b>4-8 business hours</b>
    </>
  );
};

export default function TicketBriefMessage(props) {
  const shouldShowResponseBy = props.shouldShowResponseBy;
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
          <Box display="flex" flexDirection="row" alignItems="center">
            <span>
              <i className="i i-forward ticket-escalated-icon" />
              <Popover align="right" theme="dark">
                <PopoverBody>
                  <div>Follow-up requested on this query.</div>
                </PopoverBody>
              </Popover>
            </span>{' '}
            <ResponseExpectedBy ticket={props.ticket} shouldShowResponseBy={shouldShowResponseBy} />
          </Box>
        ) : (
          <Box display="flex" flexDirection="row" alignItems="center">
            <Box>
              <i className="i i-clock ticket-message-icn" />
            </Box>
            <Box>
              <ResponseExpectedBy
                ticket={props.ticket}
                shouldShowResponseBy={shouldShowResponseBy}
              />
            </Box>
          </Box>
        )
      ) : ticketResponseArrivalType === 'waiting-for-customer' ? (
        <div>Support agent is waiting for your reply.</div>
      ) : null}
    </div>
  );
}
