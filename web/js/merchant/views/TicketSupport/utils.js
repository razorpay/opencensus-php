import moment from 'moment';
import { statuses } from './components/data';
import { getAttachmentExpiryTime } from 'common/utils/rzp-utils';
import * as EventEmitter from 'eventemitter3';
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

export function getEscalationType(ticket) {
  const STATUS = statuses[ticket.status] && statuses[ticket.status].name;

  if (STATUS === 'Active' || STATUS === 'Work In Progress') {
    if (ticket.priority === 4) {
      return 'escalated';
    }

    const today = new Date();
    const dueDate = new Date(ticket.fr_due_by);

    if (today.getTime() > dueDate.getTime()) {
      return 'able-to-escalate';
    }
  }

  return '';
}

export function getTicketStatus(ticket) {
  const STATUS = statuses[ticket.status] && statuses[ticket.status].name;
  return STATUS;
}

export function getResponseArrivalType(ticket) {
  const STATUS = statuses[ticket.status] && statuses[ticket.status].name;
  if (STATUS === 'Active' || STATUS === 'Work In Progress') {
    const today = new Date();
    const dueDate = new Date(ticket.fr_due_by);
    const isTicketCreatedByAgent = ticket.custom_fields.cf_created_by === 'agent';
    if (isTicketCreatedByAgent) {
      return 'reply-before-due';
    } else if (today.getTime() < dueDate.getTime()) {
      return 'within-expected-time';
    }

    return 'longer-than-usual';
  } else if (STATUS === 'Awaiting Your Reply') {
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
  '2': 'ACTIVE',
  '3': 'WORK_IN_PROGRESS',
  '4': 'RESOLVED',
  '5': 'CLOSED',
  '6': 'AWAITING_YOUR_REPLY',
  '8': 'WORK_IN_PROGRESS',
  '9': 'WORK_IN_PROGRESS',
  '10': 'WORK_IN_PROGRESS',
  '11': 'WORK_IN_PROGRESS',
};
