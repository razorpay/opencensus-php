import { rupeesToPaise } from 'common/utils/rzp-utils';

export function prepareDataForSubmit(formData) {
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

  const checkboxFields = ['default_offer', 'block'];

  checkboxFields.forEach((field) => {
    transformedFormData[field] = parseInt(formData[field]);
  });

  dateFields.forEach((field) => {
    if (formData[field]) {
      return (transformedFormData[field] = formData[field].unix());
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
  } else {
    fieldsToBeDeleted.push('max_order_amount');
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
