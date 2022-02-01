import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ userId }) => {
  return (
    <AnnouncementBanner
      title="Razorpay is now on Shiprocket"
      canBeClosed={true}
      theme="primary"
      card_id="shiprocket-banner"
      bannerKey={`shiprocket-banner-${userId}`}
    >
      Automatically create orders on Shiprocket from Payment pages
      <div className="big-circle-seprator" />
      <a href="#" target="_blank" rel="noreferrer noopener" class="pointer">
        <b>Know More</b>
      </a>
    </AnnouncementBanner>
  );
});
