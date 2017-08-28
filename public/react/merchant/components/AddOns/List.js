import { NavLink } from 'react-router-dom';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import { AddOnStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { getCustomerDisplayName } from 'rzp/utils/rzp-utils';

const AddOnsListItem = props => {
  let { addon, onAction } = props;

  return (
    <EntityItemRow id={addon.id}>
      <td>
        <NavLink to={`/addons/${addon.id}`}>
          {addon.id}
        </NavLink>
      </td>
      <td>
        {addon.name}
      </td>
      <td class="text-right">
        <Amount value={addon.amount} />
      </td>
      <td>
        <Time value={addon.date} format="MMM DD  YYYY, hh:mm a" />
      </td>
      <td class="text-center">
        <button
          class="btn btn-xs btn-transparent"
          disabled={!addon.isEditable}
          onClick={() => props.onAction('edit', addon.id)}
        >
          <i class="icon icon-edit" />
        </button>
      </td>
      <td class="text-center">
        <button
          class="btn btn-xs btn-transparent"
          onClick={() => props.onAction('delete', addon.id)}
        >
          <i class="icon icon-close text-danger" />
        </button>
      </td>
    </EntityItemRow>
  );
};

export default props => {
  let { type, addons, isLoading } = props;
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Add-on Id</th>
            <th>Name</th>
            <th class="text-right">Amount/Unit (INR)</th>
            <th>Created on</th>
            <th class="text-center">Edit Details</th>
            <th class="text-center">Remove</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={8}
          rows={addons}
          emptyTableMsg="No data found!"
        >
          {addons.map(addon =>
            <AddOnsListItem
              key={addon.id}
              addon={addon}
              onEditClick={() => props.onEdit(addon)}
              onDeleteClick={() => props.onDelete(addon)}
            />
          )}
        </TableBody>
      </table>
    </div>
  );
};
