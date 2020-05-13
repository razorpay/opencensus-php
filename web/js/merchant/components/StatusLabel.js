import { titleCase } from 'common/utils/rzp-utils';

const StatusLabel = statusMap => ({ status = '' }) => (
  <span class={`status-label label ${statusMap[status.toLowerCase()]}`}>
    {titleCase(status)}
  </span>
);

export const invoiceStatusMap = {
  draft: 'label-muted',
  issued: 'label-info',
  created: 'label-info',
  overdue: 'label-pending',
  partially_paid: 'label-pending',
  paid: 'label-success',
  cancelled: 'label-danger',
  expired: 'label-danger',
  next_due: 'label-semi-muted',
};

export const paymentPagesStatusMap = {
  active: 'label-info',
  inactive: 'label-muted',
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

export const refundStatusMap = {
  processed: 'label-success',
  processing: 'label-info',
  failed: 'label-danger',
};

export const settlementStatusMap = {
  initiated: 'label-info',
  created: 'bg-light',
  scheduled: 'bg-light',
  processed: 'label-success',
  failed: 'label-danger',
};

export const batchUploadStatusMap = {
  created: 'bg-light',
  processing: 'label-info',
  partially_processed: 'label-info',
  processed: 'label-success',
  failure: 'label-danger',
  cancelled: 'label-danger',
  paused: 'label-yellow',
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

export const activationStatusMap = {
  activated: 'label-success',
  rejected: 'label-danger',
  needs_clarification: 'label-pending',
  under_review: 'label-info',
  instantly_activated: 'label-muted',
};

export const disputeStatusMap = {
  open: 'label-danger',
  won: 'label-success',
  under_review: 'label-warning',
  closed: 'label-muted',
  lost: 'label-lost',
};

export const tokenStatusMap = {
  confirmed: 'label-success',
  initiated: 'label-info',
  rejected: 'label-danger',
};

export const offerStatusMap = {
  Enabled: 'label-info',
  Disabled: 'label-muted',
};

export const internationalStatusMap = {
  'access-requested': 'bg-primary',
  enabled: 'label-success',
};

const entityMap = {
  payment: paymentStatusMap,
  refund: refundStatusMap,
  settlement: settlementStatusMap,
  invoice: invoiceStatusMap,
  payment_link: paymentPagesStatusMap,
  order: orderStatusMap,
  batch: batchUploadStatusMap,
  virtual_account: virtualAccountStatusMap,
  subscription: subscriptionStatusMap,
  plan: planStatusMap,
  activation: activationStatusMap,
  dispute: disputeStatusMap,
  token: tokenStatusMap,
  offer: offerStatusMap,
  international: internationalStatusMap,
};

export const InvoiceStatusLabel = StatusLabel(invoiceStatusMap);
export const PaymentPagesStatusLabel = StatusLabel(paymentPagesStatusMap);
export const OrderStatusLabel = StatusLabel(orderStatusMap);
export const PaymentStatusLabel = StatusLabel(paymentStatusMap);
export const SettlementStatusLabel = StatusLabel(settlementStatusMap);
export const BatchUploadStatusLabel = StatusLabel(batchUploadStatusMap);
export const VirtualAccountStatusLabel = StatusLabel(virtualAccountStatusMap);
export const SubscriptionStatusLabel = StatusLabel(subscriptionStatusMap);
export const PlanStatusLabel = StatusLabel(planStatusMap);
export const ActivationStatusLabel = StatusLabel(activationStatusMap);
export const DisputeStatusLabel = StatusLabel(disputeStatusMap);
export const TokenStatusLabel = StatusLabel(tokenStatusMap);
export const OfferStatusLabel = StatusLabel(offerStatusMap);
export const RefundStatusLabel = StatusLabel(refundStatusMap);
export const InternationalStatusLabel = StatusLabel(internationalStatusMap);

export default item => StatusLabel(entityMap[item.entity])(item);
