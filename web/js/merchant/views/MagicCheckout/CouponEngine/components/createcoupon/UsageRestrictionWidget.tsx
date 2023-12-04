import React, { useContext } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import {
  FormGroup,
  Label,
  MoreDetailsContainer,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers imports
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';

const AccordionBody: React.FC = () => {
  const { widgetsData, setWidgetsData } = useContext(ModalContext);

  return (
    <div>
      <FormGroup>
        <div className="form-label">Maximum redemption</div>
        <div className="form-input max-width-100">
          <div className="display-flex">
            <Input.Check
              checked={widgetsData.usageRestriction.isRestrictedTotalUsage}
              type="checkbox"
              name="isUnlimitedUsage"
              onChange={(e) => {
                setWidgetsData((prev) => ({
                  ...prev,
                  usageRestriction: {
                    ...prev.usageRestriction,
                    isRestrictedTotalUsage: e.target.checked,
                  },
                }));
              }}
              autoRender
            />
            <Label>Number of times this discount can be used in total</Label>
          </div>
          {widgetsData.usageRestriction.isRestrictedTotalUsage ? (
            <div>
              <MoreDetailsContainer>
                <Input
                  name="maxUsage"
                  type="number"
                  required
                  value={widgetsData.usageRestriction.total}
                  className="w-350"
                  onChange={(e) => {
                    setWidgetsData((prev) => ({
                      ...prev,
                      usageRestriction: {
                        ...prev.usageRestriction,
                        total: e.target.value,
                      },
                    }));
                  }}
                  onWheel={onWheelPreventChange}
                />
              </MoreDetailsContainer>
            </div>
          ) : null}
          <div className="display-flex mt-16">
            <Input.Check
              checked={widgetsData.usageRestriction.isLimitedUsage}
              type="checkbox"
              name="isUnlimitedUsage"
              onChange={(e) => {
                setWidgetsData((prev) => ({
                  ...prev,
                  usageRestriction: {
                    ...prev.usageRestriction,
                    isLimitedUsage: e.target.checked,
                  },
                }));
              }}
              autoRender
            />
            <Label>Number of times coupon can be used per customer</Label>
          </div>
          {widgetsData.usageRestriction.isLimitedUsage ? (
            <div>
              <MoreDetailsContainer>
                <Input
                  name="maxUsage"
                  type="number"
                  required
                  value={widgetsData.usageRestriction.maxUsage}
                  className="w-350"
                  onChange={(e) => {
                    setWidgetsData((prev) => ({
                      ...prev,
                      usageRestriction: {
                        ...prev.usageRestriction,
                        maxUsage: e.target.value,
                      },
                    }));
                  }}
                  onWheel={onWheelPreventChange}
                />
              </MoreDetailsContainer>

              <div className="mt-16 display-flex">
                <Input.Radio
                  key={widgetsData.usageRestriction.limitBy}
                  defaultValue={widgetsData.usageRestriction.limitBy}
                  onChange={(e) => {
                    setWidgetsData((prev) => ({
                      ...prev,
                      usageRestriction: {
                        ...prev.usageRestriction,
                        limitBy: e.target.value,
                      },
                    }));
                  }}
                  autoRender
                  name="limitedUseagePer"
                  options={[
                    {
                      label: 'By mobile no',
                      value: 'phone',
                    },
                    {
                      label: 'By email id',
                      value: 'email',
                    },
                  ]}
                />{' '}
              </div>
            </div>
          ) : null}
        </div>
      </FormGroup>
    </div>
  );
};

const UsageRestrictionWidget: React.FC = () => {
  return (
    <div>
      <Accordion header={<div>Usage restriction</div>} body={<AccordionBody />} />
    </div>
  );
};

export default UsageRestrictionWidget;
