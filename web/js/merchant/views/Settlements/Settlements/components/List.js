import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const SettlementsListItem = ({
  settlement,
  handleBreakupClick,
  user,
  terminalProviders,
  initiatePage,
}) => {
  const screen = initiatePage?.split('.')[0] || 'Settlements';
  const page = initiatePage?.split('.')[1];
  const selfServeInitiateData = {
    selfServeAction: 'Settlement Details Fetched',
    props: {},
  };

  selfServeInitiateData.props.initiatePoint = 'settlements-table';
  if (screen) selfServeInitiateData.screen = screen;
  if (page) selfServeInitiateData.page = page;
  if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;

  const currency = user.merchant.currency;

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
    selfServeTrackInitiate(selfServeInitiateData);
  };

  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <Link
          onClick={handleTracking}
          to={`/settlements/${settlement.id}?init_point=settlements-table&init_page=${initiatePage}`}
        >
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
      <td className="text-right">
        <Amount value={settlement.amount} currency={currency} />
      </td>
      <td className="text-right">
        <Amount value={settlement.fees} currency={currency} />
      </td>
      <td className="text-right">
        <Amount value={settlement.tax} currency={currency} />
      </td>
      <td>
        <Time value={settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td>
        <SettlementStatusLabel status={settlement.status} />
      </td>
      <td>
        <button className="btn btn-xs btn-primary" onClick={handleBreakupClick}>
          Breakup
        </button>
      </td>
    </EntityItemRow>
  );
};

export default (props) => {
  const {
    settlements,
    isLoading,
    showBreakup,
    user,
    terminalProviders,
    selfServeActionsPage = 'Settlements.Settlements',
  } = props;

  return (
    <div className="table-responsive settlements-table">
      <table className="table table-hover">
        <thead>
          <tr>
            <th>Settlement Id</th>
            {user?.isSingleReconEnabled && user?.isOptimizerEnabled && <th>Payment Provider</th>}
            <th className="text-right">
              Amount
              {user?.isSingleReconEnabled && user?.isOptimizerEnabled && (
                <i className="i i-info-circle">
                  <PopoverComponent align="bottom" theme="dark" data-testid="total-amount-popover">
                    <PopoverBody>
                      <p>
                        The settlement amount represents the fund transfer amount that is initiated
                        from razorpay or optimizer payment provider to your respective settlement
                        bank account.
                      </p>
                      <p>
                        The fees and tax for optimizer non razorpay payment providers is the sum of
                        fees and tax for the transactions that make up that settlement.
                      </p>
                    </PopoverBody>
                  </PopoverComponent>
                </i>
              )}
            </th>
            <th className="text-right">Fees</th>
            <th className="text-right">Tax</th>
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
              initiatePage={selfServeActionsPage}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
