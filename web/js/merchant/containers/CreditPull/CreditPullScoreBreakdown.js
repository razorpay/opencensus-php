import React from 'react';
import Amount from 'rzp/ui/Amount';

function CreditPullScoreBreakdown({ title, total, rowData, amount }) {
  return (
    <>
      <div className="tab-title">{title}</div>
      <div className="tab-header">
        <span className="tab-header-lab">
          {amount ? <Amount value={total} currency={'INR'} /> : total}
        </span>
        <span className="tab-header-value">Total</span>
      </div>
      {rowData.map((item, index) => {
        return (
          <div key={`tab-row-${index}`} className="tab-row">
            <span className="tab-row-lab">
              {amount ? (
                <Amount value={item.value} currency={'INR'} />
              ) : (
                item.value
              )}
            </span>
            <span className="tab-row-value">{item.desc}</span>
          </div>
        );
      })}
    </>
  );
}
export default CreditPullScoreBreakdown;
