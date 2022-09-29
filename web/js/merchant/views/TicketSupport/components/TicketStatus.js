import { statuses, workflowStatusClass, TICKET_STATUS_LABELS } from './data';
import React from 'react';

const TicketStatus = ({ workflow, ticket } = {}) => {
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

  const cssClass = isDelayed
    ? workflowStatusClass[TICKET_STATUS_LABELS.DELAYED]
    : workflowStatusClass[status] || status?.class;

  statusLabel = isDelayed ? TICKET_STATUS_LABELS.IN_PROGRESS : statusLabel;
  return <span className={`label ticket-status-label label-${cssClass}`}>{statusLabel}</span>;
};

export default TicketStatus;
