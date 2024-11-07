import React, { useCallback } from 'react';
import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { analyticsFn } from 'merchant/utils/intlPaymentsRecommendation';

const content = {
  international_cards: {
    title: 'Enable International Payments',
    bannerText:
      'Your customers are trying to pay via international cards. Enable International payments or link your PayPal account today to not miss these opportunities.',
    bannerId: 'enable-international-payments',
    ctaText: 'View international methods',
    ctaLink: '/payment-methods?instrument=international',
    eventName: 'Enable international cards snackbar cta',
    event: 'clicked',
  },
  link_paypal: {
    title: 'Link your PayPal account',
    bannerText:
      'Your customers are paying via international payment methods. Link your PayPal account with us today and enjoy 0% additional fees for PayPal transactions.',
    bannerId: 'link-paypal-account',
    ctaText: 'Link your PayPal account',
    ctaLink: '/payment-methods?instrument=international',
    eventName: 'Link PayPal snackbar cta',
    event: 'clicked',
  },
};

const IntlPaymentsAnnouncement = ({ bannerKey, userId }) => {
  const { title, bannerId, bannerText, ctaLink, ctaText, eventName, event } = content[bannerKey];

  const sendAnalyticsEvent = useCallback(() => {
    analyticsFn({ eventName, event });
  }, [event, eventName]);

  return (
    <AnnouncementBanner
      title={title}
      card_id={`${bannerId}-${userId}`}
      canBeClosed={true}
      theme="warning"
    >
      <span className="display-inline pr-5">
        {bannerText}
        <Link className="btn-link" to={ctaLink} onClick={sendAnalyticsEvent}>
          <strong>{ctaText}</strong>
        </Link>
      </span>
    </AnnouncementBanner>
  );
};

export default React.memo(IntlPaymentsAnnouncement);
