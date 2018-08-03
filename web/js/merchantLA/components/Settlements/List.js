import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'rzp/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const SettlementsListItem = ({ settlement, handleBreakupClick }) => {
  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <Link to={`/settlements/${settlement.id}`}>
          <code>{settlement.id}</code>
        </Link>
      </td>
      <td class="text-right">
        <Amount value={settlement.amount} />
      </td>
      <td>
        <Time value={settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td>
        <SettlementStatusLabel status={settlement.status} />
      </td>
      <td>
        <button class="btn btn-xs btn-primary" onClick={handleBreakupClick}>
          Breakup
        </button>
      </td>
    </EntityItemRow>
  );
};

export default props => {
  let { settlements, isLoading, showBreakup } = props;

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Settlement Id</th>
            <th class="text-right">Amount</th>
            <th class="text-right">Fees</th>
            <th class="text-right">Tax</th>
            <th>Created At</th>
            <th>Status</th>
            <th />
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={7}
          rows={settlements}
          emptyTableMsg="No Settlements found!"
        >
          {settlements.map(settlement => (
            <SettlementsListItem
              key={settlement.id}
              settlement={settlement}
              handleBreakupClick={() => showBreakup(settlement)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
