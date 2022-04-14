import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';

const SettlementsListItem = ({ settlement, handleBreakupClick, user, terminalProviders }) => {
  const handleTracking = () => {
    analyticsTrack({
      objectName: 'settlement id',
      actionName: 'clicked',
      screen: 'settlements',
      properties: {
        ...settlement.analyticsPayload(),
        location: 'settlements',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };
  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <Link onClick={handleTracking} to={`/settlements/${settlement.id}`}>
          <code>{settlement.id}</code>
        </Link>
      </td>
      {user?.isSingleReconEnabled && user?.isOptimizerEnabled && (
        <td>
          <PaymentOptimizerProvider
            terminal_id={settlement?.optimizer_provider}
            settled_by={settlement?.settled_by}
            terminalProviders={terminalProviders}
            hideExternalLink={true}
          />
        </td>
      )}
      <td class="text-right">
        <Amount value={settlement.amount} currency="INR" />
      </td>
      <td class="text-right">
        <Amount value={settlement.fees} currency="INR" />
      </td>
      <td class="text-right">
        <Amount value={settlement.tax} currency="INR" />
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

export default (props) => {
  const { settlements, isLoading, showBreakup, user, terminalProviders } = props;

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Settlement Id</th>
            {user?.isSingleReconEnabled && user?.isOptimizerEnabled && <th>Payment Provider</th>}
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
          {settlements.map((settlement) => (
            <SettlementsListItem
              key={settlement.id}
              settlement={settlement}
              handleBreakupClick={() => showBreakup(settlement)}
              user={user}
              terminalProviders={terminalProviders}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
