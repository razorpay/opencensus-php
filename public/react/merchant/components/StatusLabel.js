import { titleCase } from 'rzp/utils/rzp-utils'

const StatusLabel = (statusMap) => ({ status }) => (
  <span class={`label ${statusMap[status]}`}>{titleCase(status)}</span>
)

export const invoiceStatusMap = {
  draft: 'bg-muted',
  issued: 'bg-info',
  paid: 'bg-success',
  expired: 'bg-danger'
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

export const InvoiceStatusLabel = StatusLabel(invoiceStatusMap)
export const OrderStatusLabel = StatusLabel(orderStatusMap)
export const PaymentStatusLabel = StatusLabel(paymentStatusMap)
