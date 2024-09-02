import React, { useCallback, useContext } from 'react';
import { connect } from 'react-redux';

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
  isRcodEnabled: boolean;
}

const CombinedCoupon: React.FC<CombinedCouponProps> = ({ couponName, isRcodEnabled }) => {
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
            //   * We are not allowing free shipping coupon combinations for magicX merchants.
            if (isRcodEnabled && item?.type === 'shouldCombineFreeShippingCoupon') return null;
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

const mapStateToProps = (state) => ({
  isRcodEnabled: state.magicCheckout.rcod,
});

export default connect(mapStateToProps, null)(CombinedCoupon);
