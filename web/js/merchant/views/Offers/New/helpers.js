import { rupeesToPaise, deepClone } from 'common/utils/rzp-utils';
import { filterNoCostTenures } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import { MAX_DISCOUNT } from 'merchant/views/Offers/constants';

export function prepareDataForSubmit(formData, isLowCostExperimentEnabled) {
  const transformedFormData = {
    ...formData,
  };

  const amountFields = [
    'max_cashback',
    'flat_cashback',
    'min_amount',
    'percent_rate',
    'max_order_amount',
  ];

  const dateFields = ['starts_at', 'ends_at'];

  const fieldsToBeDeletedIfDataNull = [
    'payment_method_type',
    'issuer',
    'payment_network',
    'max_payment_count',
    'iins',
    'max_offer_usage',
    'max_order_amount',
    'min_amount',
    'default_offer',
    'starts_at',
    'ends_at',
  ];

  const fieldsToBeDeleted = ['discount_type', 'redemption_type', 'applicable_on', 'no_of_cycles'];

  const checkboxFields = ['default_offer', 'block', 'creation_terms_accepted'];

  checkboxFields.forEach((field) => {
    transformedFormData[field] = parseInt(formData[field], 10);
  });

  dateFields.forEach((field) => {
    if (formData[field]) {
      transformedFormData[field] = formData[field].unix();
    }
  });

  // Convert rupees to paisa
  amountFields.forEach((field) => {
    transformedFormData[field] = rupeesToPaise(formData[field]);
  });

  // get additional fields to be deleted based on the discount_type
  if (transformedFormData.discount_type === 'flat') {
    fieldsToBeDeleted.push('max_cashback');
    fieldsToBeDeleted.push('percent_rate');
  }

  if (transformedFormData.discount_type === 'percent') {
    fieldsToBeDeleted.push('flat_cashback');
  }

  if (transformedFormData.discount_type === 'no_cost_emi') {
    fieldsToBeDeleted.push('flat_cashback');
    fieldsToBeDeleted.push('max_cashback');
    fieldsToBeDeleted.push('percent_rate');
    fieldsToBeDeleted.push('payment_network');

    transformedFormData.emi_subvention = 1;
    transformedFormData.payment_method = 'emi';

    if (isLowCostExperimentEnabled && formData.low_cost_emi && formData.low_cost_emi.length) {
      // check if emi tenure is selected for low cost offer
      // if yes remove it from emi_durations
      // since emi durations will only include tenures for no cost offer
      const formattedTenures = filterNoCostTenures(formData.emi_durations, formData.low_cost_emi);
      transformedFormData.low_cost_emi = formData.low_cost_emi;
      transformedFormData.emi_durations = formattedTenures;
      fieldsToBeDeleted.push('merchant_borne_discount');
    } else {
      fieldsToBeDeleted.push('low_cost_emi');
      fieldsToBeDeleted.push('merchant_borne_discount');
    }
  } else {
    fieldsToBeDeleted.push('low_cost_emi');
    fieldsToBeDeleted.push('emi_durations');
    fieldsToBeDeleted.push('merchant_borne_discount');
  }

  if (!['card', 'emi'].includes(formData.payment_method)) {
    fieldsToBeDeleted.push('max_payment_count');
  }

  if (formData.product_type === 'subscription') {
    transformedFormData.subscription = {
      redemption_type: transformedFormData.redemption_type,
      applicable_on: transformedFormData.applicable_on,
    };

    if (transformedFormData.no_of_cycles) {
      transformedFormData.subscription.no_of_cycles = transformedFormData.no_of_cycles;
    }
  }

  fieldsToBeDeletedIfDataNull.forEach((field) => {
    const isDataAvl = !!formData[field];
    if (!isDataAvl) {
      fieldsToBeDeleted.push(field);

      return;
    }

    const isIinsFiledEmpty = field === 'iins' && formData[field].length === 0;

    if (isIinsFiledEmpty) {
      fieldsToBeDeleted.push(field);
    }
  });
  fieldsToBeDeleted.push('creation_terms_accepted');

  // fields to be deleted
  fieldsToBeDeleted.forEach((field) => {
    if (field in transformedFormData) {
      delete transformedFormData[field];
    }
  });

  const issuers = ['AMEX', 'BAJAJ'];
  if (issuers.includes(transformedFormData.issuer)) {
    transformedFormData.payment_network = transformedFormData.issuer;
    delete transformedFormData.issuer;
  }

  return transformedFormData;
}

export const validatePaymentMethod = (val) => {
  if (!val) {
    return 'Payment method cannot be empty';
  }
  return false;
};

export const validateMaxPaymentCount = (val) => {
  if (!val) return false;

  if (!new RegExp('^[0-9]+$').test(val)) {
    return 'Please enter a number';
  }

  val = parseFloat(val);
  // Converting to value entered in RS to Paise for proper validation
  val = rupeesToPaise(val);
  if (val > MAX_DISCOUNT) {
    return `Maximum value allowed is ${MAX_DISCOUNT}`;
  }

  return false;
};

export const emiDurationString = (emiDurations) => {
  const durations = deepClone(emiDurations);

  let lastDurationString = ' months';
  if (durations.length > 1) {
    lastDurationString = ` and ${durations.pop()}${lastDurationString}`;
  }

  return durations.join(', ') + lastDurationString;
};
