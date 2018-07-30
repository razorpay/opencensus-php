import { titleCase } from 'rzp/utils/rzp-utils';
import Amount from 'rzp/ui/Amount';
import TableBody from 'rzp/ui/TableBody';

/*
* Settlement api is not changed for LA. So, components come as Payment, Refund, Tax and Fee.
* We just want Transfers and Reversals as replacement of Payment and Refunds
* */

function _getOverwrittenKey(title) {
  switch (title) {
    case 'payment':
      return 'Transfer';
      break;
    case 'refund':
      return 'Reversal';
      break;
  }
}

const excludeKeys = ['tax', 'fee'];

const Breakup = ({ breakup }) => {
  return (
    <tr>
      <td>{titleCase(_getOverwrittenKey(breakup.component))}</td>
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
          {items.map(
            (breakup, index) =>
              excludeKeys.indexOf(breakup.component) !== -1 ? null : (
                <Breakup key={`breakup_${index}`} breakup={breakup} />
              )
          )}
        </TableBody>
      </table>
    </div>
  );
};
