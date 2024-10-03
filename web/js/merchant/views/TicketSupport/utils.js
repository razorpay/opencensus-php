import moment from 'moment';
import { statuses, TICKET_STATUS_LABELS } from './components/data';
import { getAttachmentExpiryTime } from 'common/utils/rzp-utils';
import EventEmitter from 'eventemitter3';
import { TICKET_BASE_URL } from 'merchant/reducers/config';
import { merchantFetch } from 'merchant/utils/ajax';
import { getDeviceSource } from 'merchant/components/Support/getCommonSupportProperties';
import { isExperimentEnabled } from 'common/splitz/utils';

const monthsMap = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

export function sanitizeRaySubcategory(subcategory = '') {
  return subcategory?.replace(/Ray Dashboard/gi, 'Dashboard') || '';
}

export function getTicketStatus(ticket, workflow = {}) {
  const STATUS = workflow?.state || (statuses[ticket.status] && statuses[ticket.status].name);
  return STATUS;
}

export function getEscalationType(ticket, workflow = {}) {
  const STATUS = getTicketStatus(ticket, workflow);

  if (
    [
      TICKET_STATUS_LABELS.BEING_PROCESSED,
      TICKET_STATUS_LABELS.ACTIVE,
      TICKET_STATUS_LABELS.IN_PROGRESS,
    ].includes(STATUS)
  ) {
    if (ticket.priority === 4) {
      return 'escalated';
    }

    const today = new Date();
    const dueDate = new Date(ticket?.fr_due_by || parseInt(workflow?.due_date, 10));

    if (today.getTime() > dueDate.getTime()) {
      return 'able-to-escalate';
    }
  }

  return '';
}

export function getResponseArrivalType(ticket, workflow = {}) {
  const STATUS = getTicketStatus(ticket, workflow);
  if (
    [
      TICKET_STATUS_LABELS.BEING_PROCESSED,
      TICKET_STATUS_LABELS.ACTIVE,
      TICKET_STATUS_LABELS.IN_PROGRESS,
    ].includes(STATUS)
  ) {
    const today = new Date();
    const dueDate = new Date(ticket?.fr_due_by || parseInt(workflow?.due_date, 10));

    const isTicketCreatedByAgent = ticket?.custom_fields?.cf_created_by === 'agent';
    if (isTicketCreatedByAgent) {
      return 'reply-before-due';
    } else if (today.getTime() < dueDate.getTime()) {
      return 'within-expected-time';
    }

    return 'longer-than-usual';
  } else if (STATUS === TICKET_STATUS_LABELS.ACTION_REQUIRED) {
    return 'waiting-for-customer';
  }

  return '';
}

export function getExpiryTime(awsURL) {
  const expiryTime = getAttachmentExpiryTime(awsURL, 2, 'h');

  // Return time in milliseconds
  return expiryTime.diff(moment(), 'ms');
}

export const CreateTicketEmitter = new EventEmitter();

export const getFormattedDate = (d) => {
  const month = d.getMonth();
  const date = d.getDate();
  const year = d.getFullYear() % 2000;
  return `${date} ${monthsMap[month]}' ${year}`;
};

export const raiseTicket = () => {
  window.rzpAnalytics?.({
    eventCategory: 'Ticket Dashboard',
    eventAction: 'write to us clicked',
    eventLabel: `Tickets`,
  });

  CreateTicketEmitter.emit('create-ticket', 'tickets');
};

export const STATUSES = {
  2: 'ACTIVE',
  3: 'WORK_IN_PROGRESS',
  4: 'RESOLVED',
  5: 'CLOSED',
  6: 'AWAITING_YOUR_REPLY',
  8: 'WORK_IN_PROGRESS',
  9: 'WORK_IN_PROGRESS',
  10: 'WORK_IN_PROGRESS',
  11: 'WORK_IN_PROGRESS',
  14: 'WORK_IN_PROGRESS',
};

export const createWorkFlowTicket = (workflow, user) => {
  const ticketData = new FormData();
  ticketData.set('email', user.email);
  ticketData.set('name', user.name);
  ticketData.set('phone', user.contact_mobile);
  ticketData.set('description', workflow?.description || '');
  ticketData.set('subject', `[Merchant] ${workflow?.sub_category}`);
  ticketData.set('tags[]', 'workflow_ticket');
  ticketData.set('custom_fields[cf_workflow_id]', workflow.id);
  ticketData.set('custom_fields[cf_merchant_id]', user.id);
  ticketData.set('custom_fields[cf_merchant_id_dashboard]', `merchant_dashboard_${user.id}`);

  ticketData.set('custom_fields[cf_new_requester_category]', 'Merchant');
  ticketData.set('custom_fields[cf_new_requester_sub_category]', workflow?.sub_category || '');
  ticketData.set('custom_fields[cf_new_requester_item]', workflow?.item || '');

  ticketData.set('custom_fields[cf_creation_source]', getDeviceSource());

  return merchantFetch({
    url: TICKET_BASE_URL,
    mode: 'live',
    method: 'POST',
    data: ticketData,
  });
};

export const isPaginationEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { support_ticket_pagination: undefined } };

  if (!abExperiments?.support_ticket_pagination) return false;

  return isExperimentEnabled(abExperiments.support_ticket_pagination);
};
