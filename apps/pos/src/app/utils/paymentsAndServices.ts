import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import { getComponentFromStep } from 'apps/pos/src/app/utils/modularConfig';
import {
  PaymentMethodFormStringValue,
  PricingStepComponents,
} from 'apps/pos/src/app/types/PaymentsAndService';
import {
  AggregatorModelFormKeys,
  CHARGES_REGEX,
  DirectModelFormKeys,
} from 'apps/pos/src/app/constants/PaymentsAndService';

interface GetStandardPosPricingRatesProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
  componentName:
    | PricingStepComponents.MDR_VAS_RATES_COMPONENT
    | PricingStepComponents.VAS_RATES_COMPONENT;
}

export const getStandardPosPricingRates = ({
  modularConfig,
  componentName,
}: GetStandardPosPricingRatesProps) => {
  const component = getComponentFromStep({
    modularConfig,
    step: 'pricing_step',
    component: componentName,
  });
  const stdRates = component?.meta.defaultValues;
  if (!stdRates) return null;
  const result = {};
  for (const key in stdRates) {
    if (stdRates.hasOwnProperty(key)) {
      result[key] = stdRates[key].toString();
    }
  }
  return Object.keys(result).length ? result : null;
};

interface HasEditedStandardRatesProps {
  stdRates: Record<string, string> | null | undefined;
  currentRates: Record<string, string>;
}
export const hasEditedStandardRates = ({ stdRates, currentRates }: HasEditedStandardRatesProps) => {
  if (!stdRates) return { differences: {}, isRateEdited: false };
  const differences = {};
  let isRateEdited = false;
  for (const key in stdRates) {
    if (stdRates.hasOwnProperty(key) && currentRates.hasOwnProperty(key)) {
      if (stdRates[key] !== currentRates[key]) {
        isRateEdited = true;
        differences[key] = {
          stdRateValue: stdRates[key],
          currentRateValue: currentRates[key],
        };
      }
    }
  }
  return { differences, isStdRateEdited: isRateEdited };
};

export const extractPricingRates = (allFields: Record<string, unknown>) => {
  const rates: Record<string, string> = {};
  [...DirectModelFormKeys, ...AggregatorModelFormKeys].forEach((fieldName: string) => {
    if (typeof allFields[fieldName] === 'string') {
      rates[fieldName] = allFields[fieldName] as string;
    }
  });
  return rates;
};

export const validatePricingRates = (rates: Record<string, string>) => {
  let errFieldName = '';
  for (const fieldName in rates) {
    if (!CHARGES_REGEX.test(rates[fieldName])) {
      errFieldName = fieldName;
      break;
    }
  }
  return { errFieldName };
};

export const replaceEmptyValues = (
  inputObj: Record<string, PaymentMethodFormStringValue>,
  defaultValues,
): Record<string, PaymentMethodFormStringValue> => {
  for (const key in inputObj) {
    if (inputObj.hasOwnProperty(key)) {
      if (
        inputObj[key].value === null ||
        inputObj[key].value === undefined ||
        inputObj[key].value === ''
      ) {
        inputObj[key].value = defaultValues?.[key] ?? '0';
      }
    }
  }
  return inputObj;
};
