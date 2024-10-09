import React, { useState, useEffect } from 'react';

import {
  Table,
  type TableData,
  Box,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  TablePagination,
  Text,
  Tooltip,
  InfoIcon,
  IconButton,
} from '@razorpay/blade/components';

import { ShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/types';

const TitleWithTooltip = ({ title, tooltip }: { title: string; tooltip: string }): JSX.Element => (
  <Box display="flex" alignItems="center">
    <Text weight="semibold" marginRight="spacing.3">
      {title}
    </Text>
    <Tooltip content={tooltip}>
      <IconButton accessibilityLabel="info" icon={InfoIcon} onClick={() => ''} />
    </Tooltip>
  </Box>
);

export const CODTable = ({ shippingMethods, isLoading, columns }) => {
  const [data, setData] = useState<TableData<ShippingMethod>>({ nodes: [] });

  useEffect(() => {
    setData({ nodes: shippingMethods });
  }, [shippingMethods, isLoading]);

  return (
    <Box backgroundColor="surface.background.gray.intense" overflow="auto" marginTop="spacing.5">
      <Table
        data={data}
        isLoading={isLoading}
        gridTemplateColumns={`1.5fr repeat(2,0.5fr) repeat(${columns?.length - 3},1fr)`}
        pagination={
          <TablePagination defaultPageSize={10} showPageNumberSelector showPageSizePicker />
        }
        isHeaderSticky
        isFirstColumnSticky
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {columns.map((column) => {
                  return (
                    <TableHeaderCell key={column.title}>
                      <TitleWithTooltip title={column?.title} tooltip={column?.tooltip} />
                    </TableHeaderCell>
                  );
                })}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((tableItem, rowIndex) => (
                <TableRow key={rowIndex} item={tableItem}>
                  {columns.map((column, colIndex) => {
                    return (
                      <TableCell key={`${rowIndex}${colIndex}`}>
                        {column.value(tableItem)}
                      </TableCell>
                    );
                  })}
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
    </Box>
  );
};
