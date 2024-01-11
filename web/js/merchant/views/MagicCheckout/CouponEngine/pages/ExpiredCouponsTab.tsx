import React from 'react';

// ui imports
import GenericCoupons from 'merchant/views/MagicCheckout/CouponEngine/pages/GenericCouponTab';

const initialFiltersState = {
  type: 'all',
  code: '',
  status: 'expired',
  sort_by: 'date-desc',
  skip: 0,
  count: 10,
  display: 'all',
  source: 'all',
};

const ExpiredCouponsTab: React.FC = () => {
  return <GenericCoupons initialFilters={initialFiltersState} tabName="expired" />;
};

export default ExpiredCouponsTab;
