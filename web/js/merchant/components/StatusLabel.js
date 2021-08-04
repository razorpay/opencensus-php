import { titleCase } from 'common/utils/rzp-utils';

const StatusLabel = (statusMap) => ({ status = '', className }) => (
  <span class={`status-label label ${statusMap[status.toLowerCase()]} ${className}`}>
    {status === 'activated_mcc_pending' ? 'Activated' : titleCase(status)}
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
  pending: 'label-pending',
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
  created: 'bg-primary',
  initiated: 'bg-light',
};

export const settlementStatusMap = {
  initiated: 'label-info',
  created: 'bg-light',
  scheduled: 'label-warning',
  on_hold: 'label-danger',
  processed: 'label-success',
  failed: 'label-danger',
  partially_processed: 'label-partial-process',
  reversed: 'label-muted',
};

export const batchUploadStatusMap = {
  created: 'bg-light',
  processing: 'label-info',
  partially_processed: 'label-info',
  processed: 'label-success',
  failure: 'label-danger',
  cancelled: 'label-danger',
  cancellation_requested: 'label-info',
  paused: 'label-yellow',
  scheduled: 'label-info',
};

export const virtualAccountStatusMap = {
  active: 'label-info',
  closed: 'label-danger',
  paid: 'label-success',
};

export const qrCOdeStatusMap = {
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
  paused: 'label-yellow',
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
  activated_mcc_pending: 'label-success',
};

export const disputeStatusMap = {
  open: 'label-danger',
  won: 'label-success',
  under_review: 'label-review',
  closed: 'label-muted',
  lost: 'label-lost',
};

export const tokenStatusMap = {
  confirmed: 'label-success',
  initiated: 'label-info',
  rejected: 'label-danger',
  cancelled: 'label-danger',
};

export const offerStatusMap = {
  enabled: 'label-info',
  disabled: 'label-muted',
};

export const commissionInvoiceStatusMap = {
  issued: 'label-muted',
  under_review: 'label-pending',
  approved: 'label-info',
  processed: 'label-success',
};

export const SubmerchantSettlementStatusMap = {
  active: 'label-success',
  inactive: 'label-muted',
};

export const XSubmerchantCAStatusMap = {
  'process started': 'label-info',
  'request Cancelled': 'label-danger',
  unserviceable: 'label-danger',
  'request rejected': 'label-danger',
  'bank kyc in progress': 'label-pending',
  'activation in progress': 'label-pending',
  'request received': 'label-muted',
  active: 'label-success',
  inactive: 'label-muted',
  'on hold': 'label-muted',
};

export const XSubmerchantVAStatusMap = {
  activated: 'label-success',
  activated_mcc_pending: 'label-success',
  under_review: 'label-info',
  rejected: 'label-danger',
  needs_clarification: 'label-pending',
  inactive: 'label-muted',
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
  commissionInvoice: commissionInvoiceStatusMap,
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
export const CommissionInvoiceStatusLabel = StatusLabel(commissionInvoiceStatusMap);
export const SubmerchantSettlementLabel = StatusLabel(SubmerchantSettlementStatusMap);
export const QRCodeStatusLabel = StatusLabel(qrCOdeStatusMap);
export const XSubmerchantCAStatusLabel = StatusLabel(XSubmerchantCAStatusMap);
export const XSubmerchantVAStatusLabel = StatusLabel(XSubmerchantVAStatusMap);
export default (item) => StatusLabel(entityMap[item.entity])(item);
