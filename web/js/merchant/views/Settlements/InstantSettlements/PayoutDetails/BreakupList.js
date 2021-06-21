import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { trackOnDemandPayoutDetailsBreakup } from '../../trackEvents';

const processSummaryList = (instantSettlement) => {
  const reversedTxnCount = instantSettlement.ondemand_payouts.items.reduce((count, item) => {
    return item.status === 'reversed' ? count + 1 : count;
  }, 0);

  const processedTxnCount = instantSettlement.ondemand_payouts.items.reduce((count, item) => {
    return item.status === 'processed' ? count + 1 : count;
  }, 0);

  const pendingTxnCount =
    instantSettlement.ondemand_payouts.count - reversedTxnCount - processedTxnCount;

  return [
    {
      component: 'Settled Amount',
      amount: <Amount currency="INR" value={instantSettlement.amount_settled} />,
      count: processedTxnCount,
    },
    ...(instantSettlement.amount_pending !== 0
      ? [
          {
            component: 'Pending Amount',
            amount: <Amount currency="INR" value={instantSettlement.amount_pending} />,
            count: pendingTxnCount,
          },
        ]
      : []),
    ...(instantSettlement.amount_reversed !== 0
      ? [
          {
            component: 'Reversed Amount',
            amount: <Amount currency="INR" value={instantSettlement.amount_reversed} />,
            count: reversedTxnCount,
          },
        ]
      : []),
    {
      component: 'Ondemand Fee',
      amount: <Amount currency="INR" value={instantSettlement.fees - instantSettlement.tax} />,
    },
    {
      component: 'Tax',
      amount: <Amount currency="INR" value={instantSettlement.tax} />,
    },
  ];
};

const SummaryListItem = ({ component, amount, count }) => {
  return (
    <EntityItemRow id={component}>
      <td>{component}</td>
      <td className="text-right">{amount}</td>
      <td style={{ paddingLeft: 68 }} className="text-center">
        {count}
      </td>
    </EntityItemRow>
  );
};

SummaryListItem.propTypes = {
  component: PropTypes.string,
  amount: PropTypes.element,
};

const BreakupList = ({ instantSettlement }) => {
  const items = processSummaryList(instantSettlement);

  useEffect(() => {
    trackOnDemandPayoutDetailsBreakup(instantSettlement);
  }, []);

  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th width="22%">Component</th>
            <th className="text-right">Amount</th>
            <th style={{ paddingLeft: 68 }} width="44%" className="text-center">
              Count
            </th>
          </tr>
        </thead>
        <TableBody
          isLoading={false}
          colSpan={3}
          rows={items}
          emptyTableMsg="No Ondemand Settlement Breakup found!"
        >
          {items.map((item) => {
            return <SummaryListItem {...item} key={item.component} />;
          })}
        </TableBody>
      </table>
    </div>
  );
};

BreakupList.propTypes = {
  instantSettlement: PropTypes.object,
};

export default BreakupList;
