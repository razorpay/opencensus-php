import { isFeeRuleValid } from 'merchant/views/MagicCheckout/common/feeUtils';

export const validate = (payload) => {
  const { warehouse_pincode, shipping_fee_rule, cod_fee_rule, enable_cod } = payload;
  const PINCODE_REGEX = /^[1-9][0-9]{5}$/;
  const regex = new RegExp(PINCODE_REGEX);
  if (!warehouse_pincode || !regex.test(warehouse_pincode)) {
    return { error: true, errorField: 'warehouse_pincode' };
  }
  const codValidation = isFeeRuleValid(cod_fee_rule);
  if (enable_cod && (!codValidation || codValidation.error)) {
    return { errorField: 'cod_fee_rule', ...codValidation };
  }
  const shippingValidation = isFeeRuleValid(shipping_fee_rule);
  if (!shippingValidation || shippingValidation.error) {
    return { errorField: 'shipping_fee_rule', ...shippingValidation };
  }
  return { error: false, errorField: '' };
};
