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
    <div class="total-owed-amount-breakup">
      <div class="breakup-wrapper">
        <div class="flex m-b">
          <div class="full-width no-margin">Total Principal Due</div>
          <Amount value={totalPrincipalAmount * 100} />
        </div>

        <div class="m-b">
          <div className="flex">
            <div className="full-width no-margin">Total Interest Due</div>
            <Amount value={totalInterestAmount * 100} />
          </div>
          <span class="text-faded">Inclusive all fees</span>
        </div>
      </div>
      <div class="bordered-top">
        <div className="flex">
          <div className="full-width no-margin">
            <strong>Total Owed Amount</strong>
          </div>
          <Amount value={totalOwedAmount * 100} />
        </div>
      </div>
    </div>
  );
};

export default TotalOwedAmountBreakdown;
