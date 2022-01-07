import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';

const SettlementsListItem = ({ settlement, user }) => {
  const handleSettlementIdClick = () => {
    trackIS.goToISDetails();
  };
  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <i className="i i-early-settlement settle-icon mr-16" />
        <Link to={`/instantsettlement/${settlement.id}`} onClick={handleSettlementIdClick}>
          <code>{settlement.id}</code>
        </Link>
      </td>
      <td className="text-right">
        <Amount value={settlement.amount_requested} currency="INR" />
      </td>
      {user.showOnDemandDeduction ? (
        <td className="text-right">
          <Amount value={settlement.fees} currency="INR" />
        </td>
      ) : null}
      <td className="text-right">
        <Amount
          className="table-settlement-amount-settled"
          value={settlement.amount_settled}
          currency="INR"
        />
      </td>
      <td className="text-center">{settlement?.scheduled ? 'Same day' : 'Instant'}</td>
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

const List = ({ settlements, isLoading, user }) => {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th style={{ paddingLeft: 39, width: '19%' }}>Settlement Id</th>
            <th width="18%" className="text-right">
              Requested Amount
            </th>
            {user.showOnDemandDeduction ? (
              <th width="13%" className="text-right">
                Deductions
              </th>
            ) : null}

            <th width="15%" className="text-right">
              Settled Amount
            </th>
            <th width="15%" className="text-center">
              Type
            </th>
            <th width={user.showOnDemandDeduction ? '19%' : '20%'}>Created At</th>
            <th width="15%" className="text-center">
              Status
            </th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={6}
          rows={settlements}
          emptyTableMsg="No Settlements found!"
        >
          {settlements.map((settlement) => (
            <SettlementsListItem key={settlement.id} settlement={settlement} user={user} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

List.propTypes = {
  settlements: PropTypes.array,
  isLoading: PropTypes.bool,
  user: PropTypes.object,
};

export default connect((state) => ({ user: state.session.user }), null)(List);
