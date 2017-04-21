import { titleCase } from 'rzp/utils/rzp-utils'

const StatusLabel = (statusMap) => ({ status }) => (
  <span class={`status-label label ${statusMap[status]}`}>{titleCase(status)}</span>
)

export const invoiceStatusMap = {
  draft: 'label-muted',
  issued: 'label-info',
  paid: 'label-success',
  cancelled: 'label-danger',
  expired: 'label-danger',
}

export const orderStatusMap = {
  created: 'bg-light',
  attempted: 'bg-info',
  paid: 'bg-success'
}

export const paymentStatusMap = {
  created: 'bg-light',
  authorized: 'bg-info',
  captured: 'bg-success',
  failed: 'bg-danger',
  refunded: 'bg-primary'
}

export const settlementStatusMap = {
  created: 'bg-light',
  processed: 'bg-success',
  failed: 'bg-danger'
}

export const batchUploadStatusMap = {
  created: 'bg-light',
  processing: 'bg-info',
  processed: 'bg-success',
  failure: 'bg-danger',
}

export const InvoiceStatusLabel = StatusLabel(invoiceStatusMap)
export const OrderStatusLabel = StatusLabel(orderStatusMap)
export const PaymentStatusLabel = StatusLabel(paymentStatusMap)
export const SettlementStatusLabel = StatusLabel(settlementStatusMap)
export const BatchUploadStatusLabel = StatusLabel(batchUploadStatusMap)
