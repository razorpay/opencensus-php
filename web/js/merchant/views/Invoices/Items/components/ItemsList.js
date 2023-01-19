import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const TableHead = ({ columns }) => {
  return (
    <thead>
      <tr>
        {columns.map((col) => (
          <th key={col} className={col === 'Amount' ? 'text-right' : ''}>
            {col}
          </th>
        ))}
      </tr>
    </thead>
  );
};

const ItemsListItem = ({ item, onEdit, onDelete, userActionAllowed }) => {
  return (
    <EntityItemRow id={item.id}>
      <td>
        {userActionAllowed ? (
          <a onClick={onEdit}>
            <code>{item.id}</code>
          </a>
        ) : (
          <code>{item.id}</code>
        )}
      </td>
      <td>{userActionAllowed ? <a onClick={onEdit}>{item.name}</a> : <span>{item.name}</span>}</td>
      <td>{item.description}</td>
      <td className="text-right">
        <Amount value={item.amount} currency={item.currency} />
      </td>
      {userActionAllowed && (
        <td className="row-action">
          <div className="btn-group">
            <button type="button" className="btn btn-xs btn-default" onClick={onDelete}>
              <i className="i i-delete text-danger" />
              <span>Delete</span>
            </button>
          </div>
        </td>
      )}
    </EntityItemRow>
  );
};

export default ({ items, isLoading, onEdit, onDelete, userActionAllowed }) => {
  const columns = ['Item ID', 'Item Name', 'Description', 'Amount'].concat(
    userActionAllowed ? ['Actions'] : [],
  );

  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <TableHead columns={columns} />
        <TableBody isLoading={isLoading} colSpan={5} rows={items} emptyTableMsg="No Items found!">
          {items.map((item) => (
            <ItemsListItem
              key={item.id}
              item={item}
              columns={columns}
              userActionAllowed={userActionAllowed}
              onEdit={() => onEdit(item)}
              onDelete={() => onDelete(item)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
