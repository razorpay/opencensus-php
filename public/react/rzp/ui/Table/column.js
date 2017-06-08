import * as items from './item';

export const amount = [
  <div style={{ textAlign: 'right' }}>Amount</div>,
  items.amount(),
];
export const amountRefunded = [
  'Amount Refunded',
  items.amount('amount_refunded'),
];
export const amountTransferred = [
  'Amount Transferred',
  items.amount('amount_transferred'),
];

export const email = ['Email', items.email];
export const contact = ['Contact', items.contact];
export const currency = ['Currency', item => item.currency];
export const status = ['Status', items.status];
export const createdAt = ['Created At', items.createdAt];

// this is notes + order_id mixed
export const paymentOrder = orders => ['Order ID', item => orders[item.id]];

export const paymentId = ['Payment ID', items.paymentId];
export const refundId = ['Refund ID', items.refundId];
export const settlementId = ['Settlement ID', items.settlementId];
export const orderId = ['Order ID', items.orderId];
export const attempts = ['Attempts', item => item.attempts];
export const receipt = ['Receipt', item => item.receipt];

export const transferId = ['Transfer ID', item => items.idItem(item.id)];
export const source = ['Source', item => items.idLink(item.source)];
export const recipient = ['Recipient', item => items.idLink(item.recipient)];

export const reversalId = ['Transfer ID', item => items.idItem(item.id)];

export const batchId = ['Batch ID', item => items.idItem(item.id)];
export const batchCount = ['Count', item => item.total_count];
export const batchDownload = mode => [
  'Actions',
  item => (
    <Link
      class="btn btn-default btn-xs"
      to={`/${mode}/batches/${item.id}/download`}
      target="_blank"
    >
      Download
    </Link>
  ),
];
