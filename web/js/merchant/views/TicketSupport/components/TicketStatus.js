import {
  statuses,
  workflowStatusClass,
  TICKET_STATUS_LABELS,
  cssClassToBadgeVariant,
} from './data';
import { Badge } from '@razorpay/blade/components';

import React from 'react';
import { isTicketMxEscalated } from '../utils';

const TicketStatus = ({ workflow, ticket, shouldUseBladeBadge = false } = {}) => {
  const today = new Date();
  const dueDate = new Date(ticket?.fr_due_by || parseInt(workflow?.due_date, 10));

  let statusLabel = workflow?.state || statuses[ticket?.status]?.name;

  const isDelayed =
    [
      TICKET_STATUS_LABELS.BEING_PROCESSED,
      TICKET_STATUS_LABELS.ACTIVE,
      TICKET_STATUS_LABELS.IN_PROGRESS,
    ].includes(statusLabel) && today > dueDate;
  const status = workflow?.state || statuses[ticket.status];

  let bladeBadgeEmphasis = 'subtle';
  let cssClass = isDelayed
    ? workflowStatusClass[TICKET_STATUS_LABELS.DELAYED]
    : workflowStatusClass[status] || status?.class;

  statusLabel = isDelayed ? TICKET_STATUS_LABELS.IN_PROGRESS : statusLabel;

  if (isTicketMxEscalated(ticket)) {
    statusLabel = TICKET_STATUS_LABELS.ESCALATED;
    cssClass = 'danger';
    bladeBadgeEmphasis = 'intense';
  }

  return shouldUseBladeBadge ? (
    <Badge color={cssClassToBadgeVariant[cssClass]} size="medium" emphasis={bladeBadgeEmphasis}>
      {statusLabel}
    </Badge>
  ) : (
    <span className={`label ticket-status-label label-${cssClass}`}>{statusLabel}</span>
  );
};

export default TicketStatus;
