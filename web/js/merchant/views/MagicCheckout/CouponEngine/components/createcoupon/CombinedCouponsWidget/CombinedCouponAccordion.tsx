import React, { useCallback, useContext } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import {
  FormGroup,
  Label,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import { Alert } from '@razorpay/blade/components';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// constants
import { DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CombinedCouponsWidget/constants';

interface CombinedCouponProps {
  couponName: string;
}

const CombinedCoupon: React.FC<CombinedCouponProps> = ({ couponName }) => {
  const { widgetsData, setWidgetsData } = useContext(ModalContext);

  const isCombinedCouponsChecboxChecked = useCallback(() => {
    const {
      shouldCombineAmountOffOrderCoupon,
      shouldCombineOtherAmountOffProductCoupons,
      shouldCombineBulkDiscountCoupon,
      shouldCombineBxGyDiscountCoupon,
      shouldCombineFreeShippingCoupon,
    } = widgetsData.combineCoupons;

    return (
      shouldCombineAmountOffOrderCoupon ||
      shouldCombineOtherAmountOffProductCoupons ||
      shouldCombineBulkDiscountCoupon ||
      shouldCombineBxGyDiscountCoupon ||
      shouldCombineFreeShippingCoupon
    );
  }, [widgetsData.combineCoupons]);

  const handleOnChange = (e) => {
    setWidgetsData((prev) => ({
      ...prev,
      combineCoupons: {
        ...prev.combineCoupons,
        [e?.target?.name]: e?.target?.checked,
      },
    }));
  };

  if (!DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG[couponName]) {
    return null;
  }

  return (
    <FormGroup>
      <div className="form-label">Combine this discount with</div>
      <div className="form-input max-width-100">
        <div className="display-flex flex--column gap--12">
          {DISCOUNT_TYPE_BASED_MULTI_COUPON_CONFIG[couponName].map((item) => {
            return (
              <div className="display-flex" key={item.type}>
                <Input.Check
                  checked={widgetsData.combineCoupons[`${item.type}`]}
                  type="checkbox"
                  name={item.type}
                  onChange={(e) => {
                    handleOnChange(e);
                  }}
                  autoRender
                />
                <Label>{item.name}</Label>
              </div>
            );
          })}
        </div>
        {isCombinedCouponsChecboxChecked() && (
          <Alert
            title="Combining coupons can result in large discounts"
            description="Please verify before proceeding ahead"
            marginTop="spacing.8"
            color="notice"
            isDismissible={false}
          />
        )}
      </div>
    </FormGroup>
  );
};

export default CombinedCoupon;
