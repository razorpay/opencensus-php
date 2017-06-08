import * as items from './item';

export const amount = ['Amount', items.amount()];
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

export const transferId = ['Transfer ID', items.transferId];
export const source = ['Source', item => idLink(item.source)];
export const recipient = ['Recipient', item => idLink(item.recipient)];

export const reversalId = ['Reversal ID', items.reversalId];

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
