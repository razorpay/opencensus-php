import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'
import Time from 'rzp/ui/Time'
import InvoiceStatus from './InvoiceStatus'

const InvoiceListItem = ({ invoice }) => {
  return (
    <tr>
      <td>
        <Time value={invoice.invoice_date} />
      </td>
      <td>{invoice.invoice_number}</td>
      <td>
        <InvoiceStatus status={invoice.status} />
      </td>
      <td>
        <Time value={invoice.due_date} />
      </td>
      <td>{invoice.customer.name}</td>
      <td class='text-right'>{invoice.total_amount}</td>
    </tr>
  )
}

export default ({ invoices, isLoading }) => {
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='6' />
  } else if (invoices.length) {
    tableRowComponent = invoices.map(
      (invoice) => <InvoiceListItem key={invoice.id} invoice={invoice} />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='9' message='No Invoices found!' />
  }

  return (
    <div class='table-responsive'>
      <table class='table table-hover'>
        <thead>
          <tr>
            <th>Invoice Date</th>
            <th>Invoice #</th>
            <th>Status</th>
            <th>Due Date </th>
            <th>Customer Name</th>
            <th class='text-right'>Amount (INR)</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
