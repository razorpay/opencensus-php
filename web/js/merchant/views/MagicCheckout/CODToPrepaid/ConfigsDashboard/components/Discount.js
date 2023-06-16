import React, { useState } from 'react';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import SwitchField from 'common/ui/Forms/SwitchField';

import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';

import {
  VALIDATION_MSGS,
  POPOVER_INFO_TEXT,
  DISCOUNT_OPTIONS,
  DISCOUNT_TYPE,
  DISCOUNT_INFO,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

const SlabsHeader = ({ label, required }) => (
  <div className="font-bold font-12 slabs-input slabs-input-header">
    {label}
    {required && <sup className="magic-checkout-required"> *</sup>}
  </div>
);

const Discount = (props) => {
  const [isRequired, setIsRequired] = useState({
    percent: false,
    maxDiscount: false,
    minOrderValue: false,
  });

  const { availDiscount, discountType, discount, setAvailDiscount, setDiscount, setDiscountType } =
    props;

  const discountRegex = new RegExp(/^\+?([1-9]\d*)?$/gm);

  const updateDiscountValues = (e) => {
    const { value } = e.target;

    if (value !== discountType) {
      setDiscountType(value);
      setIsRequired({ percent: false, maxDiscount: false, minOrderValue: false });
      setDiscount({ percent: '', maxDiscount: '', minOrderValue: '', error: null });
    }
  };

  const isValidDiscount = (val, type) => {
    if (val === '' || val < 0) {
      return true;
    }

    if (
      type === DISCOUNT_TYPE.maxDiscount &&
      discount.minOrderValue !== '' &&
      discount.minOrderValue > 0 &&
      val >= discount.minOrderValue
    ) {
      return false;
    }

    if (
      type === DISCOUNT_TYPE.minOrderValue &&
      discount.maxDiscount !== '' &&
      discount.maxDiscount >= 0 &&
      val <= discount.maxDiscount
    ) {
      return false;
    }

    return true;
  };

  const editDiscount = (e, type) => {
    const { value } = e.target;

    if (!discountRegex.test(value)) {
      return;
    }

    const parsedVal = value === '' ? value : parseInt(value, 10);

    if (type === 'percent' && parsedVal > 99) {
      return;
    }

    if (discountType === DISCOUNT_TYPE.percentage || isValidDiscount(parsedVal, type)) {
      setDiscount((prevState) => ({
        ...prevState,
        [type]: parsedVal,
        error: null,
      }));
    } else {
      setDiscount((prevState) => ({
        ...prevState,
        [type]: parsedVal,
        error: VALIDATION_MSGS.discount,
      }));
    }
  };

  const switchMode = () => {
    setAvailDiscount((prevState) => {
      if (!prevState) {
        setDiscountType('flat');
        setIsRequired({ percent: false, maxDiscount: false, minOrderValue: false });
        setDiscount({ percent: '', maxDiscount: '', minOrderValue: '', error: null });
      }

      return !prevState;
    });
  };

  const getLabel = () => {
    return discountType === DISCOUNT_TYPE.percentage ? 'Maximum discount' : 'Discount value';
  };

  const checkEmptyField = (type) => {
    setIsRequired((prevState) => ({ ...prevState, [type]: discount[type] === '' }));
  };

  const percentageErrorClass = isRequired.percent && discount.percent === '' ? ' is-invalid' : '';

  const maxDiscountErrorClass =
    discount.error ||
    (discountType !== DISCOUNT_TYPE.percentage &&
      isRequired.maxDiscount &&
      discount.maxDiscount === '')
      ? ' is-invalid'
      : '';

  const minOrderValueErrorClass =
    discount.error || (isRequired.minOrderValue && discount.minOrderValue === '')
      ? ' is-invalid'
      : '';

  return (
    <div className="config-box discount-config-box">
      <div className="configuration-label col-md-4">
        <label>
          Discount
          <sup className="magic-checkout-required"> *</sup>
          <i className="i i-info-outline intelligence-tooltip font-normal">
            <Popover theme="dark">
              <PopoverBody>
                <p>{POPOVER_INFO_TEXT.discount}</p>
              </PopoverBody>
            </Popover>
          </i>
        </label>
      </div>
      <div className="configuration-value col-md-8">
        <div className="display-flex discount-container">
          <div>
            <span className="toggler-btn">
              <SwitchField
                checked={availDiscount}
                type="prime"
                onChange={switchMode}
                data-testid="discount-toggle"
              />
              {availDiscount ? (
                <b className="text-primary toggle-status">Enabled</b>
              ) : (
                <b className="text-faded toggle-status">Disabled</b>
              )}
            </span>
          </div>
          {availDiscount && (
            <div className="discount-config-container">
              <h5>Type of discount</h5>
              <Input.Radio
                className="pl-radio-configs"
                defaultValue={discountType}
                options={DISCOUNT_OPTIONS}
                onChange={(e) => updateDiscountValues(e)}
              />
              <div className="input-container">
                {discountType === 'percentage' && (
                  <div className="discount-input-container">
                    <SlabsHeader label="Discount percentage" required />
                    <Input
                      addonAfter="%"
                      className={`discount-input${percentageErrorClass}`}
                      type="text"
                      value={discount.percent}
                      onBlur={() => checkEmptyField('percent')}
                      onWheel={onWheelPreventChange}
                      onChange={(e) => editDiscount(e, 'percent')}
                    />
                    {percentageErrorClass !== '' && <div className="required-error">Required</div>}
                  </div>
                )}
                <div className="discount-input-container">
                  <SlabsHeader
                    label={getLabel()}
                    required={discountType !== DISCOUNT_TYPE.percentage}
                  />
                  <Input
                    addonBefore="₹"
                    className={`discount-input${maxDiscountErrorClass}`}
                    type="text"
                    value={discount.maxDiscount}
                    onBlur={() => checkEmptyField(DISCOUNT_TYPE.maxDiscount)}
                    onWheel={onWheelPreventChange}
                    onChange={(e) => editDiscount(e, DISCOUNT_TYPE.maxDiscount)}
                    data-testid={DISCOUNT_TYPE.maxDiscount}
                  />
                  {maxDiscountErrorClass !== '' && !discount.error && (
                    <div className="required-error">Required</div>
                  )}
                </div>
                <div className="discount-input-container">
                  <SlabsHeader label="Minimum order value" required />
                  <Input
                    addonBefore="₹"
                    className={`discount-input${minOrderValueErrorClass}`}
                    type="text"
                    value={discount.minOrderValue}
                    onBlur={() => checkEmptyField(DISCOUNT_TYPE.minOrderValue)}
                    onWheel={onWheelPreventChange}
                    onChange={(e) => editDiscount(e, DISCOUNT_TYPE.minOrderValue)}
                    data-testid={DISCOUNT_TYPE.minOrderValue}
                  />
                  {minOrderValueErrorClass !== '' && !discount.error && (
                    <div className="required-error">Required</div>
                  )}
                </div>
              </div>
              {discount.error && <p className="discount-error">{discount.error}</p>}
              <div className="discount-info-container display-flex">
                <i className="i i-info-outline intelligence-tooltip font-normal" />
                <p className="info-text">{DISCOUNT_INFO}</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Discount;
