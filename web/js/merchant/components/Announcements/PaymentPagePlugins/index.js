import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ bannerKey }) => {
  return (
    <AnnouncementBanner
      title="Help us choose our next Plugin!"
      canBeClosed={true}
      theme="warning"
      bannerKey={bannerKey}
      card_id="introducing-pp-plugins-banner"
    >
      Which of these would you like to see on Payment Pages?{' '}
      <a
        href="https://razorpay.typeform.com/to/JHcKs5Zy#email=xxxxx&mid=xxxxx"
        target="_blank"
        rel="noreferrer"
      >
        <b>Choose Now</b>
      </a>
    </AnnouncementBanner>
  );
});
