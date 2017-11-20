import { NavLink } from 'react-router-dom';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import { AddOnStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { getCustomerDisplayName } from 'rzp/utils/rzp-utils';

const AddOnsListItem = props => {
  let { addon, onAction, luminateRowId } = props;

  return (
    <EntityItemRow id={addon.id} luminateRowId={luminateRowId}>
      <td>
        <NavLink to={`/addons/${addon.id}`}>{addon.id}</NavLink>
      </td>
      <td>{addon.item.name}</td>
      <td class="text-right">
        <Amount value={addon.item.amount} currency={addon.item.currency} />
      </td>
      <td>
        <Time value={addon.item.created_at} format="MMM DD  YYYY, hh:mm a" />
      </td>
      <td class="text-center">
        <button
          class="btn btn-xs btn-transparent"
          onClick={() => props.onDelete()}
        >
          <i class="icon icon-close text-danger" />
        </button>
      </td>
    </EntityItemRow>
  );
};

export default props => {
  let { addons, isLoading, luminateRowId } = props;
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Add-on Id</th>
            <th>Name</th>
            <th class="text-right">Amount/Unit (INR)</th>
            <th>Created on</th>
            <th class="text-center">Remove</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={5}
          rows={addons}
          emptyTableMsg="No data found!"
        >
          {addons.map(addon => (
            <AddOnsListItem
              key={addon.id}
              addon={addon}
              luminateRowId={luminateRowId}
              onDelete={() => props.onDelete(addon.id)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
