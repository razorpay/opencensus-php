import { Link } from 'react-router-dom';

const baseUrl = {
  pay: '/payments/',
  rfnd: '/refunds/',
  order: '/orders/',
  va: '/virtualaccounts/',
  plan: '/plans/',
  sub: '/subscriptions/',
  trf: '/route/transfers/',
  disp: '/disputes/',
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

/* if label not present id will be used as label */
export const idLink = (id, label) => {
  var url = baseUrl[id.split('_')[0]];
  var item = label || idItem(id);
  if (url) {
    url += id;
    return <Link to={url}>{item}</Link>;
  }
  return item;
};

/* 
  idKey: value of this key in item object will be appened to url
  labeKey: value of this key in item object will be displayed as label in link
*/
const makePropLink = (idKey, labelKey) => item =>
  idLink(item[idKey], item[labelKey]);

export const makeIdLink = type => item => {
  return idLink(item[(item.entity === type ? '' : `${type}_`) + 'id']);
};

export const payment = makeIdLink('payment');
export const refund = makeIdLink('refund');
export const batch = item => idItem(item.id);
export const settlement = makeIdLink('settlement');
export const order = makeIdLink('order');
export const dispute = makeIdLink('dispute');

export const transfer = makeIdLink('transfer');
export const source = item => idLink(item[sources[item.entity]]);
export const recipient = makePropLink('recipient');
export const reversal = makeIdLink('reversal');
