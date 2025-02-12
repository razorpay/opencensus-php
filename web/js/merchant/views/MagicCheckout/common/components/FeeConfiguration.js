import { useState, useCallback, useEffect } from 'react';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Slabs from 'merchant/views/MagicCheckout/common/components/Slabs';
import validators from 'merchant/views/MagicCheckout/common/feeUtils';
import {
  RULE_TYPES_RADIO_INPUT,
  PAYMENT_PAGE_RULE_TYPES,
} from 'merchant/views/MagicCheckout/ShippingServices/constants';
import { FEE_RULES, RULE_TYPES } from 'merchant/views/MagicCheckout/constants';

const FeeConfiguration = ({ feeRule, updateUserFeeRule, type, required = true, isPaymentPage }) => {
  const [slabs, setSlabs] = useState([]);

  useEffect(() => {
    if (feeRule.slabs) {
      setSlabs(feeRule.slabs);
    }
  }, [feeRule.slabs]);

  const getPaymentPageLabel = (magicPPValue, defaultValue) =>
    isPaymentPage ? magicPPValue : defaultValue;

  const label =
    type === FEE_RULES.COD_FEE_RULE
      ? 'COD Charge'
      : getPaymentPageLabel('Do you charge anything extra for delivery?', 'Shipping Charge');
  const flatLabel =
    type === FEE_RULES.COD_FEE_RULE
      ? 'COD flat charge for all orders'
      : 'Shipping flat charge for all orders';

  const updateFeeChange = useCallback(
    (val, key) => {
      const { rule_type } = feeRule;
      const newFeeRule = { rule_type };
      if (rule_type !== RULE_TYPES.FREE) {
        newFeeRule[key] = val;
      }
      updateUserFeeRule(type, newFeeRule);
    },
    [updateUserFeeRule, feeRule],
  );

  const handleRuleTypeChange = useCallback(
    (e) => {
      const val = e?.target?.value;
      if (!val) return;
      feeRule.rule_type = val;
      updateUserFeeRule(type, { ...feeRule });
      if (val === RULE_TYPES.FREE) {
        updateFeeChange();
      }
    },
    [updateFeeChange],
  );

  const updateFlatFeeChange = useCallback(
    (e) => {
      let val = e?.target?.value;
      val = isNaN(parseInt(val, 10)) ? val : parseInt(val, 10);
      updateFeeChange(val, RULE_TYPES.FLAT);
    },
    [updateFeeChange],
  );

  const updateSlabs = useCallback(
    (newSlabs) => {
      setSlabs(newSlabs);
      updateFeeChange(newSlabs, RULE_TYPES.SLABS);
    },
    [setSlabs, updateFeeChange],
  );

  return (
    <div className="filter-item link-account-instruction display-flex c-fee-configuration">
      <div className="serviceability-setting-label font-bold" htmlFor="cod-availability">
        {label} {required && <sup className="magic-checkout-color-red">*</sup>}
        {isPaymentPage && (
          <i className="i i-info-outline">
            <Popover align="bottom" theme="dark">
              <PopoverBody>
                <div>We’ll add this to your total order amount automatically during payment</div>
              </PopoverBody>
            </Popover>
          </i>
        )}
      </div>
      <div className="width-full">
        <div className="display-flex justify-space-between slabs-container">
          <Input.Radio
            key={feeRule.rule_type}
            name={`${type}fee`}
            defaultValue={feeRule.rule_type}
            options={getPaymentPageLabel(PAYMENT_PAGE_RULE_TYPES, RULE_TYPES_RADIO_INPUT)}
            onChange={handleRuleTypeChange}
            className="c-rule-type"
          />
        </div>
        {isPaymentPage && <hr />}
        <div className={isPaymentPage ? 'rules-payment-page' : ''}>
          {feeRule.rule_type === RULE_TYPES.FLAT ? (
            <div className="fee-block">
              <div className={`font-bold font-${isPaymentPage ? '14' : '12'}`}>
                {getPaymentPageLabel('Add delivery amount', flatLabel)}
                {isPaymentPage && (
                  <span className="magic-checkout-color-red mandatory-symbol">*</span>
                )}
              </div>
              <div className="slabs-input-container">
                <Input
                  addonBefore="₹"
                  id="warehouse-pincode"
                  value={feeRule.flat}
                  type="number"
                  onChange={updateFlatFeeChange}
                  className="slabs-flat-charge-input"
                  validator={validators.flat}
                />
              </div>
            </div>
          ) : null}
          {feeRule.rule_type === RULE_TYPES.SLABS ? (
            <div className="fee-block slabs-sec">
              {isPaymentPage && (
                <div className="font-bold font-14 slabs-heading">
                  Add delivery amount
                  <span className="magic-checkout-color-red mandatory-symbol">*</span>
                </div>
              )}
              <Slabs
                type={type}
                slabs={slabs}
                updateSlabs={updateSlabs}
                isPaymentPage={isPaymentPage}
              />
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
};

export default FeeConfiguration;
