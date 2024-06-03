import React from 'react';
import { useSplitzService } from 'common/splitz';

// UI imports
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import CombinedCoupon from './CombinedCouponAccordion';

//constants
import { DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CombinedCouponsWidget/constants';

interface CombinedCouponProps {
  couponName: string;
}

const CombineCouponsWidget: React.FC<CombinedCouponProps> | null = ({ couponName }) => {
  const { abExperiments } = useSplitzService();
  const shouldShowMultiCoupons =
    abExperiments?.magic_multi_coupons_enabled?.variables?.result === 'on';

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

export default CombineCouponsWidget;
