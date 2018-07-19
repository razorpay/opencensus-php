import * as items from './index';
import * as id from './id';
import { getAmount, getTime } from 'rzp/ui/item';
import { makeIdLink } from 'rzp/ui/item/id';
import { getIntervalCycle, subString } from 'rzp/utils/rzp-utils';

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
export const paidCount = {
  title: 'Paid Count',
  value: item => item.paid_count,
};
export const paidOn = { title: 'Paid On', value: items.createdAt };
export const createdAt = { title: 'Created At', value: items.createdAt };
export const attempts = { title: 'Attempts', value: item => item.attempts };
export const receipt = { title: 'Receipt', value: item => item.receipt };
export const totalCount = { title: 'Count', value: item => item.total_count };

export const paymentId = { title: 'Payment Id', value: id.payment };
export const orderId = { title: 'Order Id', value: id.order };
export const rzpOrderId = { title: 'Razorpay Order Id', value: id.rzpOrder };
export const refundId = { title: 'Refund Id', value: id.refund };
export const settlementId = { title: 'Settlemt Id', value: id.settlement };
export const transferId = { title: 'Transfer Id', value: id.transfer };
export const reversalId = { title: 'Reversal Id', value: id.reversal };
export const source = { title: 'Source', value: id.source };
export const recipient = { title: 'Recipient', value: id.recipient };
export const batchId = { title: 'Batch Id', value: id.batch };
export const batchIdLink = { title: 'Batch Id', value: id.batchLink };
export const disputeId = { title: 'Dispute Id', value: id.dispute };
export const submerchant = { title: 'Merchant Name', value: id.submerchant };
export const submerchantId = { title: 'Merchant ID', value: id.submerchantId };

export const mapValues = values => title => {
  return { title, value: item => values[item.id] };
};

// this is notes order_id mixed
export const paymentOrder = orders => mapValues(orders)(orderId.title);

// Razorpay order_id
export const rzpPaymentOrder = orders => mapValues(orders)(rzpOrderId.title);

// Virtual Accounts
export const virtualAccountId = {
  title: 'Virtual Account Id',
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
  title: 'Subscription Id',
  value: makeIdLink('subscription'),
};

export const customerId = {
  title: 'Customer Id',
  value: item => item.customer_id,
};

export const nextDueOn = {
  title: 'Next Due on',
  value: getTime('charge_at', 'MMM DD YYYY'),
};

// Plans
export const planId = {
  title: 'Plan Id',
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

//Batch

export const batchName = {
  title: 'Batch Name',
  value: item => subString(item.name, 50),
};
