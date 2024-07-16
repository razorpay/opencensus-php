import React, { useEffect, useState } from 'react';
import {
  Amount,
  Box,
  Table,
  TableBody,
  TableCell,
  TableData,
  TableHeader,
  TableHeaderCell,
  TableHeaderRow,
  TableRow,
} from '@razorpay/blade/components';
import { CurrencyCodeType } from '@razorpay/i18nify-js';

import {
  getPaymentSplitAmongstItems,
  getStorefrontLineItems,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { showNotification } from 'merchant_common/reducers/notifications';

type Item = {
  id: string;
  name: string;
  amount: number;
  net_amount: number;
  unit_amount: number;
  currency: CurrencyCodeType;
  quantity: number;
};
interface IPaymentSplitItems {
  order_id: string;
}

function PaymentSplitItems({ order_id }: IPaymentSplitItems): React.ReactElement | null {
  const [isLoading, setIsLoading] = useState(false);
  const [splitLineItems, setSplitLineItems] = useState<TableData<Item>>({
    nodes: [],
  });

  useEffect(() => {
    async function fetchLineItems(order_id: string, isStorefront: boolean) {
      const lineItemsFetcher = isStorefront ? getStorefrontLineItems : getPaymentSplitAmongstItems;
      const lineItemsResponse = await lineItemsFetcher(order_id);
      if (
        lineItemsResponse &&
        lineItemsResponse.data &&
        Array.isArray(lineItemsResponse.data.items)
      ) {
        return lineItemsResponse.data.items as Item[];
      }
      return [];
    }

    setIsLoading(true);
    const isStorefront = location.hash === '#storefront';
    fetchLineItems(order_id, isStorefront)
      .then((line_items) => {
        setSplitLineItems({
          nodes: line_items,
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Unable to fetch order items. Please try again.',
        });
        setSplitLineItems({
          nodes: [],
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [order_id]);
  return splitLineItems.nodes.length > 0 ? (
    <Box display="flex" flex={1} overflowX="auto" flexDirection="row" alignItems="center">
      <Table isLoading={isLoading} data={splitLineItems} showStripedRows={true}>
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                <TableHeaderCell>Item Name</TableHeaderCell>
                <TableHeaderCell>Revenue</TableHeaderCell>
                <TableHeaderCell>Price</TableHeaderCell>
                <TableHeaderCell>Units Sold</TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((tableItem, index) => (
                <TableRow key={index} item={tableItem}>
                  <TableCell>{tableItem.name}</TableCell>
                  <TableCell>
                    <Amount value={tableItem.net_amount} currency={tableItem.currency} />
                  </TableCell>
                  <TableCell>
                    <Amount value={tableItem.amount} currency={tableItem.currency} />
                  </TableCell>
                  <TableCell>{tableItem.quantity || '--'}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
    </Box>
  ) : null;
}

export default PaymentSplitItems;
