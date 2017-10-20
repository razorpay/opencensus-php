import { NavLink } from 'react-router-dom';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { getCustomerDisplayName } from 'rzp/utils/rzp-utils';

const InvoiceListItem = props => {
  let { invoice, onEditClick } = props;
  let customer = invoice.customer_details;

  return (
    <EntityItemRow id={invoice.id}>
      <td>
        {
          do {
            if (['link', 'ecod'].indexOf(invoice.type) !== -1) {
              <NavLink to={`/paymentlinks/${invoice.id}`}>
                <code>
                  {invoice.id}
                </code>
              </NavLink>;
            } else {
              <NavLink to={`/invoices/${invoice.id}`}>
                <code>
                  {invoice.id}
                </code>
              </NavLink>;
            }
          }
        }
      </td>
      <td>
        <Time value={invoice.date} />
      </td>
      <td class="text-right">
        <Amount value={invoice.amount} />
      </td>
      <td>
        {invoice.receipt}
      </td>
      <td>
        {getCustomerDisplayName({
          name: customer.customer_name,
          contact: customer.customer_contact,
          email: customer.customer_email,
        })}
      </td>
      <td>
        {invoice.short_url && <CopyLink url={invoice.short_url} />}
      </td>
      <td>
        <InvoiceStatusLabel status={invoice.status} />
      </td>
      <td>
        <div class="row-action">
          <div class="btn-group">
            <button
              data-tip={
                !invoice.isEditable ? 'Paid invoice cannot be edited' : null
              }
              class="btn btn-xs btn-default"
              disabled={!invoice.isEditable}
              onClick={props.onEditClick}
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
  let { type, invoices, isLoading } = props;
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
          rows={invoices}
          emptyTableMsg="No data found!"
        >
          {invoices.map(invoice =>
            <InvoiceListItem
              key={invoice.id}
              invoice={invoice}
              onEditClick={() => props.onEdit(invoice)}
              onDeleteClick={() => props.onDelete(invoice)}
            />
          )}
        </TableBody>
      </table>
    </div>
  );
};
