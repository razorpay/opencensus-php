import React from 'react';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import Spinner from 'common/ui/Spinner';

import { useCallback } from 'react';
import { titleCase } from 'common/utils/rzp-utils';

const isResponseNew = (obj) => {
  delete obj.amountInINR;
  delete obj.resourceUrl;
  delete obj.resourceIdField;

  let newResponse = false;

  if ('tax' in obj && 'fee' in obj) newResponse = true;
  else newResponse = false;

  return {
    columnNames: Object.keys(obj),
    newResponse,
  };
};

const Breakup = ({ breakup, newResponse }) => {
  return (
    <tr>
      <td>{titleCase(breakup.component)}</td>
      <td>
        <Amount value={breakup.amount} currency="INR" />
      </td>
      <td>{breakup.count}</td>
      <td>{titleCase(breakup.type)}</td>
      {newResponse && (
        <td>
          <Amount value={breakup.fee} currency="INR" />
        </td>
      )}
      {newResponse && (
        <td>
          <Amount value={breakup.tax} currency="INR" />
        </td>
      )}
    </tr>
  );
};

const BreakupTable = ({ items, loading }) => {
  // Table columns will be dynamic now, so adding check on items
  if (items.length === 0) {
    return (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  const { newResponse, columnNames } = useCallback(isResponseNew(items[0]), [items]);

  return (
    <div class="table-reponsive">
      <table class="table table-hover">
        <thead>
          <tr>
            {columnNames.map((column, idx) => {
              return <th key={idx}>{titleCase(column)}</th>;
            })}
          </tr>
        </thead>
        <TableBody colSpan={4} isLoading={loading} rows={items}>
          {items.map((breakup, index) => (
            <Breakup key={`breakup_${index}`} breakup={breakup} newResponse={newResponse} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

export default BreakupTable;
