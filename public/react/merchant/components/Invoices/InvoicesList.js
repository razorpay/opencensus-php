import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'

const InvoiceListItem = ({ invoice }) => {
  return (
    <tr>
      <td>{invoice.invoice_date}</td>
      <td>{invoice.inv_number}</td>
      <td>{invoice.status}</td>
      <td>{invoice.due_date}</td>
      <td>{invoice.customer.name}</td>
      <td>{invoice.status}</td>
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
    <div className='table-responsive'>
      <table className='table'>
        <thead>
          <tr>
            <th>Invoice Date</th>
            <th>Invoice #</th>
            <th>Status</th>
            <th>Due Date </th>
            <th>Customer Name</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
