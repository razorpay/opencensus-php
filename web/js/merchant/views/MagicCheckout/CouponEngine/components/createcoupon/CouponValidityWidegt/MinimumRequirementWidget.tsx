import React, { useContext, ChangeEvent } from 'react';
import { useSplitzService } from 'common/splitz';

// ui imports
import Input from 'common/new-ui/Input';
import {
  FormGroup,
  MinimumQuantityWrapper,
  InputIcon,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

//helpers
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';
import { getMinReqWidgetOptions } from 'merchant/views/MagicCheckout/CouponEngine/helpers';

interface MinimumRequirementWidgetProps {
  couponName: string;
}

const MinimumRequirementWidget: React.FC<MinimumRequirementWidgetProps> = ({ couponName }) => {
  const stateObject = couponName === 'buyx_gety' ? 'productsPurchased' : 'discountDetails';
  const { widgetsData, setWidgetsData } = useContext(ModalContext);
  const { abExperiments } = useSplitzService();
  const shouldShowCheckoutV2Changes = abExperiments?.checkout_v2?.variables?.result === 'on';

  return (
    <FormGroup>
      <div className="form-label">Purchase requirements</div>
      <MinimumQuantityWrapper>
        <div className="w-350">
          <Input.Select
            key={widgetsData[stateObject].minimumType}
            name={`${couponName}-selectbox`}
            options={getMinReqWidgetOptions(couponName, shouldShowCheckoutV2Changes)}
            defaultValue={widgetsData[stateObject].minimumType}
            onChange={(e: ChangeEvent<HTMLSelectElement>) => {
              setWidgetsData({
                ...widgetsData,
                [stateObject]: {
                  ...widgetsData[stateObject],
                  minimumType: e.target.value,
                  minimumValue: 0,
                },
              });
            }}
          />
        </div>
        {widgetsData[stateObject].minimumType !== 'no_min_qty' ? (
          <div>
            {widgetsData[stateObject].minimumType === 'min_qty' ? (
              <Input
                name="minimumQuantity"
                type="number"
                required
                className="w-200"
                addonAfter={<span> Qty </span>}
                defaultValue={widgetsData[stateObject].minimumValue}
                value={widgetsData[stateObject].minimumValue}
                onChange={(e: ChangeEvent<HTMLInputElement>) => {
                  setWidgetsData({
                    ...widgetsData,
                    [stateObject]: {
                      ...widgetsData[stateObject],
                      minimumValue: e.target.value,
                    },
                  });
                }}
                onWheel={onWheelPreventChange}
              />
            ) : (
              <Input
                name="minimumOrderValue"
                type="number"
                required
                className="w-200"
                addonBefore={<InputIcon className="i-rupee" />}
                defaultValue={widgetsData[stateObject].minimumValue}
                value={widgetsData[stateObject].minimumValue}
                onChange={(e: ChangeEvent<HTMLInputElement>) => {
                  setWidgetsData({
                    ...widgetsData,
                    [stateObject]: {
                      ...widgetsData[stateObject],
                      minimumValue: e.target.value,
                    },
                  });
                }}
                onWheel={onWheelPreventChange}
              />
            )}
          </div>
        ) : null}
      </MinimumQuantityWrapper>
    </FormGroup>
  );
};

export default MinimumRequirementWidget;
