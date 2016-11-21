import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'
import Time from 'rzp/ui/Time'
import Amount from 'rzp/ui/Amount'
import InvoiceStatus from './InvoiceStatus'

const InvoiceListItem = ({ invoice }) => {
  return (
    <tr>
      <td>
        <a href={`#/app/invoices/${invoice.id}`}>{invoice.id}</a>
      </td>
      <td>
        <Time value={invoice.date} />
      </td>
      <td>
        {
          invoice.customer_details.customer_name ||
          invoice.customer_details.customer_email ||
          invoice.customer_details.customer_contact
        }
      </td>
      <td>{invoice.short_url}</td>
      <td class='text-right'>
        <Amount value={invoice.amount} />
      </td>
      <td class='text-right'>
        <InvoiceStatus status={invoice.status} />
      </td>
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
            <th>Invoice Id</th>
            <th>Invoice Date</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th class='text-right'>Amount (INR)</th>
            <th class='text-right'>Status</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
