const currencyConversion = (feeRule, convertTo) => {
  let newRule = {};
  if (feeRule) {
    newRule = { ...feeRule };
  }
  let cb = (val) => parseInt(val, 10) / 100;
  if (convertTo === 'paisa') {
    cb = (val) => parseInt(val, 10) * 100;
  }
  if (newRule.flat) {
    newRule.flat = cb(newRule.flat);
  }
  if (newRule.slabs) {
    newRule.slabs = newRule.slabs.map((item) => ({
      gte: cb(item.gte),
      lte: cb(item.lte),
      fee: cb(item.fee),
    }));
  }
  return newRule;
};

export const formatShippingMethods = (method, type) => {
  return {
    ...method,
    shipping_fee_rule: currencyConversion(method.shipping_fee_rule, type),
    cod_fee_rule: currencyConversion(method.cod_fee_rule, type),
  };
};

const formatServiceabilityPayload = (shippingMethodPayload) => {
  let formattedPayload = { ...shippingMethodPayload };
  formattedPayload = formatShippingMethods(formattedPayload, 'paisa');
  if (!formattedPayload.enable_cod) {
    delete formattedPayload.cod_fee_rule;
  }
  return formattedPayload;
};

export default formatServiceabilityPayload;
