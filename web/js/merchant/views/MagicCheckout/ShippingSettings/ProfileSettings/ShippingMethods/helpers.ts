import { ShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { FormInput, Inputs } from './FormContext';
import { getDefaultFormValues } from './constants';
import { rupeesToPaise, paiseToRupees } from 'common/utils/rzp-utils';
import { deepCopy } from 'common/utils/immutable';

export const buildShippingMethodsPayload = (values) => {
  values = deepCopy(values);
  const payload: Record<string, any> = {};
  Object.keys(values).forEach((key) => {
    if (key === 'fee') {
      payload[key] = rupeesToPaise(values[key].value);
    } else {
      if (key === 'fee_rules') {
        const { amount } = values[key].value;
        if (amount) {
          amount.gte = rupeesToPaise(amount.gte);
          amount.lt = rupeesToPaise(amount.lt);
        }
      }
      if (key === 'attribute_rules') {
        const value = values[key].value;
        if (value?.customer_tags) {
          Object.keys(value.customer_tags).forEach(
            (key) => (value.customer_tags[key] = rupeesToPaise(value.customer_tags[key])),
          );
        }
      }
      payload[key] = values[key].value;
    }
  });
  if (!Object.keys(payload?.attribute_rules?.customer_tags || {}).length) {
    delete payload.attribute_rules;
  }
  return payload;
};

export const buildFormDataFromMethod = (values: ShippingMethod): Record<Inputs, FormInput> => {
  const payload = { ...getDefaultFormValues() };
  values = deepCopy(values);
  Object.entries(payload).forEach((entry) => {
    if (entry[0] === 'fee') {
      values[entry[0]] = paiseToRupees(values[entry[0]]);
    }
    if (entry[0] === 'fee_rules') {
      const value = values[entry[0]];
      const { amount } = value;
      if (amount) {
        amount.gte = paiseToRupees(amount.gte);
        amount.lt = paiseToRupees(amount.lt);
      }
      values[entry[0]] = value;
    }
    if (entry[0] === 'attribute_rules') {
      const value = values[entry[0]];
      if (value?.customer_tags) {
        Object.keys(value.customer_tags).forEach(
          (key) => (value.customer_tags[key] = paiseToRupees(value.customer_tags[key])),
        );
      }
    }
    payload[entry[0]] = {
      ...entry[1],
      value: values[entry[0]] ? values[entry[0]] : payload[entry[0]].value,
    };
  });
  return payload;
};

export const validateInputs = (values) => {
  return Object.entries(values).every((item: any) => {
    if (typeof item[1].value === 'string') item[1].value = item[1].value.trim();
    return item[1]?.validation(item[1].value);
  });
};
