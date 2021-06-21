import React from 'react';
import { Link } from 'react-router-dom';
import PropTypes from 'prop-types';
import Amount from 'common/ui/Amount';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import { trackOnDemandPayoutIdClick } from '../../trackEvents';

const ListItem = ({ payout, settlement, settlementId }) => {
  return (
    <EntityItemRow id={payout.id}>
      <td>
        <Link
          to={`/instantsettlement_details/${settlementId}`}
          onClick={() => {
            trackIS.clickUTRISDetails();
            trackOnDemandPayoutIdClick({
              settlementDetails: settlement,
              payoutDetails: payout,
            });
          }}
        >
          <code>{payout.utr ? payout.utr : '-'}</code>
        </Link>
      </td>
      <td className="text-right">
        <Amount value={payout.amount} currency="INR" />
      </td>
      <td className="text-center">
        <SettlementStatusLabel status={payout.status} />
      </td>
    </EntityItemRow>
  );
};

ListItem.propTypes = {
  settlement: PropTypes.object,
  settlementId: PropTypes.string,
};

const List = ({ settlement, isLoading }) => {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th width="33%">UTR</th>
            <th width="33%" className="text-right">
              Payout Amount
            </th>
            <th className="text-center">Status</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={3}
          rows={settlement.ondemand_payouts.items}
          emptyTableMsg="No Settlements found!"
        >
          {settlement.ondemand_payouts.items.slice(0, 3).map((onDemandPayout) => (
            <ListItem
              key={onDemandPayout.id}
              settlementId={settlement.id}
              payout={onDemandPayout}
              settlement={settlement}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

List.propTypes = {
  settlement: PropTypes.object,
  isLoading: PropTypes.bool,
};

export default List;
