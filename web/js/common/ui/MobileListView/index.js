import React from 'react';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
} from '@razorpay/blade/components';

import { classList, getTableTemplateColumnsValue } from 'common/utils/rzp-utils';

const MobileListView = (props) => {
  const {
    items,
    headers,
    TableListItem,
    EmptyComponent,
    onDelete,
    isLoading,
    onClick,
    tableWrapperClass,
    onShareLinkSuccess,
    gridTemplateColumns,
  } = props;

  const _gridTemplateColumns =
    gridTemplateColumns ||
    getTableTemplateColumnsValue(...headers.map((col) => col.width ?? '1fr'));

  return (
    <div className={classList(tableWrapperClass, 'table-responsive')}>
      <Table
        data={{ nodes: items }}
        isLoading={isLoading}
        gridTemplateColumns={_gridTemplateColumns}
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {headers &&
                  headers.map((header, index) => {
                    return <TableHeaderCell key={index}>{header}</TableHeaderCell>;
                  })}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((item) => (
                <TableListItem
                  key={item.id}
                  item={item}
                  onClick={() => onClick && onClick(item)}
                  onDeleteClick={() => onDelete && onDelete(item)}
                  onShareLinkSuccess={onShareLinkSuccess}
                />
              ))}
            </TableBody>
          </>
        )}
      </Table>
      {!isLoading && items.length === 0 && EmptyComponent ? <EmptyComponent /> : null}
    </div>
  );
};

export default MobileListView;
