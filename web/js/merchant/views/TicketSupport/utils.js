import moment from 'moment';
import { statuses } from './components/data';
import { getAttachmentExpiryTime } from 'common/utils/rzp-utils';
import * as EventEmitter from 'eventemitter3';

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

export function getResponseArrivalType(ticket) {
  const STATUS = statuses[ticket.status] && statuses[ticket.status].name;

  if (STATUS === 'Active' || STATUS === 'Work In Progress') {
    const today = new Date();
    // const dueDate = new Date(ticket.fr_due_by);
    const dueDate = new Date('2021-01-16T06:06:27Z');

    if (today.getTime() < dueDate.getTime()) {
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
