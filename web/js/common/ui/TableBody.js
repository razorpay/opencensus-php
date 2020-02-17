import React from 'react';
import TableLoader from 'common/ui/TableLoader';
import EmptyTableRow from 'common/ui/EmptyTableRow';

export default props => {
  let tableRowComponent;
  const {
    isLoading,
    emptyTableRow,
    emptyTableMsg,
    colSpan,
    rows,
    className,
    children,
    EmptyComponent,
  } = props;

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan={colSpan} />;
  } else if (!rows.length) {
    tableRowComponent = emptyTableRow || (
      <EmptyTableRow colSpan={colSpan} message={emptyTableMsg} />
    );
  }

  return (
    <tbody>
      {tableRowComponent
        ? typeof tableRowComponent === 'function'
          ? tableRowComponent()
          : tableRowComponent
        : children}
    </tbody>
  );
};
