import React from 'react';
import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const getAnnouncementContent = (internationalProductsStatus) => {
  if (
    internationalProductsStatus.data.payment_gateway === 'approved' ||
    internationalProductsStatus.data.payment_links === 'approved'
  ) {
    return {
      title: 'International Access',
      theme: 'success',
      content: (
        <>
          Your request to enable international payments was approved.{' '}
          <Link to="/config#request-international">Click here to know more</Link>.
        </>
      ),
    };
  }

  if (
    internationalProductsStatus.data.payment_gateway === 'rejected' ||
    internationalProductsStatus.data.payment_links === 'rejected'
  ) {
    return {
      title: 'International Access',
      theme: 'danger',
      content: (
        <>
          Your request to enable international payments was rejected.{' '}
          <Link to="/config#request-international">Click here to know more</Link>.
        </>
      ),
    };
  }

  return null;
};

const InternationalRequestStatus = ({ internationalProductsStatus }) => {
  const announcement = getAnnouncementContent(internationalProductsStatus);
  if (!announcement) {
    return null;
  }

  return (
    <AnnouncementBanner
      title={announcement.title}
      theme={announcement.theme}
      bannerKey="international-request-status"
      canBeClosed={true}
    >
      {announcement.content}
    </AnnouncementBanner>
  );
};

export default InternationalRequestStatus;
