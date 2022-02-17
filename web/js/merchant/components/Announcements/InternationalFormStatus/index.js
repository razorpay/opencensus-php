import React, { useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { analyticsFn } from 'merchant/utils/intlPaymentsRecommendation';

const eventName = 'International activation form reminder banner';

const getAnnouncementContent = ({ status, track, businessName = 'your business' }) => {
  if (
    status &&
    status.data?.international_activation_form_initiated &&
    !status.data?.international_activation_form_completed
  ) {
    return {
      title: 'International Payments',
      theme: 'danger',
      content: (
        <span>
          Don&apos;t stop! You&apos;re 1 step away from unlocking international payments for{' '}
          {businessName}.{' '}
          <Link to="/payment-methods?instrument=international" onClick={track}>
            <strong>Activate Now</strong>
          </Link>
        </span>
      ),
    };
  }

  return null;
};

const InternationalFormStatus = ({ status, businessName }) => {
  const trackActivateNowClick = useCallback(() => {
    analyticsFn({ eventName, event: 'clicked' });
  }, []);

  const trackCloseClick = useCallback(() => {
    analyticsFn({ eventName, event: 'closed' });
  }, []);

  const announcement = getAnnouncementContent({
    status,
    track: trackActivateNowClick,
    businessName,
  });

  useEffect(() => {
    if (announcement) {
      analyticsFn({ eventName, event: 'shown' });
    }
  }, [announcement]);

  if (!announcement) {
    return null;
  }

  return (
    <AnnouncementBanner
      title={announcement.title}
      theme={announcement.theme}
      bannerKey="international-form-status"
      canBeClosed={true}
      card_id="international-form-status"
      handleClose={trackCloseClick}
    >
      {announcement.content}
    </AnnouncementBanner>
  );
};

export default InternationalFormStatus;
