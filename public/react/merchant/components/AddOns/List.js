import { NavLink } from 'react-router-dom';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { getCustomerDisplayName } from 'rzp/utils/rzp-utils';

const AddOnsListItem = props => {
  let { addon, onAction } = props;
  let customer = addon.customer_details;

  return (
    <EntityItemRow id={addon.id}>
      <td>
        <code>
          {addon.id}
        </code>
      </td>
      <td>
        <Time value={addon.date} />
      </td>
      <td class="text-right">
        <Amount value={addon.amount} />
      </td>
      <td>
        {addon.receipt}
      </td>
      <td>
        {getCustomerDisplayName({
          name: customer.customer_name,
          contact: customer.customer_contact,
          email: customer.customer_email,
        })}
      </td>
      <td>
        {addon.short_url && <CopyLink url={addon.short_url} />}
      </td>
      <td>
        <InvoiceStatusLabel status={addon.status} />
      </td>
      <td>
        <div class="row-action">
          <div class="btn-group">
            <button
              data-tip={
                !addon.isEditable ? 'Paid invoice cannot be edited' : null
              }
              class="btn btn-xs btn-default"
              disabled={!addon.isEditable}
              onClick={props.onAction}
            >
              <i class="icon icon-edit" />
              <span>edit</span>
            </button>
          </div>
        </div>
      </td>
    </EntityItemRow>
  );
};

export default props => {
  let { type, addons, isLoading } = props;
  let label = type === 'link' ? 'Payment Link' : 'Invoice';

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>
              {label} Id
            </th>
            <th>
              {label} Date
            </th>
            <th class="text-right">Amount</th>
            <th>Receipt No.</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th>Status</th>
            <th>Actions</th>
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
              addons={addon}
              onEditClick={() => props.onEdit(addon)}
              onDeleteClick={() => props.onDelete(addon)}
            />
          )}
        </TableBody>
      </table>
    </div>
  );
};
