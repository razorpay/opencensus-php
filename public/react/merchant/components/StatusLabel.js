import { titleCase } from 'rzp/utils/rzp-utils';

const StatusLabel = statusMap => ({ status }) => (
  <span class={`status-label label ${statusMap[status]}`}>
    {titleCase(status)}
  </span>
);

export const invoiceStatusMap = {
  draft: 'label-muted',
  issued: 'label-info',
  paid: 'label-success',
  cancelled: 'label-danger',
  expired: 'label-danger',
};

export const orderStatusMap = {
  created: 'bg-light',
  attempted: 'label-info',
  paid: 'label-success',
};

export const paymentStatusMap = {
  created: 'bg-light',
  authorized: 'label-info',
  captured: 'label-success',
  failed: 'label-danger',
  refunded: 'bg-primary',
};

export const settlementStatusMap = {
  created: 'bg-light',
  processed: 'label-success',
  failed: 'label-danger',
};

export const batchUploadStatusMap = {
  created: 'bg-light',
  processing: 'label-info',
  processed: 'label-success',
  failure: 'label-danger',
};

export const InvoiceStatusLabel = StatusLabel(invoiceStatusMap);
export const OrderStatusLabel = StatusLabel(orderStatusMap);
export const PaymentStatusLabel = StatusLabel(paymentStatusMap);
export const SettlementStatusLabel = StatusLabel(settlementStatusMap);
export const BatchUploadStatusLabel = StatusLabel(batchUploadStatusMap);
