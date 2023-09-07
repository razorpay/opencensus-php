import { ExperimentType } from 'common/splitz/types';
import { DECIMAL_POINT_REGEX } from 'merchant/views/Offers/New/Screens/NoCostEMI/constants';
import {
  EmiPlanType,
  EMI_OFFER_TYPES,
  INPUT_VALIDATION_STATES,
  LowCostOfferType,
  OfferStateType,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/types';

export const isLowCostExperimentEnabled = (experiment: ExperimentType): boolean =>
  experiment.variables.result === 'on';

/**
 * Helper function to get all tenures selected with low cost offer
 */
export const getAllLowCostTenures = (lowCostPayload: LowCostOfferType[]): Array<number> => {
  return lowCostPayload.map((offer) => offer.tenure);
};

/**
 * Helper function to filter out low cost tenure from emi_durations
 * Since to create offers emi_duration should only contain the tenures with no cost emi selected
 */
export const filterNoCostTenures = (
  noCostTenure: Array<number>,
  lowCostPayload: LowCostOfferType[],
): Array<number> => {
  return noCostTenure.filter(
    (duration) => !lowCostPayload.find((offer) => offer.tenure === duration),
  );
};

export const validateDecimalPointValue = (val: string): string => {
  const isValid = new RegExp(DECIMAL_POINT_REGEX).test(val);

  if (!isValid) return 'Please enter number upto 2 decimal points';
  return '';
};

export const isMerchantDiscountValid = (value: number, payback: number): boolean => {
  return Boolean(value && value < payback);
};

/**
 * Validate Merchant borne discount
 */
export const validateMerchantDiscount = (
  value: number,
  plan: EmiPlanType,
): {
  validation: INPUT_VALIDATION_STATES;
  validationText: string;
} => {
  if (!value) {
    return {
      validation: INPUT_VALIDATION_STATES.NONE,
      validationText: '',
    };
  }
  const decimalPointError = validateDecimalPointValue(value.toString());
  if (decimalPointError) {
    return {
      validation: INPUT_VALIDATION_STATES.ERROR,
      validationText: decimalPointError,
    };
  }

  if (+value > +plan.merchant_payback) {
    return {
      validation: INPUT_VALIDATION_STATES.ERROR,
      validationText: `Cannot exceed ${plan.merchant_payback}%`,
    };
  }

  return {
    validation: INPUT_VALIDATION_STATES.NONE,
    validationText: '',
  };
};

/**
 * Helper function to validate if a low cost / no cost offer was selected
 */
export const isOfferTypeAbsent = (offersData: OfferStateType): string | undefined => {
  return Object.keys(offersData).find((offer) => !offersData[offer].offer_type);
};

/**
 * Helper function to validate of low cost offer was selected but payback amount is missing
 */
export const isLowCostAmountMissing = (offersData: OfferStateType): string | undefined => {
  return Object.keys(offersData).find(
    (offer) =>
      offersData[offer].offer_type === EMI_OFFER_TYPES.LOW_COST &&
      (!offersData[offer].merchant_discount || !offersData[offer].valid),
  );
};
