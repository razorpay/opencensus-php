import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { setItem } from 'common/utils/localStorage';
import { useState, useEffect, useCallback } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const RewardsAnnouncment = ({ userId }) => {
  const [hidden, setHidden] = useState(false);
  const bannerID = `rewards-banner-${userId}`;

  const closeAnnoucement = useCallback(() => {
    setItem(bannerID, 1);
    setHidden(true);
  }, [bannerID]);

  const interestClicked = useCallback(() => {
    analyticsTrack({
      objectName: 'Rewards Interest Banner',
      actionName: 'clicked',
      screen: 'Checkout Rewards',
      properties: {
        location: 'rewards',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    closeAnnoucement();
  }, [closeAnnoucement]);

  useEffect(() => {
    analyticsTrack({
      objectName: 'Rewards Interest Banner',
      actionName: 'appear',
      screen: 'Checkout Rewards',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, [userId]);

  if (hidden) {
    return null;
  }

  return (
    <AnnouncementBanner
      class="rewards-anc"
      theme="warning"
      title="Checkout Rewards"
      bannerKey={bannerID}
    >
      <span class="display-inline">
        Checkout Rewards lets you run promotional offers across thousands of merchants. If you are
        interested to create rewards, let us know here.
      </span>{' '}
      <button class="btn btn-outline interested-btn" onClick={interestClicked}>
        INTERESTED
      </button>
    </AnnouncementBanner>
  );
};

export default React.memo(RewardsAnnouncment);
