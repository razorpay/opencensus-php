import React from 'react';
import { getEscalationType, getResponseArrivalType } from '../utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { statuses } from './data';

const Banner = ({ ticket }) => {
  const ESCALATION_TYPE = getEscalationType(ticket);
  const RESPONSE_ARRIVAL_TYPE = getResponseArrivalType(ticket);
  const expectedResponseBy = moment(ticket.fr_due_by).format('HH:mm, DD MMM');

  const openGrievanceFlow = () => {
    analyticsTrack({
      objectName: 'raise grievance',
      actionName: 'clicked',
      screen: 'support tickets',
      properties: {
        ticketId: ticket.ticket_id,
        status: statuses[ticket.status] ? statuses[ticket.status].name : ticket.status,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    if (rzpTicketSystem) {
      rzpTicketSystem.openModal('#grievance-new', {
        ticketID: ticket.id,
      });
    }
  };

  if (
    ESCALATION_TYPE !== 'escalated' &&
    ESCALATION_TYPE !== 'able-to-escalate' &&
    RESPONSE_ARRIVAL_TYPE !== 'within-expected-time'
  ) {
    return null;
  }
  if (ESCALATION_TYPE === 'escalated' || RESPONSE_ARRIVAL_TYPE === 'within-expected-time') {
    return (
      <div className="row escalate-banner escalation-warning">
        <div className="col-xs-2"></div>
        <div className="col-xs-10" style={{ paddingLeft: 0 }}>
          <i class="i i-forward ticket-escalated-icon" />
          <span className="message-escalation">Response expected before: {expectedResponseBy}</span>
        </div>
      </div>
    );
  }

  return null;
};

export default React.memo(Banner);
