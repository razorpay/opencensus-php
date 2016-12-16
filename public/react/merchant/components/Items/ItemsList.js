import TableLoader from 'rzp/ui/TableLoader'
import EmptyTableRow from 'rzp/ui/EmptyTableRow'

const ItemsListItem = (props) => {
  let { item, canHighlightRow } = props
  return (
    <tr class={canHighlightRow ? 'luminate' : ''}>
      <td>{item.name}</td>
      <td>{item.description}</td>
      <td class='text-right'>{item.amountInINR}</td>
      <td class='row-action'>
        <div class='btn-group'>
          <button
            class='btn btn-xs btn-default'
            onClick={props.onEdit}
          >
            <i class='fa fa-edit'></i>
            <span>edit</span>
          </button>
          <button
            class='btn btn-xs btn-default'
            onClick={props.onDelete}
          >
            <i class='fa fa-trash text-danger'></i>
            <span>delete</span>
          </button>
        </div>
      </td>
    </tr>
  )
}

const ItemsList = (props) => {
  let {
    items,
    isLoading,
    onEdit,
    onDelete,
    highlightRow
  } = props

  let tableRowComponent

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan='4' />
  } else if (items.length) {
    tableRowComponent = items.map((item) =>
      <ItemsListItem
        key={item.id}
        item={item}
        canHighlightRow={highlightRow(item)}
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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {tableRowComponent}
        </tbody>
      </table>
    </div>
  )
}

ItemsList.defaultProps = {
  highlightRow: () => {}
}

export default ItemsList
