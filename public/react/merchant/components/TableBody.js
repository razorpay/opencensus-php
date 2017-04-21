import TableLoader from 'rzp/ui/TableLoader';
import EmptyTableRow from 'rzp/ui/EmptyTableRow';

export default props => {
  let tableRowComponent;
  let {
    isLoading,
    emptyTableRow,
    emptyTableMsg,
    colSpan,
    rows,
    className,
    children,
  } = props;

  if (isLoading) {
    tableRowComponent = <TableLoader colSpan={colSpan} />;
  } else if (!rows.length) {
    tableRowComponent =
      emptyTableRow ||
      <EmptyTableRow colSpan={colSpan} message={emptyTableMsg} />;
  }

  return (
    <tbody>
      {tableRowComponent || children}
    </tbody>
  );
};
