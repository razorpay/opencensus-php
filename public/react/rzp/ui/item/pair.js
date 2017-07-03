import * as items from './index';
import * as id from './id';
import { getAmount, getTime } from 'rzp/ui/item';
import { makeIdLink } from 'rzp/ui/item/id';
import { getIntervalCycle } from 'rzp/utils/rzp-utils';

export const withClick = onClick => ({ value, ...rest }) => {
  return {
    value: <span onClick={onClick}>{value}</span>,
    ...rest,
  };
};

const textRightClass = 'text-right';

export const amount = {
  title: 'Amount',
  value: items.amount,
  columnClass: textRightClass,
};
export const amountRefunded = {
  title: 'Amount Refunded',
  value: items.amountRefunded,
  columnClass: textRightClass,
};
export const amountTransferred = {
  title: 'Amount Transferred',
  value: items.amountTransferred,
  columnClass: textRightClass,
};

export const email = { title: 'Email', value: item => item.email };
export const contact = { title: 'Contact', value: item => item.contact };
export const currency = { title: 'Currency', value: item => item.currency };
export const status = { title: 'Status', value: items.status };
export const createdAt = { title: 'Created At', value: items.createdAt };
export const attempts = { title: 'Attempts', value: item => item.attempts };
export const receipt = { title: 'Receipt', value: item => item.receipt };
export const totalCount = { title: 'Count', value: item => item.total_count };

export const paymentId = { title: 'Payment ID', value: id.payment };
export const orderId = { title: 'Order ID', value: id.order };
export const refundId = { title: 'Refund ID', value: id.refund };
export const settlementId = { title: 'Settlemt ID', value: id.settlement };
export const transferId = { title: 'Transfer ID', value: id.transfer };
export const reversalId = { title: 'Reversal ID', value: id.reversal };
export const source = { title: 'Source', value: id.source };
export const recipient = { title: 'Recipient', value: id.recipient };
export const batchId = { title: 'Batch ID', value: id.batch };

export const mapValues = values => title => {
  return { title, value: item => values[item.id] };
};

// this is notes + order_id mixed
export const paymentOrder = orders => mapValues(orders)(orderId.title);

// Virtual Accounts
export const virtualAccountId = {
  title: 'Virtual Account ID',
  value: makeIdLink('virtual_account'),
};
export const accountDescription = {
  title: 'Account Description',
  value: item => item.description,
};
export const amountPaid = {
  title: 'Amount Paid',
  columnClass: textRightClass,
  value: getAmount('amount_paid'),
};

// Subscriptions
export const subscriptionId = {
  title: 'Subscription ID',
  value: makeIdLink('subscription'),
};

export const customerId = {
  title: 'Customer ID',
  value: item => item.customer_id,
};

export const nextDueOn = {
  title: 'Next Due on',
  value: getTime('charge_at', 'MMM DD YYYY'),
};

// Plans
export const planId = {
  title: 'Plan ID',
  value: makeIdLink('plan'),
};

export const planName = {
  title: 'Plan Name',
  value: item => item.item.name,
};

export const planAmount = {
  title: 'Amount/Unit (INR)',
  value: getAmount('item.amount'),
  columnClass: textRightClass,
};

export const planBillingCycle = {
  title: 'Billing Cycle',
  value: item => getIntervalCycle(item.interval, item.period),
};
