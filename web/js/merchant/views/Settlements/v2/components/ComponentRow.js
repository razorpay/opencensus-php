import React from 'react';
import Amount from 'common/ui/Amount';
import { titleCase } from 'common/utils/rzp-utils';

const RenderColumn = ({ title, comp }) => (
  <td>
    <div>
      <div className="title">{title}</div>
      {comp}
    </div>
  </td>
);

const ComponentRow = ({ breakupItem, newResponse }) => {
  const { type, component, count, amount, fee, tax, settled_amount } = breakupItem;
  let customClass = 'highlight-debit';
  if (type === 'credit') {
    customClass = 'highlight-credit';
  }
  if (component === 'unreconciled') {
    customClass = 'highlight-unreconciled';
  }
  return (
    <tr
      data-testid={`settlementBreakup${type}`}
      /* Added this conditional classes to TR also for the m-web support */
      className={`${customClass}-row`}
    >
      <td className={customClass}>
        <div>
          <b>{titleCase(component)}</b>
        </div>
      </td>
      <RenderColumn title="Type" comp={<span>{titleCase(type)}</span>} />
      <RenderColumn title="Count" comp={count ? <span>{count}</span> : '-'} />
      <RenderColumn title="Amount" comp={<Amount value={amount} currency="INR" />} />
      {newResponse && <RenderColumn title="Fee" comp={<Amount value={fee} currency="INR" />} />}
      {newResponse && <RenderColumn title="Tax" comp={<Amount value={tax} currency="INR" />} />}
      {newResponse && (
        <RenderColumn
          title="Settled Amount"
          comp={
            type === 'debit' ? (
              <span>
                - <Amount value={settled_amount * -1} currency="INR" />
              </span>
            ) : (
              <span>
                <Amount value={settled_amount} currency="INR" />
              </span>
            )
          }
        />
      )}
    </tr>
  );
};

export default ComponentRow;
