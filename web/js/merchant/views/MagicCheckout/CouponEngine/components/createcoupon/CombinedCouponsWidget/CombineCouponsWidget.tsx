import React from 'react';
import { connect } from 'react-redux';
import type { User } from 'common/typings';

// UI imports
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import CombinedCoupon from './CombinedCouponAccordion';

//constants
import {
  DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG,
  COUPON_KEYS,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CombinedCouponsWidget/constants';

interface CombinedCouponProps {
  couponName: string;
  user: User;
  isRcodEnabled: boolean;
}

const CombineCouponsWidget: React.FC<CombinedCouponProps> | null = ({
  couponName,
  user,
  isRcodEnabled,
}) => {
  const shouldShowMultiCoupons = user.isMultiCouponsEnabled;

  /**
   * We are not allowing free shipping coupon combinations for magicX merchants.
   * For bulk_order,freebie_item and buyx_gety coupon types , free shipping is the only possible coupon combination as of now , so
   * for these coupon types we do not render coupon combination sections for magicX merchants.
   */
  if (
    isRcodEnabled &&
    [COUPON_KEYS.bulk_order, COUPON_KEYS.freebie_item, COUPON_KEYS.buyx_gety].includes(couponName)
  )
    return null;

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

export default connect((state) => ({
  user: state.session.user,
  isRcodEnabled: state.magicCheckout.rcod,
}))(CombineCouponsWidget);
