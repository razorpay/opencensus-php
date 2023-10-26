import React, { useContext, ChangeEvent } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import {
  FormGroup,
  DottedButtonWrapper,
  SubTitle,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers imports
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';

const MaxQuantityWidget: React.FC = () => {
  const { widgetsData, setWidgetsData } = useContext(ModalContext);
  return (
    <FormGroup>
      <div className="form-label">Usage Limit</div>
      <div className="form-input">
        <div className="display-flex">
          <Input.Check
            checked={widgetsData.discountOffered.hasLimitedUseagePerOrder}
            type="checkbox"
            name="isUnlimitedUsage"
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
              setWidgetsData({
                ...widgetsData,
                discountOffered: {
                  ...widgetsData.discountOffered,
                  hasLimitedUseagePerOrder: e.target.checked,
                },
              });
            }}
            autoRender
          />
          <SubTitle>Maximum uses per order</SubTitle>
        </div>
        {widgetsData.discountOffered.hasLimitedUseagePerOrder ? (
          <DottedButtonWrapper>
            <Input
              name="maxUsage"
              type="number"
              required
              defaultValue={widgetsData.discountOffered.maxUsagePerOrder}
              value={widgetsData.discountOffered.maxUsagePerOrder}
              className="w-350"
              onChange={(e: ChangeEvent<HTMLInputElement>) => {
                setWidgetsData({
                  ...widgetsData,
                  discountOffered: {
                    ...widgetsData.discountOffered,
                    maxUsagePerOrder: e.target.value,
                  },
                });
              }}
              onWheel={onWheelPreventChange}
            />
          </DottedButtonWrapper>
        ) : null}
      </div>
    </FormGroup>
  );
};

export default MaxQuantityWidget;
