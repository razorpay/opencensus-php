import { NavLink } from 'react-router-dom';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

const InvoiceListItem = props => {
  let { invoice, canHighlight, onEditClick } = props;
  return (
    <tr class={canHighlight ? 'luminate' : ''}>
      <td>
        {invoice.type === 'link'
          ? <NavLink to={`/paymentlinks/${invoice.id}`}>
              <code>{invoice.id}</code>
            </NavLink>
          : <NavLink to={`/invoices/${invoice.id}`}>
              <code>{invoice.id}</code>
            </NavLink>}
      </td>
      <td>
        <Time value={invoice.date} />
      </td>
      <td>{invoice.receipt}</td>
      <td>
        {invoice.customer_details.customer_contact ||
          invoice.customer_details.customer_email ||
          invoice.customer_details.customer_name}
      </td>
      <td>{invoice.short_url}</td>
      <td class="text-right">
        <Amount value={invoice.amount} />
      </td>
      <td class="text-right">
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
    </tr>
  );
};

export default props => {
  let { type, invoices, isLoading, highlightRow = () => {} } = props;
  let label = type === 'link' ? 'Payment Link' : 'Invoice';

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{label} Id</th>
            <th>{label} Date</th>
            <th>Receipt No.</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th class="text-right">Amount (INR)</th>
            <th class="text-right">Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={8}
          rows={invoices}
          emptyTableMsg="No data found!"
        >
          {invoices.map(invoice => (
            <InvoiceListItem
              key={invoice.id}
              invoice={invoice}
              canHighlight={highlightRow(invoice)}
              onEditClick={() => props.onEdit(invoice)}
              onDeleteClick={() => props.onDelete(invoice)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
