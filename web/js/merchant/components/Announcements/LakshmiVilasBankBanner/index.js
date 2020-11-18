import React from 'react';
import { Link } from 'react-router-dom';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const LakshmiVilasBankBanner = () => {
  return (
    <AnnouncementBanner
      title="Bank A/C not supported"
      theme="danger"
      bannerKey="lakshmi-vilas-bank"
      canBeClosed={false}
    >
      Razorpay isn’t supporting Laxmi Vilas Bank accounts temporarily, please edit your bank account
      to continue receiving settlements.
      <span className="big-dot-separator" />
      <Link to="/profile#request-bank-account-change">Edit Bank A/C</Link>
    </AnnouncementBanner>
  );
};

export default LakshmiVilasBankBanner;
