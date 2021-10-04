import React from 'react';
import { getEscalationType, getResponseArrivalType } from '../utils';
import moment from 'moment';
const Banner = ({ ticket }) => {
  const ESCALATION_TYPE = getEscalationType(ticket);
  const RESPONSE_ARRIVAL_TYPE = getResponseArrivalType(ticket);
  const expectedResponseBy = moment(ticket.fr_due_by).format('HH:mm, DD MMM');

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
        <div className="col-xs-2" />
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
