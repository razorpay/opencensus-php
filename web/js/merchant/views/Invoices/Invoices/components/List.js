import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Box,
  Text,
  ShareIcon,
} from '@razorpay/blade/components';
import View from '@razorpay/blade-old/src/atoms/View';

import MobileListView from 'common/ui/MobileListView';
import { amount, createdAt } from 'common/ui/item/pair';
import { getTableTemplateColumnsValue } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { shareURL } from 'merchant/views/Invoices/Invoices/helpers';
import {
  customer,
  invoiceId,
  invoiveStatus,
  paymentLink,
  paymentLinkId,
  receiptNumber,
  referenceId,
} from 'merchant/views/Invoices/Invoices/item';

const PaymentLinkMobileTableListView = (items) => {
  const { item, onShareLinkSuccess = () => {} } = items;

  return (
    <TableRow item={item} testID={`entity-item-row-${item.id}`}>
      <TableCell>
        <Box display="flex" flexDirection="column" width="100%">
          {paymentLinkId.value(item)}

          {item?.customer_details && customer.value(item)}

          {item?.short_url && (
            <View
              className="link-container"
              data-tip="Copied"
              onClick={() => {
                onShareLinkSuccess();
                shareURL(item.short_url, item?.id);
              }}
            >
              <Box display="flex" alignItems="center" padding="4px" gap="4px">
                <ShareIcon size="small" />
                <Text size="small">Share payment link</Text>
              </Box>
            </View>
          )}
        </Box>
      </TableCell>

      <TableCell>{amount.value(item)}</TableCell>
      <TableCell>{invoiveStatus.value(item)}</TableCell>
    </TableRow>
  );
};

const InvoiceListItem = (props) => {
  const { invoice, columns } = props;

  return (
    <TableRow item={invoice} testID={`entity-item-row-${invoice.id}`}>
      {columns.map(({ title, value }) => (
        <TableCell key={title}>{value(invoice)}</TableCell>
      ))}
    </TableRow>
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
  const label = isPaymentLinksType ? paymentLinkId : invoiceId;
  const listStyle = isPaymentLinksType ? 'Payment-link-list' : 'Invoice-list';
  const receipt = isPaymentlinksV2Enabled ? referenceId : receiptNumber;

  const columns = [label, createdAt, amount, receipt, customer, paymentLink(onCopy), invoiveStatus];

  return isMobileDevice() && isPaymentLinksType ? (
    <MobileListView
      isLoading={isLoading}
      headers={['Payment Link Id', 'Amount', 'Status']}
      TableListItem={PaymentLinkMobileTableListView}
      tableWrapperClass={listStyle}
      items={invoices}
      EmptyComponent={EmptyList}
      {...props}
    />
  ) : (
    <>
      <Table
        data={{ nodes: invoices }}
        isLoading={isLoading}
        gridTemplateColumns={getTableTemplateColumnsValue(
          ...columns.map((col) => col.width ?? '1fr'),
        )}
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {columns.map(({ title }) => (
                  <TableHeaderCell key={title}>{title}</TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((invoice) => (
                <InvoiceListItem key={invoice.id} invoice={invoice} columns={columns} />
              ))}
            </TableBody>
          </>
        )}
      </Table>
      {!isLoading && invoices.length === 0 && EmptyList ? <EmptyList /> : null}
    </>
  );
};
