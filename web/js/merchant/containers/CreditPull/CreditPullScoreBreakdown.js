import React from 'react';
import Amount from 'ui/Amount';

function CreditPullScoreBreakdown({ title, total, rowData, amount }) {
  const greeting = 'Hello Function Component!';
  return (
    <>
      <div className="tab-title">{title}</div>
      <div className="tab-header">
        <span className="tab-header-lab">
          {amount ? <Amount value={total} /> : total}
        </span>
        <span className="tab-header-value">Total</span>
      </div>
      {rowData.map((item, index) => {
        return (
          <div key={`tab-row-${index}`} className="tab-row">
            <span className="tab-row-lab">
              {amount ? <Amount value={item.value} /> : item.value}
            </span>
            <span className="tab-row-value">{item.desc}</span>
          </div>
        );
      })}
    </>
  );
}
export default CreditPullScoreBreakdown;
