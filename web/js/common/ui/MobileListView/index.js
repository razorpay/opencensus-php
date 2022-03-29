import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import TableBody from 'common/ui/TableBody';

const MobileListView = (props) => {
  const {
    items,
    headers,
    TableListItem,
    EmptyComponent,
    onDelete,
    loading,
    onClick,
    tableWrapperClass,
  } = props;
  return (
    <div className={classList(tableWrapperClass, 'table-responsive')}>
      <table class="table table-hover">
        <thead>
          <tr>
            {headers &&
              headers.map((header, index) => {
                return <th key={index}>{header}</th>;
              })}
          </tr>
        </thead>
        <TableBody
          isLoading={loading}
          colSpan={8}
          rows={items}
          emptyTableRow={EmptyComponent}
          {...props}
        >
          {items &&
            items.map((item) => (
              <TableListItem
                key={item.id}
                item={item}
                onClick={() => onClick && onClick(item)}
                onDeleteClick={() => onDelete && onDelete(item)}
              />
            ))}
        </TableBody>
      </table>
    </div>
  );
};

export default MobileListView;
