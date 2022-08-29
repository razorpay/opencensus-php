import './RouteSettlementsList.styl';
import React from 'react';
import PropTypes from 'prop-types';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const SettlementsListItem = ({ settlement }) => {
  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <i className="i i-early-settlement settle-icon mr-16" />
        <code>{settlement.id}</code>
      </td>
      <td className="text-right">
        <Amount value={settlement.amount} currency="INR" />
      </td>
      <td className="text-right">
        <Amount
          className="table-settlement-amount-settled"
          value={settlement.total_amount_settled}
          currency="INR"
        />
      </td>
      <td className="text-right">
        <Amount value={settlement.total_amount_pending} currency="INR" />
      </td>
      <td className="text-center">
        <Time value={Number(settlement.created_at)} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td className="text-center">
        <SettlementStatusLabel status={settlement.status} />
      </td>
    </EntityItemRow>
  );
};

SettlementsListItem.propTypes = {
  settlement: PropTypes.object,
};

const RouteSettlementsList = ({ settlements, isLoading }) => {
  return (
    <div className="table-responsive route-settlements-table">
      <table className="table table-hover">
        <thead>
          <tr>
            <th className="settlement-id">Settlement Id</th>
            <th width="18%" className="text-right">
              Requested Amount
            </th>
            <th width="15%" className="text-right">
              Settled Amount
            </th>
            <th width="15%" className="text-right">
              Pending Amount
            </th>
            <th width="25%" className="text-center">
              Created At
            </th>
            <th width="15%" className="text-center">
              Status
            </th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={5}
          rows={settlements}
          emptyTableMsg="No Settlements found!"
        >
          {settlements.map((settlement) => (
            <SettlementsListItem key={settlement.id} settlement={settlement} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

RouteSettlementsList.propTypes = {
  settlements: PropTypes.array,
  isLoading: PropTypes.bool,
};

export default RouteSettlementsList;
