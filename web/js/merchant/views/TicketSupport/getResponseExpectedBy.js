import moment from 'moment'; // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
import { isTicketMxEscalated } from './utils';

/**
 * Adds business hours to a date, accounting for weekends and outside business hours
 * Business hours are 8 AM to 8 PM, Monday to Friday
 */
const addBusinessHours = (date, hours) => {
  if (!date || isNaN(date.getTime())) return moment(); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232

  const businessStart = 8,
    businessEnd = 20;
  const startMoment = moment(date); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  let hoursAdded = 0,
    iterations = 0;
  const currentMoment = moment(startMoment); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  const MAX_ITERATIONS = 1000;

  while (hoursAdded < hours && iterations < MAX_ITERATIONS) {
    currentMoment.add(1, 'hour');
    iterations++;

    const dayOfWeek = currentMoment.day();
    const currentHour = currentMoment.hour();
    const isBusinessHour =
      dayOfWeek >= 1 && dayOfWeek <= 5 && currentHour >= businessStart && currentHour < businessEnd;

    if (isBusinessHour) hoursAdded += 1;
  }

  return currentMoment;
};

/**
 * Validate date format and check if it's breached (before current time)
 */
const validateDateFormat = (dateString, format) => {
  if (!dateString) return false;

  // Check format based on expected pattern
  const formatPatterns = {
    'DD-MM-YYYY HH:mm': /^\d{2}-\d{2}-\d{4} \d{2}:\d{2}$/,
    'YYYY-MM-DD': /^\d{4}-\d{2}-\d{2}$/,
    created_at: /^\d{4}-\d{2}-\d{2}T/,
  };

  return formatPatterns[format]?.test(dateString) || false;
};

/**
 * Check if a date string is before current time
 */
const isDateBreached = (dateString, format) => {
  if (!dateString) return true;

  const parsedDate = moment(dateString, format); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  // For date-only formats, check against end of business day
  return format === 'YYYY-MM-DD'
    ? parsedDate.hour(20).minute(0).second(0).isBefore(moment()) // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    : parsedDate.isBefore(moment()); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
};

/**
 * Format date as "Apr 22, 5:20 PM" or "Apr 22, 8:00 PM" for date-only formats
 */
const formatDate = (dateString, format) => {
  const date = moment(dateString, format); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  return format === 'YYYY-MM-DD'
    ? `${date.format('MMM D')}, 8:00 PM`
    : `${date.format('MMM D, h:mm A')}`;
};

/**
 * Determine when a response can be expected for a ticket
 */
export const getResponseExpectedBy = (ticket, shortMessage = false) => {
  if (!ticket) return { responseBy: '', prefix: '', shouldShowEta: false };

  const isMXEscalated = isTicketMxEscalated(ticket);
  const prefix = shortMessage
    ? 'by'
    : isMXEscalated
    ? 'This has been escalated for higher priority resolution. Expect a response by'
    : 'Expect a response by';

  // Process promises in order of priority
  const promises = [
    { value: ticket?.custom_fields?.cf_promise_three, format: 'YYYY-MM-DD' },
    { value: ticket?.custom_fields?.cf_promise_two, format: 'YYYY-MM-DD' },
    { value: ticket?.custom_fields?.cf_promise_one, format: 'DD-MM-YYYY HH:mm' },
  ];

  // Check each promise
  for (const promise of promises) {
    if (
      promise.value &&
      validateDateFormat(promise.value, promise.format) &&
      !isDateBreached(promise.value, promise.format)
    ) {
      return {
        responseBy: formatDate(promise.value, promise.format),
        prefix,
        shouldShowEta: true,
      };
    }
  }

  // Default: 18 business hours from created_at
  if (ticket.created_at && validateDateFormat(ticket.created_at, 'created_at')) {
    try {
      const createdAtDate = new Date(ticket.created_at);
      if (!isNaN(createdAtDate.getTime())) {
        const defaultResponseExpectedBy = addBusinessHours(createdAtDate, 18);

        const now = moment(); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
        if (!defaultResponseExpectedBy.isBefore(now)) {
          return {
            responseBy: defaultResponseExpectedBy.format('MMM D, h:mm A'),
            prefix,
            shouldShowEta: true,
          };
        }
      }
    } catch (error) {
      // Fall through to default return
    }
  }

  // Default when no valid ETA can be determined
  return { responseBy: '', prefix: '', shouldShowEta: false };
};
