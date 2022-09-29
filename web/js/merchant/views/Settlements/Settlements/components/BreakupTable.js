import React from 'react';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import { titleCase } from 'common/utils/rzp-utils';

const getBreakupComponentTitle = (key) => {
  if (key === 'credit_repayment') {
    return (
      <>
        Lending Repayment
        <span
          data-tooltip="This amount is collected to repay for your Loans / Cash Advance withdrawals."
          data-tooltip-position="top"
        >
          <i className="i i-info-outline" />
        </span>
      </>
    );
  }
  return titleCase(key);
};

const Breakup = ({ breakup, isNew }) => {
  return (
    <tr>
      <td>{getBreakupComponentTitle(breakup.component)}</td>
      <td>
        <Amount value={breakup.amount} currency="INR" />
      </td>
      <td>{breakup.count}</td>
      <td>{titleCase(breakup.type)}</td>
      {isNew && (
        <td>
          <Amount value={breakup.fee} currency="INR" />
        </td>
      )}
      {isNew && (
        <td>
          <Amount value={breakup.tax} currency="INR" />
        </td>
      )}
      {isNew && (
        <td>
          <Amount value={breakup.settled_amount} currency="INR" />
        </td>
      )}
    </tr>
  );
};

const BreakupTable = ({ items, loading, isNew, columnNames }) => {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            {columnNames.map((column, idx) => {
              return <th key={idx}>{titleCase(column)}</th>;
            })}
          </tr>
        </thead>
        <TableBody colSpan={4} isLoading={loading} rows={items}>
          {items.map((breakup, index) => (
            <Breakup key={`breakup_${index}`} breakup={breakup} isNew={isNew} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

export default BreakupTable;
