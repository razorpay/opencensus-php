import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from '../TableBody';
import TransactionNavLink from 'merchant/components/TransactionNavLink';

const SettlementsListItem = ({
  settlement,
  handleBreakupClick,
  onSettlementClick,
}) => {
  return (
    <tr>
      <td>
        <TransactionNavLink
          to={`/app/settlements/${settlement.id}`}
          onClick={() => onSettlementClick(settlement)}
        >
          {settlement.id}
        </TransactionNavLink>
      </td>
      <td class="text-right">
        <Amount value={settlement.amount} />
      </td>
      <td>
        <Time value={settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td class="text-right">
        <Amount value={settlement.fees} />
      </td>
      <td class="text-right">
        <Amount value={settlement.service_tax} />
      </td>
      <td>
        <SettlementStatusLabel status={settlement.status} />
      </td>
      <td>
        <button class="btn btn-xs btn-primary" onClick={handleBreakupClick}>
          Breakup
        </button>
      </td>
    </tr>
  );
};

export default props => {
  let { settlements, isLoading, showBreakup, onSettlementClick } = props;

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Settlement Id</th>
            <th class="text-right">Amount</th>
            <th>Created At</th>
            <th class="text-right">Fees</th>
            <th class="text-right">Service Tax</th>
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
              onSettlementClick={onSettlementClick}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
