import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ userId, email }) => {
  return (
    <AnnouncementBanner
      title="Early Access"
      canBeClosed={true}
      theme="warning"
      card_id="pp-custom-domain-banner"
      bannerKey={`pp-custom-domain-banner-${userId}`}
    >
      Would you like to link a domain to your payment pages?
      <div className="big-circle-seprator" />
      <a
        href={`https://razorpay.typeform.com/to/IGPMFsd7#mid=${userId}&email=${email}`}
        target="_blank"
        rel="noreferrer noopener"
      >
        <b>Apply Now</b>
      </a>
    </AnnouncementBanner>
  );
});
