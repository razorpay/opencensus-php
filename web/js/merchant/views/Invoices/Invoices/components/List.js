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
import MobileListView from 'common/ui/MobileListView';
import copyToClipboard from 'common/utils/copyToClipboard';
import { isMobileDevice } from 'merchant/components/Home/data';

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
    copyToClipboard(url);
  }
};

const commonListItem = (item) => {
  return (
    <>
      <td>
        <Amount value={item?.amount} currency={item?.currency} />
      </td>
      <td>
        <InvoiceStatusLabel status={item?.status ? item.status.toLowerCase() : null} />
      </td>
    </>
  );
};

const PaymentLinkMobileTableListView = (items) => {
  const { item } = items;

  return (
    <EntityItemRow id={item?.id}>
      <td>
        <NavLink to={`/paymentlinks/${item?.id}`}>
          <code>{item?.id}</code>
        </NavLink>
        <tr class="mobile-text">{item?.customer_details?.customer_contact}</tr>
        <tr class="mobile-text">{truncatedString(item?.customer_details?.customer_email)}</tr>
        {item?.short_url && (
          <View
            className="link-container"
            data-tip="Copied"
            onClick={() => shareURL(item.short_url, item?.id)}
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
      {commonListItem(item)}
    </EntityItemRow>
  );
};

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
      {commonListItem(invoice)}
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
  const listStyle = label === 'Payment Link' ? 'Payment-link-list' : 'Invoice-list';
  return isMobileDevice() && label === 'Payment Link' ? (
    <MobileListView
      headers={['Payment Link Id', 'Amount', 'Status']}
      TableListItem={PaymentLinkMobileTableListView}
      tableWrapperClass={listStyle}
      items={invoices}
      {...props}
    />
  ) : (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{label} Id</th>
            <th>Created Date</th>
            <th>Amount</th>
            <th>{isPaymentlinksV2Enabled ? 'Reference Id' : 'Receipt No.'}</th>
            <th>Customer</th>
            <th>Payment Link</th>
            <th>Status</th>
          </tr>
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
