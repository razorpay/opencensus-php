import { titleCase } from 'rzp/utils/rzp-utils';

const StatusLabel = statusMap => ({ status }) =>
  <span class={`status-label label ${statusMap[status]}`}>
    {titleCase(status)}
  </span>;

export const invoiceStatusMap = {
  draft: 'label-muted',
  issued: 'label-info',
  overdue: 'label-pending',
  partially_paid: 'label-pending',
  paid: 'label-success',
  cancelled: 'label-danger',
  expired: 'label-danger',
  next_due: 'label-semi-muted',
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

export const virtualAccountStatusMap = {
  active: 'label-info',
  closed: 'label-danger',
  paid: 'label-success',
};

export const subscriptionStatusMap = {
  created: 'bg-light',
  authenticated: 'label-info',
  active: 'label-success',
  pending: 'label-pending',
  cancelled: 'label-danger',
  halted: 'label-danger',
  expired: 'label-danger',
  completed: 'label-muted',
};

export const planStatusMap = {
  active: 'label-success',
  inactive: 'label-muted',
};

const entityMap = {
  payment: paymentStatusMap,
  settlement: settlementStatusMap,
  invoice: invoiceStatusMap,
  order: orderStatusMap,
  batch: batchUploadStatusMap,
  virtual_account: virtualAccountStatusMap,
  subscription: subscriptionStatusMap,
  plan: planStatusMap,
};

export const InvoiceStatusLabel = StatusLabel(invoiceStatusMap);
export const OrderStatusLabel = StatusLabel(orderStatusMap);
export const PaymentStatusLabel = StatusLabel(paymentStatusMap);
export const SettlementStatusLabel = StatusLabel(settlementStatusMap);
export const BatchUploadStatusLabel = StatusLabel(batchUploadStatusMap);
export const VirtualAccountStatusLabel = StatusLabel(virtualAccountStatusMap);
export const SubscriptionStatusLabel = StatusLabel(subscriptionStatusMap);
export const PlanStatusLabel = StatusLabel(planStatusMap);

export default item => StatusLabel(entityMap[item.entity])(item);
