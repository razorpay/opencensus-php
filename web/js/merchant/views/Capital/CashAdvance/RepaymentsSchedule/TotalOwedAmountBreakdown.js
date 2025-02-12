import React from 'react';
import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

const TotalOwedAmountBreakdown = ({
  loading,
  totalOwedAmount,
  totalInterestAmount,
  totalPrincipalAmount,
}) => {
  if (loading) {
    return <PlaceholderLoader />;
  }
  return (
    <div className="total-owed-amount-breakup">
      <div className="breakup-wrapper">
        <div className="flex m-b">
          <div className="full-width no-margin">Total Principal Due</div>
          <Amount value={totalPrincipalAmount} />
        </div>

        <div className="m-b">
          <div className="flex">
            <div className="full-width no-margin">Total Interest Due</div>
            <Amount value={totalInterestAmount} />
          </div>
          <span className="text-faded">Inclusive all fees</span>
        </div>
      </div>
      <div className="bordered-top">
        <div className="flex">
          <div className="full-width no-margin">
            <strong>Total Owed Amount</strong>
          </div>
          <Amount value={totalOwedAmount} />
        </div>
      </div>
    </div>
  );
};

export default TotalOwedAmountBreakdown;
