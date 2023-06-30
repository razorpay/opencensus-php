import React from 'react';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';

export interface RzpSuccessProps {
  merchantContact: string;
  merchantEmail: string;
  referralUrl: string;
  tracking: TODO_PD;
  source: string | undefined;
  merchantType: string;
  partnerID: string;
}
const RzpSuccessContainer = ({
  merchantContact,
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
        <div className="text-container" data-testid="success-text">
          <span className="success-text">
            Razorpay account access link will be sent to your affiliate's email at&nbsp;
          </span>
          <div className="merchant-email-wrapper">
            <span className="merchant-email">{merchantEmail}</span>
            {merchantContact ? (
              <>
                &nbsp; and via SMS on &nbsp;
                <span className="merchant-email">+91-{merchantContact}</span>
              </>
            ) : null}
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
