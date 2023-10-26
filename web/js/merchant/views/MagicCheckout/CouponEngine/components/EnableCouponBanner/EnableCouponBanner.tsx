import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { StyledLink } from 'merchant/views/MagicCheckout/CouponEngine/components/EnableCouponBanner/EnableCouponBannerStyles';

const EnableCouponBanner: React.FC = () => {
  return (
    <AnnouncementBanner
      title="Enable coupons"
      theme="purply"
      card_id="enable-coupons"
      className="enable-coupons-banner"
    >
      Enable coupons for customers to make it visible on Magic Checkout.{' '}
      <StyledLink to="settings/coupons"> Click here </StyledLink>
      to enable
    </AnnouncementBanner>
  );
};

export default EnableCouponBanner;
