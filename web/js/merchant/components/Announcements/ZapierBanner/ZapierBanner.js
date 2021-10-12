import React from 'react';
import { Link } from 'react-router-dom';
import { setItem } from 'common/utils/localStorage';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ fromWhere, bannerKey }) => {
  return (
    <AnnouncementBanner
      title="Razorpay is now on Zapier"
      canBeClosed={true}
      theme="primary"
      card_id={`Sep22-AppStore-Zapier-GTM-${fromWhere}`}
      onClose={() => {
        setItem(bannerKey, 1);
      }}
    >
      Now integrate Razorpay with GSheets, Zoho, Slack and 3000+ apps through Zapier{' '}
      <a
        href="https://zapier.com/apps/razorpay-1/integrations/?utm_source=GrowthAsset"
        target="_blank"
        rel="noreferrer noopener"
        class="Button--primary Button scheduled-btn-act btn-border"
      >
        <b>Try Now</b>
      </a>{' '}
      <Link to="/app-store/zapier" target="_blank">
        <strong>Learn More</strong>
      </Link>
    </AnnouncementBanner>
  );
});
