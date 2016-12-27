import { titleCase } from 'rzp/utils/rzp-utils'

const StatusLabel = (statusMap) => ({ status }) => (
  <span class={`label ${statusMap[status]}`}>{titleCase(status)}</span>
)

export const invoiceStatusMap = {
  draft: 'label-muted',
  issued: 'label-info',
  paid: 'label-success',
  expired: 'label-danger'
}

export const orderStatusMap = {
  created: 'bg-light',
  attempted: 'bg-info',
  paid: 'bg-success'
}

export const InvoiceStatusLabel = StatusLabel(invoiceStatusMap)
export const OrderStatusLabel = StatusLabel(orderStatusMap)
