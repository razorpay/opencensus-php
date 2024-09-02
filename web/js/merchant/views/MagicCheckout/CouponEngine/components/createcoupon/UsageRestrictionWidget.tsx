import React, { useContext, useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

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

// api imports
import { getCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';

// helpers imports
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';
import {
  validateUsageRestrictionOnCount,
  validateUsageRestrictionOnCheckbox,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import { classList } from 'common/utils/rzp-utils';

// constant imports
import {
  DUPLICATE_FLOW,
  RESTRICTED_STATUS,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/constants';

interface AccordionBodyProps {
  flow: string;
}

const AccordionBody: React.FC<AccordionBodyProps> = ({ flow }) => {
  const { widgetsData, setWidgetsData, allCouponsList, setErrorStates, errorStates } =
    useContext(ModalContext);
  const { code } = useParams();
  const [isUsageRestrictionEnabled, setUsageRestriction] = useState<boolean>(false);

  useEffect(() => {
    if (code) {
      const fetchCouponData = async () => {
        let couponData;
        couponData = allCouponsList.find((coupon) => coupon.code === code);

        if (!couponData) {
          const { data } = await getCoupon(code);
          couponData = data?.coupons?.[0] ?? {};
        }

        const { isLimitedUsage, isRestrictedTotalUsage } =
          couponData?.meta_data?.display_information?.usageRestriction ?? {};
        const isCloneMode = flow?.trim() === DUPLICATE_FLOW.trim();

        /**
         * Should enforce restriction only if coupon is not being cloned , current status is RESTRICTED_STATUS and
         * Coupon had one of the usage restrictions set before and moved to published/activated status
         */
        const shouldEnableUsageRestriction =
          (isLimitedUsage || isRestrictedTotalUsage) &&
          RESTRICTED_STATUS?.includes(widgetsData?.status) &&
          !isCloneMode;

        setUsageRestriction(shouldEnableUsageRestriction);
      };

      fetchCouponData();
    }
  }, [code, flow, widgetsData?.status, allCouponsList]);

  const handleValidationsOnUsageCount = (updatedWidgetData) => {
    validateUsageRestrictionOnCount({
      usageRestriction: updatedWidgetData?.usageRestriction,
      setErrorStates,
    });
  };

  const handleCheckboxValidations = (updatedWidgetData) => {
    validateUsageRestrictionOnCheckbox({
      isUsageRestrictionEnabled,
      usageRestriction: updatedWidgetData?.usageRestriction,
      setErrorStates,
    });
    handleValidationsOnUsageCount(updatedWidgetData);
  };

  return (
    <div>
      <FormGroup>
        <p className="error-message">{errorStates.usageRestriction.enforceUsageRestriction}</p>
      </FormGroup>
      <FormGroup>
        <div className="form-label">Maximum redemption</div>
        <div className="form-input max-width-100">
          <div className="display-flex">
            <Input.Check
              checked={widgetsData.usageRestriction.isRestrictedTotalUsage}
              type="checkbox"
              name="isUnlimitedUsage"
              onChange={(e) => {
                setWidgetsData((prev) => {
                  const updatedWidgetData = {
                    ...prev,
                    usageRestriction: {
                      ...prev.usageRestriction,
                      isRestrictedTotalUsage: e.target.checked,
                    },
                  };
                  handleCheckboxValidations(updatedWidgetData);
                  return updatedWidgetData;
                });
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
                  className={classList(
                    'w-350',
                    errorStates.usageRestriction.total ? 'error-fields' : '',
                  )}
                  onChange={(e) => {
                    setWidgetsData((prev) => {
                      const updatedWidgetData = {
                        ...prev,
                        usageRestriction: {
                          ...prev.usageRestriction,
                          total: e.target.value,
                        },
                      };
                      handleValidationsOnUsageCount(updatedWidgetData);
                      return updatedWidgetData;
                    });
                  }}
                  onWheel={onWheelPreventChange}
                  min="1"
                />
                <p className="error-message">{errorStates.usageRestriction.total}</p>
              </MoreDetailsContainer>
            </div>
          ) : null}
          <div className="display-flex mt-16">
            <Input.Check
              checked={widgetsData.usageRestriction.isLimitedUsage}
              type="checkbox"
              name="isUnlimitedUsage"
              onChange={(e) => {
                setWidgetsData((prev) => {
                  const updatedWidgetData = {
                    ...prev,
                    usageRestriction: {
                      ...prev.usageRestriction,
                      isLimitedUsage: e.target.checked,
                    },
                  };
                  handleCheckboxValidations(updatedWidgetData);
                  return updatedWidgetData;
                });
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
                  className={classList('w-350', errorStates.usageRestriction.maxUsage)}
                  onChange={(e) => {
                    setWidgetsData((prev) => {
                      const updatedWidgetData = {
                        ...prev,
                        usageRestriction: {
                          ...prev.usageRestriction,
                          maxUsage: e.target.value,
                        },
                      };
                      handleValidationsOnUsageCount(updatedWidgetData);
                      return updatedWidgetData;
                    });
                  }}
                  onWheel={onWheelPreventChange}
                  min={1}
                />
                <p className="error-message">{errorStates.usageRestriction.maxUsage}</p>
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

interface UsageRestrictionWidgetProps {
  flow: string;
}

const UsageRestrictionWidget: React.FC<UsageRestrictionWidgetProps> = ({ flow }) => {
  return (
    <div>
      <Accordion header={<div>Usage restriction</div>} body={<AccordionBody flow={flow} />} />
    </div>
  );
};

export default UsageRestrictionWidget;
