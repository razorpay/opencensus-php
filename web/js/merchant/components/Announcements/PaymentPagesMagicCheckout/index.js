import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ userId, email }) => {
  return (
    <AnnouncementBanner
      title="Early Access"
      canBeClosed={true}
      card_id="pp-magic-checkout-banner"
      bannerKey={`pp-magic-checkout-banner-${userId}`}
    >
      Would you like to integrate magic checkout to your payment pages?
      <div className="big-circle-seprator" />
      <a
        href={`https://razorpay.typeform.com/to/tCFM180r#mid=${userId}&email=${email}`}
        target="_blank"
        rel="noreferrer noopener"
      >
        <b>Know More</b>
      </a>
    </AnnouncementBanner>
  );
});
