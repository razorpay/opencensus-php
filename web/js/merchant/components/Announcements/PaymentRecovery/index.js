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
      <span class="display-inline">
        You may find minor variations in your balance due to recent adjustments against your account
        related to the application of promotional credits. Adjustment details are reflected in your
        Combined Report.
      </span>
    </AnnouncementBanner>
  );
};

export default React.memo(PaymentRecoveryAnnouncment);
