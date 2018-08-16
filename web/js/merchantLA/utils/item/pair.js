import * as items from './index';
import * as id from './id';
import { getAmount, getTime } from 'merchantLA/utils/item';
import { makeIdLink } from 'merchantLA/utils/item/id';
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

export const settlementId = { title: 'Settlemt Id', value: id.settlement };
export const transferId = { title: 'Transfer Id', value: id.transfer };
export const reversalId = { title: 'Reversal Id', value: id.reversal };
export const source = { title: 'Source', value: id.source };
export const recipient = { title: 'Recipient', value: id.recipient };

export const mapValues = values => title => {
  return { title, value: item => values[item.id] };
};
