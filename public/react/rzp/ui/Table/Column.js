import { Link } from 'react-router-dom';
import FormattedAmount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import StatusLabel from 'merchant/components/StatusLabel';

const idBaseUrl = {
  pay: '/payments/',
  rfnd: '/refunds/',
  order: '/orders/',
  trf: '/marketplace/transfers/',
  acc: '/marketplace/accounts/',
  rvrsl: '/marketplace/reversals/',
};

export const idLink = (id, which) => {
  var idSplit = which || id.split('_')[0];
  return <Link to={idBaseUrl[which] + id}><code>{id}</code></Link>;
};

export const amount = [
  'Amount',
  item => <FormattedAmount value={item.amount} />,
];
export const email = ['Email', item => item.email];
export const contact = ['Contact', item => item.contact];
export const status = ['Status', item => StatusLabel(item)];
export const createdAt = [
  'Created At',
  item => <Time value={item.created_at} format="DD MMM YYYY, hh:mm:ss a" />,
];

export const paymentId = ['Payment ID', item => idLink(item.id, 'pay')];
export const paymentOrder = orders => ['Order ID', item => orders[item.id]];

export const refundId = ['Refund ID', item => idLink(item.id, 'pay')];
export const refundPayment = [
  'Payment ID',
  item => idLink(item.payment_id, 'pay'),
];

export const orderId = ['Order ID', item => idLink(item.id, 'order')];
export const attempts = ['Attempts', item => item.attempts];
export const receipt = ['Receipt', item => item.receipt];

export const transferId = ['Transfer ID', item => idLink(item.id, 'trf')];
export const transferSource = ['Source', item => idLink(item.source)];
export const transferRecipient = ['Recipient', item => idLink(item.recipient)];

export const reversalId = ['Reversal ID', item => idLink(item.id, 'rvrsl')];
export const reversalTransfer = [
  'Transfer ID',
  item => idLink(item.transfer_id, 'trf'),
];
