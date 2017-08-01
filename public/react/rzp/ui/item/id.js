import { Link } from 'react-router-dom';

const baseUrl = {
  pay: '/payments/',
  rfnd: '/refunds/',
  order: '/orders/',
  va: '/virtualaccounts/',
  plan: '/plans/',
  sub: '/subscriptions/',
  trf: '/route/transfers/',
  // acc: '/route/accounts/',
  // rvrsl: '/route/reversals/',
};

const sources = {
  payment: 'order_id',
  refund: 'payment_id',
  transfer: 'source',
  reversal: 'transfer_id',
};

export const idItem = id => <code>{id}</code>;

export const idLink = id => {
  var url = baseUrl[id.split('_')[0]];
  var item = idItem(id);
  if (url) {
    url += id;
    return <Link to={url}>{item}</Link>;
  }
  return item;
};

const makePropLink = prop => item => idLink(item[prop]);

export const makeIdLink = type => item => {
  return idLink(item[(item.entity === type ? '' : `${type}_`) + 'id']);
};

export const payment = makeIdLink('payment');
export const refund = makeIdLink('refund');
export const batch = item => idItem(item.id);
export const settlement = makeIdLink('settlement');
export const order = makeIdLink('order');

export const transfer = makeIdLink('transfer');
export const source = item => idLink(item[sources[item.entity]]);
export const recipient = makePropLink('recipient');
export const reversal = makeIdLink('reversal');
