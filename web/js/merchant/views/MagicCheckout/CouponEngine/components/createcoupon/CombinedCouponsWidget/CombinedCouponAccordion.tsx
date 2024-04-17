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

interface CombinedCouponProps {
  couponName: string;
  combinedCouponConfig: object;
}
const CombinedCoupon: React.FC<CombinedCouponProps> = ({ couponName, combinedCouponConfig }) => {
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

  return (
    <FormGroup>
      <div className="form-label">Combine this discount with</div>
      <div className="form-input max-width-100">
        <div className="display-flex flex--column gap--12">
          {combinedCouponConfig[couponName].map((item) => {
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
