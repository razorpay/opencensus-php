import React from 'react';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';

export interface RzpSuccessProps {
  merchantEmail: string;
  referralUrl: string;
  tracking: TODO_PD;
  source: string | undefined;
  merchantType: string;
  partnerID: string;
}
const RzpSuccessContainer = ({
  merchantEmail,
  referralUrl,
  tracking,
  source,
  merchantType,
  partnerID,
}: RzpSuccessProps): JSX.Element => {
  return (
    <div className="step merchant-added-container">
      <div className="success-container">
        <div className="left-icon-container">
          <i className="i i-done ModeIndicator--live-icon" />
        </div>
        <div className="text-container">
          <div>
            <span className="success-text">
              Razorpay account access link will be sent to your affiliate's email at
            </span>
          </div>
          <div className="merchant-email-wrapper">
            <span className="merchant-email">
              {merchantEmail}
              {/* MobileNumber SMS Text will be added later */}
              {/* {merchantContact ? `and +91-${merchantContact}` : ''} */}
            </span>
          </div>
        </div>
      </div>
      <div className="social-share-container">
        <div className="social-share-text">
          <span>You can also copy and share the link via other mediums</span>
        </div>
        <SocialShareGroup
          referralUrl={referralUrl}
          tracking={tracking}
          source={source}
          product={merchantType}
          partnerID={partnerID}
        />
      </div>
    </div>
  );
};

export default RzpSuccessContainer;
