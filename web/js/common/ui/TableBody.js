import React from 'react';
import TableLoader from 'common/ui/TableLoader';
import EmptyTableRow from 'common/ui/EmptyTableRow';

export default (props) => {
  let tableRowComponent;
  const { isLoading, emptyTableRow, emptyTableMsg, colSpan, rows, children, SpinnerComponent } =
    props;

  if (isLoading) {
    tableRowComponent = SpinnerComponent || <TableLoader colSpan={colSpan} />;
  } else if (!rows.length) {
    tableRowComponent = emptyTableRow || (
      <EmptyTableRow colSpan={colSpan} message={emptyTableMsg} />
    );
  }

  return (
    <tbody>
      {tableRowComponent
        ? typeof tableRowComponent === 'function'
          ? tableRowComponent(colSpan)
          : tableRowComponent
        : children}
    </tbody>
  );
};
