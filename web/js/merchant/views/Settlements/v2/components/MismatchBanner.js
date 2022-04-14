import Banner from 'common/ui/Banner';
import Amount from 'common/ui/Amount';
import { titleCase } from 'common/utils/rzp-utils';

export const MismatchBanner = ({ totalAmount, calculatedAmounts, gatewayName }) => {
  return (
    <Banner className="mis-match-banner">
      <div className="banner-heading">
        <i className="i i-info-outline" />
        Settlement mismatch of <Amount value={totalAmount - calculatedAmounts} currency="INR" />
      </div>
      <p>
        Razorpay Optimizer has a log of <Amount value={calculatedAmounts} currency="INR" /> but, we
        fetched an amount of <Amount value={totalAmount} currency="INR" /> from{' '}
        {titleCase(gatewayName)}. Check the {titleCase(gatewayName)} dashboard for mismatched
        amounts.
      </p>
    </Banner>
  );
};
