import { NavLink } from 'react-router-dom';
import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import CopyLink from 'merchant/components/CopyLink';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { getCustomerDisplayName } from 'common/utils/rzp-utils';

const InvoiceListItem = (props) => {
  const { invoice, onCopy } = props;
  const customer = invoice.customer_details;

  return (
    <EntityItemRow id={invoice.id}>
      <td>
        <NavLink
          to={
            ['link', 'ecod'].indexOf(invoice.type) !== -1
              ? `/paymentlinks/${invoice.id}`
              : `/invoices/${invoice.id}`
          }
        >
          <code>{invoice.id}</code>
        </NavLink>
      </td>
      <td>
        <Time value={invoice.date || invoice.created_at} />
      </td>
      <td class="text-right">
        <Amount value={invoice.amount} currency={invoice.currency} />
      </td>
      <td>{invoice.receipt}</td>
      <td>
        {getCustomerDisplayName({
          name: customer?.customer_name,
          contact: customer?.customer_contact,
          email: customer?.customer_email,
        })}
      </td>
      <td>
        {invoice.short_url && (
          <CopyLink
            onCopy={(text) => {
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
        <InvoiceStatusLabel status={invoice.status ? invoice.status.toLowerCase() : null} />
      </td>
    </EntityItemRow>
  );
};

export default (props) => {
  const {
    type,
    invoices,
    isLoading,
    onCopy = () => {},
    EmptyList,
    isPaymentlinksV2Enabled,
  } = props;
  const isPaymentLinksType = type === 'link';
  const label = isPaymentLinksType ? 'Payment Link' : 'Invoice';

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{label} Id</th>
            <th>Created Date</th>
            <th class="text-right">Amount</th>
            <th>{isPaymentlinksV2Enabled ? 'Reference Id' : 'Receipt No.'}</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th>Status</th>
          </tr>
        </thead>
        <TableBody isLoading={isLoading} colSpan={8} rows={invoices} emptyTableRow={EmptyList}>
          {invoices.map((invoice) => (
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
