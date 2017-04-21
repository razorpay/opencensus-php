import TableBody from '../TableBody';

const ItemsListItem = props => {
  let { item, canHighlightRow } = props;
  return (
    <tr class={canHighlightRow ? 'luminate' : ''}>
      <td>{item.name}</td>
      <td>{item.description}</td>
      <td class="text-right">{item.amountInINR}</td>
      <td class="row-action">
        <div class="btn-group">
          <button class="btn btn-xs btn-default" onClick={props.onEdit}>
            <i class="fa fa-edit" />
            <span>edit</span>
          </button>
          <button class="btn btn-xs btn-default" onClick={props.onDelete}>
            <i class="fa fa-trash text-danger" />
            <span>delete</span>
          </button>
        </div>
      </td>
    </tr>
  );
};

const ItemsList = props => {
  let { items, isLoading, onEdit, onDelete, highlightRow } = props;

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Description</th>
            <th class="text-right">Amount (INR)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={4}
          rows={items}
          emptyTableMsg="No Items found!"
        >
          {items.map(item => (
            <ItemsListItem
              key={item.id}
              item={item}
              canHighlightRow={highlightRow(item)}
              onEdit={() => onEdit(item)}
              onDelete={() => onDelete(item)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

ItemsList.defaultProps = {
  highlightRow: () => {},
};

export default ItemsList;
