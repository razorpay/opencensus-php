import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const SettlementsListItem = ({ settlement, user }) => {
  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <i className="i i-early-settlement settle-icon mr-16" />
        <code>{settlement.id}</code>
      </td>
      <td className="text-right">
        <Amount value={settlement.amount} currency="INR" />
      </td>
      {user.showOnDemandDeduction ? (
        <td className="text-right">
          <Amount value={settlement.fees} currency="INR" />
        </td>
      ) : null}
      <td className="text-right">
        <Amount value={settlement.amount_settled} currency="INR" />
      </td>
      <td>{!settlement.utr ? '-' : settlement.utr}</td>
      <td>
        <Time value={settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td className="text-center">
        <SettlementStatusLabel status={settlement.status} />
      </td>
    </EntityItemRow>
  );
};

SettlementsListItem.propTypes = {
  settlement: PropTypes.object,
  user: PropTypes.object,
};

const PayoutList = ({ items, user }) => {
  return (
    <div className="table-responsive mb-40">
      <table className="table table-hover">
        <thead>
          <tr>
            <th width="18%" style={{ paddingLeft: 44 }}>
              Ondemand Payout ID
            </th>
            <th width="14%" className="text-right">
              Requested Amount
            </th>
            {user.showOnDemandDeduction ? (
              <th width="11%" className="text-right">
                Deductions
              </th>
            ) : null}
            <th width={user.showOnDemandDeduction ? '13%' : '14%'} className="text-right">
              Settled Amount
            </th>
            <th width="15%">UTR</th>
            <th width="18%">Created At</th>
            <th width="15%" className="text-center">
              Status
            </th>
          </tr>
        </thead>
        <TableBody isLoading={false} colSpan={6} rows={items} emptyTableMsg="No Settlements found!">
          {items.map((settlement) => (
            <SettlementsListItem key={settlement.id} settlement={settlement} user={user} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

PayoutList.propTypes = {
  items: PropTypes.array,
  user: PropTypes.object,
};

export default connect((state) => ({ user: state.session.user }), null)(PayoutList);
