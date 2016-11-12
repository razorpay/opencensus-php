import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'

const PlansListItem = (props) => {
  let { plan } = props
  return (
    <tr>
      <td>{plan.name}</td>
      <td>{plan.amount}</td>
      <td>{plan.interval_count} {plan.interval}</td>
      <td>
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

export default ({ plans, isLoading, onEdit, onDelete }) => {
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='4' />
  } else if (plans.length) {
    tableRowComponent = plans.map((plan) =>
      <PlansListItem
        key={plan.id}
        plan={plan}
        onEdit={() => onEdit(plan)}
        onDelete={() => onDelete(plan)}
      />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='4' message='No Plans found!' />
  }

  return (
    <div className='table-responsive'>
      <table className='table'>
        <thead>
          <tr>
            <th>Plan Name</th>
            <th>Plant Amount</th>
            <th>Bill Every</th>
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
