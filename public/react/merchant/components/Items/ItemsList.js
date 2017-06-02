import TableBody from 'rzp/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const ItemsListItem = ({ item, onEdit, onDelete }) => {
  return (
    <EntityItemRow id={item.id}>
      <td><a onClick={onEdit}>{item.name}</a></td>
      <td>{item.description}</td>
      <td class="text-right">{item.amountInINR}</td>
      <td class="row-action">
        <div class="btn-group">
          <button class="btn btn-xs btn-default" onClick={onEdit}>
            <i class="icon icon-edit" />
            <span>edit</span>
          </button>
          <button class="btn btn-xs btn-default" onClick={onDelete}>
            <i class="icon icon-trash text-danger" />
            <span>delete</span>
          </button>
        </div>
      </td>
    </EntityItemRow>
  );
};

export default ({ items, isLoading, onEdit, onDelete }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Description</th>
            <th class="text-right">Amount</th>
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
              onEdit={() => onEdit(item)}
              onDelete={() => onDelete(item)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
