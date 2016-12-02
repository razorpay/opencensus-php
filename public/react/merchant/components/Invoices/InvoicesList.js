import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'
import Time from 'rzp/ui/Time'
import Amount from 'rzp/ui/Amount'
import InvoiceStatus from './InvoiceStatus'

const InvoiceListItem = (props) => {
  let { invoice, ...attrs } = props
  return (
    <tr {...attrs}>
      <td>
        <a href={`#/app/invoices/${invoice.id}`}>{invoice.id}</a>
      </td>
      <td>
        <Time value={invoice.date} />
      </td>
      <td>{invoice.receipt}</td>
      <td>
        {
          invoice.customer_details.customer_contact ||
          invoice.customer_details.customer_email ||
          invoice.customer_details.customer_name
        }
      </td>
      <td>{invoice.short_url}</td>
      <td>{invoice.type}</td>
      <td class='text-right'>
        <Amount value={invoice.amount} />
      </td>
      <td class='text-right'>
        <InvoiceStatus status={invoice.status} />
      </td>
      <td class='text-right'>
        <div class='row-action'>
          <div class='btn-group'>
            <button
              class='btn btn-xs btn-default'
              onClick={props.onEditClick}
            >
              edit
            </button>
            <button
              class='btn btn-xs btn-danger'
              onClick={props.onEditClick}
            >
              delete
            </button>
          </div>
        </div>
      </td>
    </tr>
  )
}

export default (props) => {
  let { invoices, isLoading, highlightRow = () => {} } = props
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='8' />
  } else if (invoices.length) {
    tableRowComponent = invoices.map((invoice) =>
      <InvoiceListItem
        key={invoice.id}
        invoice={invoice}
        class={highlightRow(invoice) ? 'luminate' : ''}
        onEditClick={() => props.onEdit(invoice)}
      />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='8' message='No Invoices found!' />
  }

  return (
    <div class='table-responsive'>
      <table class='table table-hover'>
        <thead>
          <tr>
            <th>Invoice Id</th>
            <th>Invoice Date</th>
            <th>Receipt</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th>Type</th>
            <th class='text-right'>Amount (INR)</th>
            <th class='text-right'>Status</th>
            <th class='text-right'>Actions</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
