import { NavLink } from 'react-router-dom';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { getCustomerDisplayName } from 'rzp/utils/rzp-utils';

const InvoiceListItem = props => {
  let { invoice, onCopy } = props;
  let customer = invoice.customer_details;

  return (
    <EntityItemRow id={invoice.id}>
      <td>
        {do {
          if (['link', 'ecod'].indexOf(invoice.type) !== -1) {
            <NavLink to={`/paymentlinks/${invoice.id}`}>
              <code>{invoice.id}</code>
            </NavLink>;
          } else {
            <NavLink to={`/invoices/${invoice.id}`}>
              <code>{invoice.id}</code>
            </NavLink>;
          }
        }}
      </td>
      <td>
        <Time value={invoice.date} />
      </td>
      <td class="text-right">
        <Amount value={invoice.amount} currency={invoice.currency} />
      </td>
      <td>{invoice.receipt}</td>
      <td>
        {getCustomerDisplayName({
          name: customer.customer_name,
          contact: customer.customer_contact,
          email: customer.customer_email,
        })}
      </td>
      <td>
        {invoice.short_url && (
          <CopyLink
            onCopy={text => {
              onCopy({
                invoiceId: invoice.id,
                text,
              });
            }}
            url={invoice.short_url}
          />
        )}
      </td>
      <td>
        <InvoiceStatusLabel status={invoice.status} />
      </td>
    </EntityItemRow>
  );
};

export default props => {
  let { type, invoices, isLoading, onCopy = () => {} } = props;
  const isPaymentLinksType = type === 'link';
  let label = isPaymentLinksType ? 'Payment Link' : 'Invoice';

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{label} Id</th>
            <th>Created Date</th>
            <th class="text-right">Amount</th>
            <th>Receipt No.</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th>Status</th>
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
              onDeleteClick={() => props.onDelete(invoice)}
              onCopy={onCopy}
              type={type}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
