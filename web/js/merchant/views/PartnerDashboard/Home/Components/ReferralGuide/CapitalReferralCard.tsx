import React, { useEffect } from 'react';
import { Button } from '@razorpay/blade/components';
import BackgroundImage from 'assets/capital-background.svg';
import ReferIcon from 'assets/capital-refer.svg';
import { AddMerchantSource } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { analyticsTrack } from 'common/utils/analytics';

interface CapitalReferralCardProps {
  handleReferClient: (source: AddMerchantSource, type?: string) => void;
  mid: string;
}
export const CapitalReferralCard = ({
  handleReferClient,
  mid,
}: CapitalReferralCardProps): JSX.Element => {
  const handleRefer = (): void => {
    analyticsTrack({
      screen: 'Home screen Capital Banner',
      objectName: 'partnerships.capital.new merchant',
      actionName: 'refer now button clicked',
      properties: {
        partner_id: mid,
      },
      toLumberjack: true,
    });
    handleReferClient('referral-guide-capital', PRODUCT_TYPE.CAPITAL);
  };

  useEffect(() => {
    analyticsTrack({
      screen: 'Home screen Capital Banner',
      objectName: 'partnerships.capital.new merchant',
      actionName: 'banner viewed',
      properties: {
        partner_id: mid,
      },
      toLumberjack: true,
    });
  }, [mid]);

  return (
    <div className="capital-referral-guide-card">
      <div className="details-container">
        <div className="heading-title">
          Now refer for Razorpay<span>X</span> Line Of Credit.
        </div>
        <div className="details">
          <div className="details-with-icon">
            <img src={ReferIcon} alt="refer" width="40" height="40" />
            <div className="details-text">
              Refer merchants to RazorpayX <br /> Line Of Credit
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
