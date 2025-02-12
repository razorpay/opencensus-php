import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const SettlementsListItem = ({ settlement, handleBreakupClick, user }) => {
  return (
    <EntityItemRow id={settlement.id}>
      <td>
        <Link to={`/settlements/${settlement.id}`}>
          <code>{settlement.id}</code>
        </Link>
      </td>
      <td>
        <Amount value={settlement.amount} currency={user?.merchant?.currency || 'INR'} />
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

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const ConnectedSettlementsListItem = connect(mapStateToProps)(SettlementsListItem);

export default (props) => {
  let { settlements, isLoading, showBreakup } = props;

  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th>Settlement Id</th>
            <th>Amount</th>
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
            <ConnectedSettlementsListItem
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
