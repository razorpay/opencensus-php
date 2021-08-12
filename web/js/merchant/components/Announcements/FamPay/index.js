import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import LocalStorageService from 'common/utils/localStorage';
import RTracking from 'react-tracking';
import { compose } from 'redux';

const FamPay = ({ user, tracking }) => {
  const hasShown = LocalStorageService.getItem(`${user?.current}-healthifyme-banner-shown`) === '1';

  if (hasShown) {
    return null;
  }

  React.useEffect(() => {
    tracking?.trackEvent(
      window.rzpQ?.merchantActions().success('merchant_dashboard.display_banner', {
        banner_text:
          'Best Wishes to Team HealthifyMe on your latest raise. Wishing you continued success from Razorpay',
        card_id: 'HealthifyMe fund raise',
      }),
    );
  }, []);

  if (user?.current === 'CQeKIc4TOFPrNU') {
    return (
      <AnnouncementBanner
        title="Congratulations!"
        theme="primary"
        className="fampay-fundraise-banner"
        canBeClosed={true}
        card_id="fundraise-banner"
        onClose={() => {
          LocalStorageService.setItem(`${user?.current}-healthifyme-banner-shown`, '1');
          tracking.trackEvent(
            window.rzpQ?.merchantActions().success('merchant_dashboard.close_banner', {
              banner_text:
                'Best Wishes to Team HealthifyMe on your latest raise. Wishing you continued success from Razorpay',
              card_id: 'HealthifyMe fund raise',
            }),
          );
        }}
      >
        Best Wishes to <strong>Team HealthifyMe</strong> on your latest raise. Wishing you continued
        success from <strong>Razorpay</strong> 🚀 🎉
      </AnnouncementBanner>
    );
  }

  return null;
};

export default compose(
  RTracking({
    page: 'HealthifyMeBanner',
  }),
)(FamPay);
