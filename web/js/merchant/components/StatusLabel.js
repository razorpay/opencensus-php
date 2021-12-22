import { titleCase } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';

const statusLabel = (statusMap, statusDescriptionMap) => ({ status = '', className }) => (
  <span
    class={`status-label label ${statusMap ? statusMap[status?.toLowerCase()] : ''} ${className}`}
  >
    {status === 'activated_mcc_pending' ? 'Activated' : titleCase(status)}
    {statusDescriptionMap && statusDescriptionMap[status] && (
      <i class="i i-info-circle status-label-info-icon">
        <Popover persistent={false} theme="dark">
          <PopoverBody>
            <p>{statusDescriptionMap[status]}</p>
          </PopoverBody>
        </Popover>
      </i>
    )}
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

export const routeTransfersStatusMap = {
  created: 'label-muted',
  pending: 'label-muted',
  processed: 'label-info',
  failed: 'label-danger',
  reversed: 'bg-primary',
  partially_reversed: 'bg-primary',
};

export const orderStatusMap = {
  created: 'bg-light',
  attempted: 'label-info',
  paid: 'label-success',
  placed: 'label-success',
};

export const orderStatusDescMap = {
  placed: 'order is placed via COD',
};

export const paymentStatusMap = {
  created: 'bg-light',
  authorized: 'label-info',
  captured: 'label-success',
  failed: 'label-danger',
  refunded: 'bg-primary',
  pending: 'label-pending',
};

export const paymentStatusDescMap = {
  pending: 'order is placed via COD',
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
  on_temporary_hold: 'label-danger',
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

export const SubmerchantSettlementStatusMap_New = {
  active: 'label-light-positive',
  inactive: 'label-light-information',
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

export const storeProductsStatusMap = {
  active: 'label-info',
  inactive: 'label-muted',
};

export const InvoiceStatusLabel = statusLabel(invoiceStatusMap);
export const PaymentPagesStatusLabel = statusLabel(paymentPagesStatusMap);
export const RouteTransfersStatusLabel = statusLabel(routeTransfersStatusMap);
export const OrderStatusLabel = statusLabel(orderStatusMap, orderStatusDescMap);
export const PaymentStatusLabel = statusLabel(paymentStatusMap, paymentStatusDescMap);
export const SettlementStatusLabel = statusLabel(settlementStatusMap);
export const BatchUploadStatusLabel = statusLabel(batchUploadStatusMap);
export const VirtualAccountStatusLabel = statusLabel(virtualAccountStatusMap);
export const SubscriptionStatusLabel = statusLabel(subscriptionStatusMap);
export const PlanStatusLabel = statusLabel(planStatusMap);
export const ActivationStatusLabel = statusLabel(activationStatusMap);
export const DisputeStatusLabel = statusLabel(disputeStatusMap);
export const TokenStatusLabel = statusLabel(tokenStatusMap);
export const OfferStatusLabel = statusLabel(offerStatusMap);
export const RefundStatusLabel = statusLabel(refundStatusMap);
export const CommissionInvoiceStatusLabel = statusLabel(commissionInvoiceStatusMap);
export const SubmerchantSettlementLabel = statusLabel(SubmerchantSettlementStatusMap);
export const SubmerchantSettlementLabelNew = statusLabel(SubmerchantSettlementStatusMap_New);
export const QRCodeStatusLabel = statusLabel(qrCOdeStatusMap);
export const XSubmerchantCAStatusLabel = statusLabel(XSubmerchantCAStatusMap);
export const XSubmerchantVAStatusLabel = statusLabel(XSubmerchantVAStatusMap);
export const StoreProductsStatusLabel = statusLabel(storeProductsStatusMap);

// statusLabel is being used in lot of places, not sure which place is triggering this error https://sentry.io/organizations/rzp/issues/2660520384/?project=5699615
// item.entity is coming as undefined. Passing {} for now, this will hide the status, enabling us to
// know which api is returning incorrect data and silence the errors for now.
export default (item) => statusLabel(item.entity ? entityMap[item.entity] : {})(item);
