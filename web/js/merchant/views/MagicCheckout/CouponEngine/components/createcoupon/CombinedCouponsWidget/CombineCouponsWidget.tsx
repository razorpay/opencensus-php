import React, { useContext } from 'react';
import { useSplitzService } from 'common/splitz';

// ui imports
import Input from 'common/new-ui/Input';
import {
  FormGroup,
  Label,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

const AccordionBody: React.FC = () => {
  const { widgetsData, setWidgetsData } = useContext(ModalContext);

  return (
    <FormGroup>
      <div className="form-label">Combine this discount with</div>
      <div className="form-input max-width-100">
        <div className="display-flex">
          <Input.Check
            checked={widgetsData.combineCoupons.shouldCombineFreeShippingCoupon}
            type="checkbox"
            name="shouldCombineFreeShippingCoupon"
            onChange={(e) => {
              setWidgetsData((prev) => ({
                ...prev,
                combineCoupons: {
                  ...prev.combineCoupons,
                  shouldCombineFreeShippingCoupon: e.target.checked,
                },
              }));
            }}
            autoRender
          />
          <Label>Free Shipping Coupons</Label>
        </div>
      </div>
    </FormGroup>
  );
};

const CombineCouponsWidget: React.FC | null = () => {
  const { abExperiments } = useSplitzService();
  const shouldShowCheckoutV2Changes = abExperiments?.checkout_v2?.variables?.result === 'on';

  if (shouldShowCheckoutV2Changes) {
    return (
      <div>
        <Accordion header={<div>Coupon combinations (optional) </div>} body={<AccordionBody />} />
      </div>
    );
  }

  return null;
};

export default CombineCouponsWidget;
