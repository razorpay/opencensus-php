import { titleCase } from 'common/utils/rzp-utils';
import TableBody from 'common/ui/TableBody';

const Row = (props) => {
  return (
    <tr>
      <td />
      <td>{props.rowData.date}</td>
      <td>{titleCase(props.rowData.description)}</td>
      <td />
    </tr>
  );
};

export default ({ items }) => {
  return (
    <div className="table-responsive holiday">
      <table className="table table-hover">
        <thead>
          <tr>
            <th />
            <th>Date</th>
            <th>Name</th>
            <th />
          </tr>
        </thead>
        <TableBody colSpan={2} isLoading={false} rows={items}>
          {items.map((item, index) => (
            <Row key={index} rowData={item} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
