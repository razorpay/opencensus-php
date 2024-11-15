import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';

import { getTableTemplateColumnsValue } from 'common/utils/rzp-utils';

export default ({
  rows,
  columns,
  showHeaders = true,
  loading,
  onCellClick,
  onRowClick,
  isDisabled,
  gridTemplateColumns,
}) => {
  const _gridTemplateColumns =
    gridTemplateColumns ||
    getTableTemplateColumnsValue(...columns.map((col) => col.width ?? '1fr'));

  return (
    <Table data={{ nodes: rows }} isLoading={loading} gridTemplateColumns={_gridTemplateColumns}>
      {(tableData) => (
        <>
          {showHeaders && (
            <TableHeader>
              <TableHeaderRow>
                {columns.map((column, index) => (
                  <TableHeaderCell key={index}>
                    {typeof column.title === 'function' ? column.title() : column.title}
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
          )}
          <TableBody>
            {tableData.map((item, index) => (
              <TableRow
                key={item.id ?? index}
                testID={`entity-item-row-${item.id}`}
                item={item}
                isDisabled={typeof isDisabled === 'function' ? isDisabled(item) : undefined}
                onClick={
                  typeof onRowClick === 'function'
                    ? () =>
                        onRowClick({
                          id: item.id,
                          rowData: {
                            paymentMethod: item?.method || '',
                            sourceChannel: item?.source_channel || '',
                          },
                        })
                    : undefined
                }
              >
                {columns.map((column, index) => (
                  <TableCell key={index}>
                    {typeof column.value === 'function'
                      ? column.value(item, onCellClick)
                      : column.value}
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </>
      )}
    </Table>
  );
};
