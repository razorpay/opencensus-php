import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import React, { useEffect } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const trackRender = () => {
  analyticsTrack({
    objectName: 'Payment Recovery Banner',
    actionName: 'appear',
    screen: 'home page',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toLumberjack: true,
  });
};

const PaymentRecoveryAnnouncment = ({ userId }) => {
  const bannerID = `repayment-banner-${userId}`;
  const bannerMessage = `There may be variations in your Razorpay balance due to free credits corrections. Please refer to 'Combined Reports' under the Reports section for more details.`;

  useEffect(() => {
    trackRender();
  }, []);

  return (
    <AnnouncementBanner
      theme="warning"
      title="Adjustments"
      bannerKey={bannerID}
      card_id="payment-recovery-banner"
      canBeClosed={true}
    >
      <span className="display-inline">{bannerMessage}</span>
    </AnnouncementBanner>
  );
};

export default React.memo(PaymentRecoveryAnnouncment);
