import React from 'react';

import CouponSettings from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/CouponSettings';
import GetStartedWithCoupons from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/GetStartedWithCoupons';

import { Page } from './styled';

const EnableCouponsTab: React.FC = () => (
  <Page className="content-wrapper">
    <CouponSettings />
    <GetStartedWithCoupons />
  </Page>
);

export default EnableCouponsTab;
