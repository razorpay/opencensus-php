import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { getItem, setItem } from 'common/utils/localStorage';
import React, { useState, useEffect, useCallback } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const RewardsOnBoardingAnnouncment = ({ userId }) => {
  const [isInstrested, setIsInstrested] = useState(false);
  const bannerID = `rewards-onboarding-banner-${userId}`;

  const closeAnnoucement = useCallback(() => {
    setItem(bannerID, 1);
    setIsInstrested(true);
  }, [bannerID]);

  const interestClicked = useCallback(() => {
    analyticsTrack({
      objectName: 'Onboarding Interest Banner',
      actionName: 'clicked',
      screen: 'Checkout Rewards',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
    closeAnnoucement();
  }, [closeAnnoucement]);

  useEffect(() => {
    analyticsTrack({
      objectName: 'Onboarding Interest Banner',
      actionName: 'appear',
      screen: 'Checkout Rewards',
      properties: {
        location: 'onboarding',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  }, [userId]);

  if (!isInstrested && getItem(bannerID)) setIsInstrested(!!getItem(bannerID));

  return (
    <AnnouncementBanner
      class="rewards-onboarding-anc"
      theme="success"
      title="Coming Soon !"
      card_id="checkout-rewards-interest-banner"
    >
      <span class="display-inline">
        {isInstrested
          ? 'Your interest has been recorded! We are currently testing out the feature. We will notify you once it’s available.'
          : 'Wouldn’t it be great if you could reward your customers for every purchase? Let us know if you are interested.'}
      </span>{' '}
      <span className="grp-buttons">
        <a
          href="https://razorpay.com/docs/payment-gateway/checkout-rewards/"
          target="_blank"
          className="know-more-link"
          rel="noreferrer noopener"
        >
          Know More
        </a>
        {!isInstrested && (
          <button class="btn btn-outline interested-btn" onClick={interestClicked}>
            INTERESTED
          </button>
        )}
      </span>
    </AnnouncementBanner>
  );
};

export default React.memo(RewardsOnBoardingAnnouncment);
