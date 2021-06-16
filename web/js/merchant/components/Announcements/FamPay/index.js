import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import LocalStorageService from 'common/utils/localStorage';

const FamPay = ({ user }) => {
  const hasShown = LocalStorageService.getItem(`${user?.current}-fampay-banner-shown`) === '1';

  if (hasShown) {
    return null;
  }

  if (user?.current === 'DUJEYQf1bKqpiV') {
    return (
      <AnnouncementBanner
        title="Congratulations!"
        theme="primary"
        className="fampay-fundraise-banner"
        canBeClosed={true}
        onClose={() => {
          LocalStorageService.setItem(`${user?.current}-fampay-banner-shown`, '1');
        }}
      >
        Best Wishes to <strong>Team FamPay</strong> on your latest raise. Wishing you continued
        success from <strong>Razorpay</strong> 🚀 🎉
      </AnnouncementBanner>
    );
  }

  return null;
};

export default FamPay;
