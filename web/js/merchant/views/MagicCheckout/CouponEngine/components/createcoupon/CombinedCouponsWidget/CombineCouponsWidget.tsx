import React from 'react';
import { useSplitzService } from 'common/splitz';

// UI imports
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';

//constants
import {
  DEFAULT_MULTI_COUPON_CONFIG,
  DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CombinedCouponsWidget/constants';
import CombinedCoupon from './CombinedCouponAccordion';

interface CombinedCouponProps {
  couponName: string;
  combinedCouponConfig: object;
}

const CombineCouponsWidget: React.FC<CombinedCouponProps> | null = ({ couponName }) => {
  const { abExperiments } = useSplitzService();
  const shouldShowCheckoutV2Changes = abExperiments?.checkout_v2?.variables?.result === 'on';
  const shouldShowMultiCoupons =
    abExperiments?.magic_multi_coupons_enabled?.variables?.result === 'on';

  const combinedCouponConfig = shouldShowMultiCoupons
    ? DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG
    : DEFAULT_MULTI_COUPON_CONFIG;

  if (shouldShowCheckoutV2Changes && combinedCouponConfig[couponName]) {
    return (
      <div>
        <Accordion
          header={<div>Coupon combinations (optional) </div>}
          body={
            <CombinedCoupon couponName={couponName} combinedCouponConfig={combinedCouponConfig} />
          }
        />
      </div>
    );
  }

  return null;
};

export default CombineCouponsWidget;
