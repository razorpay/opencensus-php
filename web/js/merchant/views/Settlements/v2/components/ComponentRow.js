import React from 'react';
import Amount from 'common/ui/Amount';
import { titleCase } from 'common/utils/rzp-utils';

const ComponentRow = ({ breakupItem, newResponse }) => {
  return (
    <tr
      data-testid={`settlementBreakup${breakupItem.type}`}
      /* Added this conditional classes to TR also for the m-web support */
      className={breakupItem.type === 'credit' ? `highlight-credit-row` : `highlight-debit-row`}
    >
      <td className={breakupItem.type === 'credit' ? `highlight-credit` : `highlight-debit`}>
        <div>
          <b>{titleCase(breakupItem.component)}</b>
        </div>
      </td>
      <td>
        <div>
          <div className="title">Type</div>
          <span>{titleCase(breakupItem.type)}</span>
        </div>
      </td>
      <td>
        <div>
          <div className="title">Count</div>
          {breakupItem.count ? <span>{breakupItem.count}</span> : '-'}
        </div>
      </td>
      <td>
        <div>
          <div className="title">Amount</div>
          <Amount value={breakupItem.amount} currency="INR" />
        </div>
      </td>
      {newResponse && (
        <td>
          <div>
            <div className="title">Fee</div>
            <Amount value={breakupItem.fee} currency="INR" />
          </div>
        </td>
      )}
      {newResponse && (
        <td>
          <div>
            <div className="title">Tax</div>
            <Amount value={breakupItem.tax} currency="INR" />
          </div>
        </td>
      )}
      {newResponse && (
        <td>
          <div>
            <div className="title">Settled Amount</div>
            {breakupItem.type === 'debit' ? (
              <span>
                - <Amount value={breakupItem.settled_amount * -1} currency="INR" />
              </span>
            ) : (
              <span>
                <Amount value={breakupItem.settled_amount} currency="INR" />
              </span>
            )}
          </div>
        </td>
      )}
    </tr>
  );
};

export default ComponentRow;
