import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/CardPaymentsBlocked';

export default React.memo(({ isCAW }) => {
  return (
    <AnnouncementBanner
      docURL={
        isCAW
          ? 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments'
          : 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/subscriptions'
      }
    >
      {isCAW ? (
        <>
          Due to recent RBI mandate, cards issued by Indian banks are temporarily disabled for new
          registration links. Existing registration links and tokens are not impacted and will
          continue to function normally. To learn more about the implications of these changes,
          please refer our
        </>
      ) : (
        <>
          Due to recent RBI mandate, cards issued by Indian banks are temporarily disabled for new
          Subscriptions. There is no impact on existing subscriptions and they will continue to
          function normally. To learn more about the implications of these changes, please refer our
        </>
      )}
    </AnnouncementBanner>
  );
});
