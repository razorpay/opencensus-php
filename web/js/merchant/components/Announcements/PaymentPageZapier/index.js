import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ bannerKey }) => {
  return (
    <AnnouncementBanner
      title="Razorpay with Zapier"
      canBeClosed={true}
      theme="primary"
      bannerKey={bannerKey}
      card_id="pp-zapier-banner"
    >
      We are excited to give you early access for our Razorpay Zapier integration.{' '}
      <a
        href="https://zapier.com/apps/razorpay-1/integrations"
        target="_blank"
        rel="noreferrer"
        class="Button--secondary Button scheduled-btn-act btn-border"
      >
        <b>Try Now</b>
      </a>
    </AnnouncementBanner>
  );
});
