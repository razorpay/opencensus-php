import React from 'react';
import {
  Box,
  Heading,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';

const ColumnPreviewTable = ({ title, tableData, columnConfig }) => {
  return tableData.length ? (
    <Box display="flex" flexDirection="column" gap="spacing.2">
      <Box display="flex" justifyContent="space-between" alignItems="center">
        <Heading>{title}</Heading>
      </Box>
      <Table
        data={{ nodes: tableData }}
        showBorderedCells={true}
        display="grid"
        gridTemplateColumns={`repeat(${columnConfig.length}, 1fr)`}
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {columnConfig.map((column) => (
                  <TableHeaderCell key={column.merchant_source_id}>
                    {column.merchant_source_name}
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((tableItem, rowIndex) => (
                <TableRow key={rowIndex} item={tableItem}>
                  {columnConfig.map((column) => (
                    <TableCell key={`${column.merchant_source_id}_${rowIndex}`}>
                      {tableItem[column.merchant_source_id]?.name || ''}
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
    </Box>
  ) : null;
};

export default ColumnPreviewTable;