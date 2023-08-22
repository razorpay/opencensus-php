import { Link } from 'react-router-dom';

const baseUrl = {
  trf: '/transfers/',
  rvrsl: '/reversals/',
};

const sources = {
  transfer: 'source',
  reversal: 'transfer_id',
};

export const idItem = (id) => <code>{id}</code>;

/* if label not present id will be used as label */
export const idLink = (id = '', label) => {
  let url = baseUrl[id.split('_')[0]];
  const item = label || idItem(id);
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
const makePropLink = (idKey, labelKey) => (item) => idLink(item[idKey], item[labelKey]);

export const makeIdLink = (type) => (item) => {
  return idLink(item[`${item.entity === type ? '' : `${type}_`}id`]);
};

export const payment = makeIdLink('payment');
export const refund = makeIdLink('refund');
export const batch = (item) => idItem(item.id);
export const settlement = makeIdLink('settlement');
export const order = makeIdLink('order');
export const dispute = makeIdLink('dispute');

export const transfer = makeIdLink('transfer');
export const customerRefund = makeIdLink('customerRefundId');
export const source = (item) => idLink(item[sources[item.entity]]);
export const recipient = makePropLink('recipient');
export const reversal = makeIdLink('reversal');
