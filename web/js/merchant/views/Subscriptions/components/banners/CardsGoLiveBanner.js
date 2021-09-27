import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default React.memo(() => {
  return (
    <AnnouncementBanner title="IMPORTANT UPDATE" canBeClosed={true} theme="warning">
      <span class="display-inline">
        Your favourite payment method ‘Cards’ is back and live on Razorpay Subscriptions!
      </span>
      <DocLink
        class="btn btn-link"
        href="https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments/cards/"
        target="_blank"
      >
        Click here to know more
      </DocLink>
    </AnnouncementBanner>
  );
});
