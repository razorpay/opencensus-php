import { titleCase } from 'rzp/utils/rzp-utils';
import Amount from 'rzp/ui/Amount';
import TableBody from 'rzp/ui/TableBody';

const Breakup = ({ breakup }) => {
  return (
    <tr>
      <td>{titleCase(breakup.component)}</td>
      <td>
        <Amount value={breakup.amount} />
      </td>
      <td>{breakup.count}</td>
      <td>{titleCase(breakup.type)}</td>
    </tr>
  );
};

export default ({ items, loading }) => {
  return (
    <div class="table-reponsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Component</th>
            <th>Amount</th>
            <th>Count</th>
            <th>Type</th>
          </tr>
        </thead>
        <TableBody colSpan={4} isLoading={loading} rows={items}>
          {items.map((breakup, index) => (
            <Breakup key={`breakup_${index}`} breakup={breakup} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
