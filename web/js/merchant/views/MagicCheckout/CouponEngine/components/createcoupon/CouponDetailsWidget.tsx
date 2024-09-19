import React, { useContext } from 'react';
import { connect } from 'react-redux';

import type { GenericRecord } from 'merchant/views/MagicCheckout/types';

// ui imports
import Input from 'common/new-ui/Input';
import {
  Card,
  CouponName,
  FormGroup,
  CheckboxGroup,
  CheckboxLabelWithInfo,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import Popover, { PopoverBody } from 'common/ui/Popover';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers imports
import { couponDetailsValidator } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import { getDisplayCouponType } from 'merchant/views/MagicCheckout/CouponEngine/helpers';
import { classList } from 'common/utils/rzp-utils';

interface CouponDetailsProps {
  couponName: string;
  flow: string;
  isRcodEnabled: boolean;
  magicSettings: GenericRecord;
}

const CouponDetails: React.FC<CouponDetailsProps> = ({
  couponName,
  flow = 'created',
  isRcodEnabled,
  magicSettings,
}) => {
  const { widgetsData, setWidgetsData, errorStates, setErrorStates } = useContext(ModalContext);
  const isAutoApplyCouponsEnabled =
    magicSettings.one_cc_coupon_engine && magicSettings.one_cc_auto_apply_coupons;

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>, name: string) => {
    const { type, checked: isChecked } = e.target;
    const newValue = type === 'checkbox' ? isChecked : e.target.value;
    setWidgetsData({
      ...widgetsData,
      couponDetails: {
        ...widgetsData.couponDetails,
        [name]: newValue,
      },
    });
  };

  const handleFormValidations = (name: string, value: string) => {
    couponDetailsValidator({ fieldName: name, value, setErrorStates, flowName: flow });
  };

  return (
    <div>
      <CouponName>
        <span>{flow === 'edit' ? 'Edit Coupon' : 'Create new coupon'}</span>
        <span className="coupon-slash">{` / `}</span>
        <span className="coupon-type">{getDisplayCouponType(couponName)}</span>
      </CouponName>
      <Card>
        <FormGroup>
          <div className="form-label">Coupon Code</div>
          <div className="form-input">
            <Input
              name="code"
              type="text"
              defaultValue={widgetsData.couponDetails.code}
              value={widgetsData.couponDetails.code}
              onChange={(e) => handleInputChange(e, 'code')}
              className={classList('w-350', errorStates.couponDetails.code ? 'error-field' : '')}
              placeholder="Enter coupon code"
              onBlur={(e) => {
                const value = e.target.value;
                handleFormValidations('code', value);
              }}
              disabled={flow === 'edit' && widgetsData.status !== 'created'}
            />
            <p className="error-message">{errorStates.couponDetails.code}</p>
          </div>
        </FormGroup>
        <FormGroup>
          <div className="form-label">Coupon description</div>
          <div className="form-input">
            <Input.Textarea
              name="description"
              defaultValue={widgetsData.couponDetails.description}
              value={widgetsData.couponDetails.description}
              onChange={(e) => handleInputChange(e, 'description')}
              style={{ resize: 'none' }}
              className={classList(
                'w-350',
                errorStates.couponDetails.description ? 'error-field' : '',
              )}
              placeholder="Enter description of coupon to be shown in checkout"
              onBlur={(e) => {
                const value = e.target.value;
                handleFormValidations('description', value);
              }}
            />
            <p className="error-message">{errorStates.couponDetails.description}</p>

            <CheckboxGroup>
              <Input.Check
                checked={widgetsData.couponDetails.display}
                type="checkbox"
                name="display"
                onChange={(e) => handleInputChange(e, 'display')}
                autoRender
              />
              <span>Display this coupon at checkout</span>
            </CheckboxGroup>

            {widgetsData.couponDetails.display && (
              <CheckboxGroup>
                <Input.Check
                  checked={widgetsData.couponDetails.couponDiscoveryEnabled}
                  type="checkbox"
                  name="couponDiscoveryEnabled"
                  onChange={(e) => handleInputChange(e, 'couponDiscoveryEnabled')}
                  autoRender
                />
                <CheckboxLabelWithInfo>
                  <span>Enable coupon as an unavailable coupon</span>
                  <i className="i i-info-outline">
                    <Popover theme="dark">
                      <PopoverBody>
                        <div>
                          If you enable this option, this coupon will be shown as an Unavailable
                          coupon (disabled state) when the cart conditions or eligibility conditions
                          are not met by the users. As soon as the conditions are fulfilled, this
                          coupon gets enabled automatically. The users can then use this coupon.{' '}
                        </div>
                      </PopoverBody>
                    </Popover>
                  </i>
                </CheckboxLabelWithInfo>
              </CheckboxGroup>
            )}
            {/**
             * For MagicX , Enabling Coupon Code for Prepaid Payment methods is not available
             */}
            {!isRcodEnabled && (
              <CheckboxGroup>
                <Input.Check
                  checked={widgetsData.couponDetails.prepaidMethodsOnly}
                  type="checkbox"
                  name="prepaidMethodsOnly"
                  onChange={(e) => handleInputChange(e, 'prepaidMethodsOnly')}
                  autoRender
                />
                <span>Enable this coupon code only for Prepaid Payment methods</span>
              </CheckboxGroup>
            )}
            {isAutoApplyCouponsEnabled && (
              <CheckboxGroup>
                <Input.Check
                  checked={widgetsData.couponDetails.autoapply}
                  type="checkbox"
                  name="autoapply"
                  onChange={(e) => handleInputChange(e, 'autoapply')}
                  autoRender
                />
                <span>Automatically apply this coupon for eligible users</span>
              </CheckboxGroup>
            )}
          </div>
        </FormGroup>
      </Card>
    </div>
  );
};

const mapStateToProps = (state) => ({
  magicSettings: state.magic_settings,
  isRcodEnabled: state.magicCheckout.rcod,
});

export default connect(mapStateToProps, null)(CouponDetails);
