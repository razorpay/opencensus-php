import Time from 'common/ui/Time';
import { NavLink } from 'react-router-dom';
import TableBody from 'common/ui/TableBody';
import Amount from 'common/ui/Amount';
import CopyLink from 'merchant/components/CopyLink';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import { getCustomerDisplayName, truncatedString } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';

const copyText = (url) => {
  navigator?.clipboard.writeText(url);
};

const shareURL = (url, title) => {
  if (navigator.share) {
    navigator
      .share({
        title,
        url,
      })
      .catch(console.error);
  } else {
    // fallback
    copyText(url);
  }
};

const InvoiceListItem = (props) => {
  const { invoice, onCopy, label } = props;
  const customer = invoice.customer_details;
  const paymentLinkMobileTableListView = (
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
        <tr class="mobile-text">{customer?.customer_contact}</tr>
        <tr class="mobile-text">{truncatedString(customer?.customer_email)}</tr>
        {invoice.short_url && (
          <View
            className="link-container"
            data-tip="Copied"
            onClick={() => shareURL(invoice.short_url, invoice.id)}
          >
            <Flex alignItems="center">
              <Text size="small">
                <div className="share-payment-link">
                  <Icon name="share" size="small" />
                </div>
                Share payment link
              </Text>
            </Flex>
          </View>
        )}
      </td>
      <td>
        <Amount value={invoice.amount} currency={invoice.currency} />
      </td>
      <td>
        <InvoiceStatusLabel status={invoice.status ? invoice.status.toLowerCase() : null} />
      </td>
    </EntityItemRow>
  );

  return isMobileDevice() && label === 'Payment Link' ? (
    paymentLinkMobileTableListView
  ) : (
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
      <td>
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

const desktopListViewHeaders = (desktopListViewChildren, label, isPaymentlinksV2Enabled) => {
  return desktopListViewChildren ? (
    desktopListViewChildren
  ) : (
    <tr>
      <th>{label} Id</th>
      <th>Created Date</th>
      <th>Amount</th>
      <th>{isPaymentlinksV2Enabled ? 'Reference Id' : 'Receipt No.'}</th>
      <th>Customer</th>
      <th>Payment Link</th>
      <th>Status</th>
    </tr>
  );
};

const mobileListViewHeaders = (mobileListViewChildren) =>
  mobileListViewChildren ? (
    mobileListViewChildren
  ) : (
    <tr>
      <th>Payment Link Id</th>
      <th>Amount</th>
      <th>Status</th>
    </tr>
  );

export default (props) => {
  const {
    type,
    invoices,
    isLoading,
    onCopy = () => {},
    EmptyList,
    isPaymentlinksV2Enabled,
    mobileListViewChildren,
    desktopListViewChildren,
  } = props;
  const isPaymentLinksType = type === 'link';
  const label = isPaymentLinksType ? 'Payment Link' : 'Invoice';
  const listStyle = label === 'Payment Link' ? 'Payment-link-list' : 'Invoice-list';
  return (
    <div class={`${listStyle} table-responsive`}>
      <table class="table table-hover">
        <thead>
          {isMobileDevice() && isPaymentLinksType
            ? mobileListViewHeaders(mobileListViewChildren)
            : desktopListViewHeaders(desktopListViewChildren, label, isPaymentlinksV2Enabled)}
        </thead>
        <TableBody isLoading={isLoading} colSpan={8} rows={invoices} emptyTableRow={EmptyList}>
          {invoices.map((invoice) => (
            <InvoiceListItem
              label={label}
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
