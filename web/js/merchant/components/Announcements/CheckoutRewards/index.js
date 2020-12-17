import { trackMarketingExperimentBanner } from '../ga';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { showNotification } from 'merchant_common/reducers/notifications';
import { setItem } from 'common/utils/localStorage';
import { useState } from 'react';

const RewardsAnnouncment = ({ userId }) => {
  const [hidden, setHidden] = useState(false);
  const bannerID = `rewards-banner-${userId}`;

  const closeAnnoucement = () => {
    setItem(bannerID, 1);
    setHidden(true);
  };

  if (hidden) {
    return null;
  }

  trackMarketingExperimentBanner('Checkout Rewards', 'Appear');

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
      <button
        class="btn btn-outline interested-btn"
        onClick={() => {
          trackMarketingExperimentBanner('Rewards', 'Clicked Interested', userId);
          closeAnnoucement();
        }}
      >
        INTERESTED
      </button>
    </AnnouncementBanner>
  );
};

export default React.memo(RewardsAnnouncment);
