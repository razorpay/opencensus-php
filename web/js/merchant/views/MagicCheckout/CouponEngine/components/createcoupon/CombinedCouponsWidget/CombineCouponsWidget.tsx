import React from 'react';
import { connect } from 'react-redux';
import type { User } from 'common/typings';

// UI imports
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import CombinedCoupon from './CombinedCouponAccordion';

//constants
import { DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CombinedCouponsWidget/constants';

interface CombinedCouponProps {
  couponName: string;
  user: User;
}

const CombineCouponsWidget: React.FC<CombinedCouponProps> | null = ({ couponName, user }) => {
  const shouldShowMultiCoupons = user.isMultiCouponsEnabled;

  if (shouldShowMultiCoupons && DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG[couponName]) {
    return (
      <div>
        <Accordion
          header={<div>Coupon combinations (optional) </div>}
          body={<CombinedCoupon couponName={couponName} />}
        />
      </div>
    );
  }

  return null;
};

export default connect((state) => ({ user: state.session.user }))(CombineCouponsWidget);
