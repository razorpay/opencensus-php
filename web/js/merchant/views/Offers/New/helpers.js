import { rupeesToPaise, deepClone } from 'common/utils/rzp-utils';
import { filterNoCostTenures } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import {
  ALL_PRE_PAID_PAYMENT_METHODS,
  MAX_DISCOUNT,
  OFFER_TYPES,
  PAYMENT_METHODS,
  UPI_APP_PROVIDERS,
} from 'merchant/views/Offers/constants';

export const isGranularPSPOfferEnabled = (payment_method, type) => {
  return payment_method === PAYMENT_METHODS.UPI && type === OFFER_TYPES.Cashback;
};

export const getIinsRegexToTest = (is10DigitBinExperimentEnabled, isSubscription) =>
  isSubscription ? /^\d{6}$/ : is10DigitBinExperimentEnabled ? /^\d{6,10}$/ : /^\d{6}$/;

export const getClubbedOfferRules = (formData) => {
  let isAdditionalOffer = false;

  if (Array.isArray(formData.emi_durations) && formData.emi_durations.length) {
    const lowCostEMISet = new Set((formData.low_cost_emi || []).map((item) => item?.tenure));

    const isDebitCard = formData?.issuer?.toLowerCase()?.includes('_dc');

    const rules = formData.emi_durations.reduce((acc, tenure) => {
      const filters = {
        includes: {
          orders: [
            {
              min_amount: rupeesToPaise(formData.min_amount),
              applies_to: 'total_amount',
              ...(formData.max_order_amount
                ? { max_amount: rupeesToPaise(formData.max_order_amount) }
                : {}),
            },
          ],
          payment_instruments: [
            {
              method: 'emi',
              card: {
                iins: [],
                issuers: [formData.issuer],
                types: [isDebitCard ? 'DEBIT' : 'CREDIT'],
              },
              emi_durations: [tenure],
            },
          ],
        },
      };

      const limit_type = formData.additional_offer_discount_type
        ? formData.additional_offer_discount_type === 2
          ? 'UPTO'
          : 'FLAT'
        : 'FLAT';

      const isLowCostEmi = lowCostEMISet.has(tenure);

      const benefits = [
        {
          offer_type: isLowCostEmi ? 'low_cost_emi' : 'no_cost_emi',
          limit_type: 'FLAT',
          tenures: [tenure],
          unit: 2,
          value: isLowCostEmi
            ? +formData.low_cost_emi.find((item) => item.tenure === tenure).discount_to_avail
                .discount_percentage
            : rupeesToPaise(formData.plan_merchant_payback[tenure]) || 0,
        },
      ];

      if (formData.additional_offer && formData.tenures_applicable.includes(tenure)) {
        isAdditionalOffer = true;
        benefits.push({
          offer_type: formData.additional_offer,
          limit_type,
          unit: formData.additional_offer_discount_type,
          value: rupeesToPaise(formData.flat_cashback || formData.percent_rate),
          ...(formData.percent_rate
            ? { max_discount: rupeesToPaise(formData.max_cashback) || 0 }
            : {}),
        });
      }

      return [
        ...acc,
        {
          filters,
          benefits,
        },
      ];
    }, []);

    return {
      rules,
      isAdditionalOffer,
    };
  }

  return {
    rules: [],
    isAdditionalOffer: false,
  };
};

const clubbedOfferAdditionalFields = [
  'additional_offer',
  'additional_offer_discount_type',
  'max_cashback',
  'flat_cashback',
  'percent_rate',
  'tenures_applicable',
];

