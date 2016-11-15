import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'

const CustomersListItem = (props) => {
  let { customer } = props
  return (
    <tr>
      <td>{customer.name}</td>
      <td>{customer.email}</td>
      <td>{customer.contact}</td>
      <td class='row-action'>
        <div class='btn-group'>
          <button
            class='btn btn-xs btn-default'
            onClick={props.onEdit}
          >
            <i class='fa fa-edit'></i>
          </button>
          <button
            class='btn btn-xs btn-danger'
            onClick={props.onDelete}
          >
            <i class='fa fa-trash'></i>
          </button>
        </div>
      </td>
    </tr>
  )
}

export default ({ customers, isLoading, onEdit, onDelete }) => {
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='4' />
  } else if (customers.length) {
    tableRowComponent = customers.map((customer) =>
      <CustomersListItem
        key={customer.id}
        customer={customer}
        onEdit={() => onEdit(customer)}
        onDelete={() => onDelete(customer)}
      />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='4' message='No Customers found!' />
  }

  return (
    <div className='table-responsive'>
      <table className='table table-hover'>
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}
