import React from 'react';
import { Button } from '@razorpay/blade/components';
import BackgroundImage from 'assets/capital-background.svg';
import ReferIcon from 'assets/capital-refer.svg';
import { AddMerchantSource } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

interface CapitalReferralCardProps {
  handleReferClient: (source: AddMerchantSource, type?: string) => void;
}
export const CapitalReferralCard = ({
  handleReferClient,
}: CapitalReferralCardProps): JSX.Element => {
  const handleRefer = (): void => {
    handleReferClient('referral-guide-capital', PRODUCT_TYPE.CAPITAL);
  };

  return (
    <div className="capital-referral-guide-card">
      <div className="details-container">
        <div className="heading-title">
          Now refer for Razorpay<span>X</span> Corporate Card.
        </div>
        <div className="details">
          <div className="details-with-icon">
            <img src={ReferIcon} alt="refer" width="40" height="40" />
            <div className="details-text">
              Refer merchants to RazorpayX <br /> Current Account
            </div>
          </div>
          <div className="details-with-icon">
            <img src={ReferIcon} alt="refer" width="40" height="40" />
            <div className="details-text">
              Refer merchants to RazorpayX <br /> Corporate Credit Cards
            </div>
          </div>
          <div className="details-with-icon">
            <Button onClick={handleRefer} size="medium" type="button">
              Refer Now
            </Button>
          </div>
        </div>
      </div>
      <div className="image-container">
        <img src={BackgroundImage} alt="rupee" />
      </div>
    </div>
  );
};
