import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'

const ItemsListItem = (props) => {
  let { item } = props
  return (
    <tr>
      <td>{item.name}</td>
      <td>{item.description}</td>
      <td class='text-right'>{item.rate}</td>
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

export default ({ items, isLoading, onEdit, onDelete }) => {
  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='4' />
  } else if (items.length) {
    tableRowComponent = items.map((item) =>
      <ItemsListItem
        key={item.id}
        item={item}
        onEdit={() => onEdit(item)}
        onDelete={() => onDelete(item)}
      />
    )
  } else {
    tableRowComponent = <EmptyTableRow colSpan='4' message='No Items found!' />
  }

  return (
    <div class='table-responsive'>
      <table class='table table-hover'>
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Description</th>
            <th class='text-right'>Amount (INR)</th>
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
