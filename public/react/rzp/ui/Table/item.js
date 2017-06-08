import { Link } from 'react-router-dom';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import Time from 'rzp/ui/Time';
import StatusLabel from 'merchant/components/StatusLabel';

const baseUrl = {
  pay: '/payments/',
  rfnd: '/refunds/',
  order: '/orders/',
  trf: '/marketplace/transfers/',
  // acc: '/marketplace/accounts/',
  rvrsl: '/marketplace/reversals/',
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
export const id = type => item =>
  idLink(item[(item.entity === type ? '' : `${type}_`) + 'id']);

export const amount = key => item => (
  <div class="text-right">
    {getFixedINRAmount(item[key || 'amount'])}
  </div>
);

export const email = item => item.email;
export const contact = item => item.contact;
export const status = item => StatusLabel(item);
export const createdAt = item => (
  <Time value={item.created_at} format="DD MMM YYYY, hh:mm:ss a" />
);

export const paymentId = id('payment');
export const refundId = id('refund');
export const settlementId = id('settlement');
export const orderId = id('order');
export const transferId = id('transfer');
export const reversalId = id('reversal');
