import React from 'react';
import { connect } from 'react-redux';
import { titleCase } from 'common/utils/rzp-utils';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';

/*
 * Settlement api is not changed for LA. So, components come as Payment, Refund, Tax and Fee.
 * We just want Transfers and Reversals as replacement of Payment and Refunds
 * */

function _getOverwrittenKey(title) {
  switch (title) {
    case 'payment':
      return 'Transfer';
    case 'refund':
      return 'Reversal';
    default:
      return title;
  }
}

const excludeKeys = ['tax', 'fee'];

const Breakup = ({ breakup, user }) => {
  return (
    <tr>
      <td>{titleCase(_getOverwrittenKey(breakup.component))}</td>
      <td>
        <Amount value={breakup.amount} currency={user?.merchant?.currency || 'INR'} />
      </td>
      <td>{breakup.count}</td>
      <td>{titleCase(breakup.type)}</td>
    </tr>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const ConnectedBreakup = connect(mapStateToProps)(Breakup);

export default ({ items, loading }) => {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th>Component</th>
            <th>Amount</th>
            <th>Count</th>
            <th>Type</th>
          </tr>
        </thead>
        <TableBody colSpan={4} isLoading={loading} rows={items}>
          {items.map((breakup, index) =>
            excludeKeys.indexOf(breakup.component) !== -1 ? null : (
              <ConnectedBreakup key={`breakup_${index}`} breakup={breakup} />
            ),
          )}
        </TableBody>
      </table>
    </div>
  );
};
