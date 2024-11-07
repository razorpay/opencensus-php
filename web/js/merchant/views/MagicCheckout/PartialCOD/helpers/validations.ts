import {
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItem,
} from 'merchant/views/MagicCheckout/PartialCOD/types';

export const validateCreateAdvancedSlab = (
  slabData: PrepaidPaymentAmountItem,
  onErrorFound: (errors: {
    min_order_amount: string;
    max_order_amount: string;
    customer_risk_category: string;
    value: string;
  }) => void,
) => {
  let isValid = true;
  const newErrors = {
    min_order_amount: '',
    max_order_amount: '',
    customer_risk_category: '',
    value: '',
  };
  const { rules, value, type } = slabData;
  const { min_order_amount, max_order_amount, customer_risk_category } = rules;

  // Validate min order amount
  if (isNaN(min_order_amount) || min_order_amount < 0) {
    newErrors.min_order_amount = 'Enter a valid amount';
    isValid = false;
  }

  // Validate max order amount
  if (
    !max_order_amount ||
    (typeof max_order_amount === 'number' &&
      (max_order_amount <= 0 || max_order_amount < min_order_amount))
  ) {
    newErrors.max_order_amount = 'Enter a valid amount';
    isValid = false;
  }

  // Validate customer risk category
  if (customer_risk_category.length === 0) {
    newErrors.customer_risk_category = 'Select at least one risk category';
    isValid = false;
  }

  // Validate value
  if (isNaN(value) || value <= 0) {
    newErrors.value = `Enter a valid ${type === 'flat' ? 'amount' : 'percentage'}`;
    isValid = false;
  }

  // Additional checks for percentage type
  if (type === 'percentage' && value > 50) {
    newErrors.value = 'Pre-pay amount should not be higher than 50% of order value';
    isValid = false;
  }

  // Validate if pre-pay amount exceeds max order amount
  if (typeof max_order_amount === 'number' && value > max_order_amount) {
    newErrors.value = `Pre-pay amount should not be higher than ${max_order_amount / 100}`;
    isValid = false;
  }

  onErrorFound(newErrors);
  return isValid;
};

export const validateBasicSlab = (
  value: string | undefined,
  type: string,
  onErrorFound: (errors: { type: string; value: string }) => void,
) => {
  let isValid = true;
  const newErrors = { type: '', value: '' };

  const isFalsyValue = value === undefined || value === '' || isNaN(Number(value));
  const numericValue = Number(value);

  if (type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE) {
    if (isFalsyValue) {
      newErrors.value = 'Please enter a value';
      isValid = false;
    } else if (numericValue <= 0 || numericValue > 100) {
      newErrors.value = 'Please enter a valid value';
      isValid = false;
    } else if (numericValue >= 50) {
      newErrors.value = 'Pre-pay amount should not be higher than 50% of order value';
      isValid = false;
    }
  } else if (type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT) {
    if (isFalsyValue) {
      newErrors.value = 'Please enter a value';
      isValid = false;
    } else if (numericValue <= 0) {
      newErrors.value = 'Please enter a valid value';
      isValid = false;
    }
  }

  onErrorFound(newErrors);
  return isValid;
};