export function prepareDataForSubmit(
  formData,
  isLowCostExperimentEnabled,
  isGranularOfferExpEnabled = false,
  isMultiPaymentOfferExperimentEnabled = false,
  is10DigitBinExperimentEnabled = false,
) {
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

  const fieldsToBeDeleted = [
    'discount_type',
    'redemption_type',
    'applicable_on',
    'no_of_cycles',
    'payerAccountTypes',
    'upiApps',
    'upiAppsList',
    'selectedInstruments',
    'plan_merchant_payback',
  ];

  const checkboxFields = ['default_offer', 'block', 'creation_terms_accepted'];

  // backward compatibility with payment_method
  const instruments =
    Array.isArray(transformedFormData.selectedInstruments) &&
    transformedFormData.selectedInstruments.length
      ? transformedFormData.selectedInstruments
          .filter((instrument) => instrument !== ALL_PRE_PAID_PAYMENT_METHODS)
          .map((instrument) => ({ method: instrument }))
      : [
          {
            method: transformedFormData.payment_method,
          },
        ];

  const isSinglePaymentMethodOffer = instruments.length === 1;
  const selectedSingleInstrument = instruments[0]?.method;

  if (isMultiPaymentOfferExperimentEnabled && !isSinglePaymentMethodOffer) {
    transformedFormData.instruments = instruments;
    transformedFormData.payment_method = 'multiple';
  } else {
    transformedFormData.payment_method = selectedSingleInstrument;
  }

  checkboxFields.forEach((field) => {
    transformedFormData[field] = parseInt(formData[field], 10);
  });

  dateFields.forEach((field) => {
    if (formData[field]) {
      // Storing moment object
      transformedFormData[field] = formData[field].unix();
    }
  });

  // Convert rupees to paisa
  amountFields.forEach((field) => {
    transformedFormData[field] = rupeesToPaise(formData[field]);
  });

  //convert comma separated iins to array
  if (formData.iins) {
    const iinRegexToTest = getIinsRegexToTest(
      is10DigitBinExperimentEnabled,
      formData.product_type === 'subscription',
    );

    formData.iins = formData.iins
      .split(',')
      .map((iin) => iin.trim())
      .filter((iin) => iinRegexToTest.test(iin));
    transformedFormData.iins = formData.iins;
  }

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

    const { rules, isAdditionalOffer } = getClubbedOfferRules(formData);

    if (rules.length) {
      if (isAdditionalOffer) {
        transformedFormData.rules = rules;
        transformedFormData.type = 'clubbed';
      }
    }

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

    fieldsToBeDeleted.push(...clubbedOfferAdditionalFields);
  } else {
    fieldsToBeDeleted.push('low_cost_emi');
    fieldsToBeDeleted.push('emi_durations');
    fieldsToBeDeleted.push('merchant_borne_discount');
  }

  // granular checks only applicable for single instrument
  if (!isSinglePaymentMethodOffer || !['card', 'emi'].includes(selectedSingleInstrument)) {
    fieldsToBeDeleted.push('max_payment_count');
  }

  if (formData.payment_method_type === 'both') {
    formData.payment_method_type = '';
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

  // Transform UPI data
  if (
    isSinglePaymentMethodOffer &&
    isGranularOfferExpEnabled &&
    isGranularPSPOfferEnabled(selectedSingleInstrument, formData.type)
  ) {
    const { payerAccountTypes, upiApps, upiAppsList } = transformedFormData;

    transformedFormData.upi = {
      payer_account_type: Array.isArray(payerAccountTypes)
        ? payerAccountTypes
        : [payerAccountTypes],
      apps: upiApps === UPI_APP_PROVIDERS.ALL ? [UPI_APP_PROVIDERS.ALL] : upiAppsList,
    };
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
  if (!val || !val.length) {
    return 'Payment method cannot be empty';
  }
  return false;
};

export function validateDiscountType(val) {
  if (!val) {
    return 'Please select a discount type';
  }
  return false;
}

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

export const validateUPIAppsList = (upiApps, val) => {
  if (upiApps === UPI_APP_PROVIDERS.ALL || val.length) return false;
  return 'UPI apps cannot be empty';
};
export const validatePayerAccountTypes = (val) => {
  if (!val.length) return 'Payer account types apps cannot be empty';
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

export function getEOD() {
  let eod = new Date();
  eod.setHours(23);
  eod.setMinutes(59);

  return eod.getTime();
}
