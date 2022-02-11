import React from 'react';
import { getCustomURL } from 'merchant/components/DocsLink';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import track from '../../../views/PaymentPages/PaymentPages/Wysiwyg/track';

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
      <a
        href={getCustomURL(
          'https://razorpay.com/docs/payments/payment-pages/plugins-add-ons/shiprocket',
        )}
        target="_blank"
        rel="noreferrer noopener"
        class="pointer"
        onClick={track.wysiwyg.clickShiprocketDocsLink.bind(null, 'details')}
      >
        <b>Know More</b>
      </a>
    </AnnouncementBanner>
  );
});
