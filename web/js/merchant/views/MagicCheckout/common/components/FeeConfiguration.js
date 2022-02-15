import Slabs from 'merchant/views/MagicCheckout/common/components/Slabs';
import { RULE_TYPES_RADIO_INPUT } from 'merchant/views/MagicCheckout/ShippingServices/constants';
import { FEE_RULES, RULE_TYPES } from 'merchant/views/MagicCheckout/constants';
import Input from 'common/new-ui/Input';
import { useState, useCallback, useEffect } from 'react';
import validators from 'merchant/views/MagicCheckout/common/feeUtils';

const FeeConfiguration = ({ feeRule, updateUserFeeRule, type, required = true }) => {
  const [slabs, setSlabs] = useState([]);

  useEffect(() => {
    if (feeRule.slabs) {
      setSlabs(feeRule.slabs);
    }
  }, [feeRule.slabs]);

  const label = type === FEE_RULES.COD_FEE_RULE ? 'COD Charge' : 'Shipping Charge';
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
    <>
      <div className="filter-item link-account-instruction display-flex c-fee-configuration">
        <div className="serviceability-setting-label font-bold" for="cod-availability">
          {label} {required && <sup className="magic-checkout-color-red">*</sup>}
        </div>
        <div className="width-full">
          <div className="display-flex justify-space-between slabs-container">
            <Input.Radio
              key={feeRule.rule_type}
              name={`${type}fee`}
              defaultValue={feeRule.rule_type}
              options={RULE_TYPES_RADIO_INPUT}
              onChange={handleRuleTypeChange}
              className="c-rule-type"
            />
          </div>
          <div>
            {feeRule.rule_type === RULE_TYPES.FLAT ? (
              <div className="fee-block">
                <div className="font-bold font-12">{flatLabel}</div>
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
              <div className="fee-block">
                <Slabs type={type} slabs={slabs} updateSlabs={updateSlabs} />
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </>
  );
};

export default FeeConfiguration;
