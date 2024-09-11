import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import { getComponentFromStep } from 'apps/pos/src/app/utils/modularConfig';
import {
  PaymentMethodFormType,
  PaymentMethodsFieldKeyNames,
  PaymentMethodFormStringValue,
  PricingStepComponents,
} from 'apps/pos/src/app/types/PaymentsAndService';
import {
  AggregatorModelFormKeys,
  CHARGES_REGEX,
  DirectModelFormKeys,
} from 'apps/pos/src/app/constants/PaymentsAndService';
import { trackEvent } from 'apps/pos/src/services/analytics';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  FIELD_TYPES,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
  PAGE_TYPES,
} from 'apps/pos/src/services/analytics/types';

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

interface HandleCheckboxAnalyticsProps {
  key: string;
  modelType: PaymentMethodFormType.DIRECT | PaymentMethodFormType.AGGREGATOR;
}
export const handleCheckboxAnalytics = ({ key, modelType }: HandleCheckboxAnalyticsProps) => {
  if (key === PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD) {
    trackEvent({
      eventName: ANALYTICS_EVENTS.FORM_FIELD,
      action: ANALYTICS_ACTIONS.SELECTED,
      properties: {
        formName: modelType === PaymentMethodFormType.AGGREGATOR ? 'MDR Rates & VAS' : 'VAS Rates',
        fieldName: 'Custom Rates',
        fieldType: FIELD_TYPES.CHECKBOX,
        section: 'Payment Method & Service Selection',
        subSection:
          modelType === PaymentMethodFormType.AGGREGATOR
            ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
            : L2_FUNNEL_STAGE.DIRECT_MODEL,
        l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage:
          modelType === PaymentMethodFormType.AGGREGATOR
            ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
            : L2_FUNNEL_STAGE.DIRECT_MODEL,
      },
    });
  }
};

export const handleFileUploadAnalytics = (
  modelType: PaymentMethodFormType.DIRECT | PaymentMethodFormType.AGGREGATOR,
) => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.LINK,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label: 'Upload - Custom Pricing Proof',
      section: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      subSection:
        modelType === PaymentMethodFormType.AGGREGATOR
          ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
          : L2_FUNNEL_STAGE.DIRECT_MODEL,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage:
        modelType === PaymentMethodFormType.AGGREGATOR
          ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
          : L2_FUNNEL_STAGE.DIRECT_MODEL,
    },
  });
};
export const handleNachFileUploadAnalytics = () => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.LINK,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label: 'Upload - NACH Form',
      section: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      subSection: L2_FUNNEL_STAGE.NACH,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage: L2_FUNNEL_STAGE.NACH,
    },
  });
};

export const handleMdrEditAnalytics = (label: string) => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.LINK,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label,
      l1FunnelStage: L1_FUNNEL_STAGE.AGGREGATOR_MODEL,
      l2FunnelStage: L2_FUNNEL_STAGE.MDR_RATES_AFFORDABILITY_CATEGORY,
      section: 'Aggregator Model',
      subSection: L2_FUNNEL_STAGE.MDR_RATES_AFFORDABILITY_CATEGORY,
    },
  });
};

export const handleVasEditAnalytics = (label: string) => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.LINK,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label,
      l1FunnelStage: L1_FUNNEL_STAGE.DIRECT_MODEL,
      l2FunnelStage: L2_FUNNEL_STAGE.VAS_CATEGORY,
      section: L1_FUNNEL_STAGE.DIRECT_MODEL,
      subSection: L2_FUNNEL_STAGE.VAS_CATEGORY,
    },
  });
};

interface HandleCustomRatesAnalyticsProps {
  key: string;
  modelType: PaymentMethodFormType.DIRECT | PaymentMethodFormType.AGGREGATOR;
}

export const handleCustomRatesAnalytics = ({ key, modelType }: HandleCustomRatesAnalyticsProps) => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.FORM_FIELD_FILL,
    action: ANALYTICS_ACTIONS.INITIATED,
    properties: {
      formName:
        modelType === PaymentMethodFormType.AGGREGATOR ? 'MDR Rates & VAS Rates' : 'VAS Rates',
      fieldName: 'Custom Rates',
      fieldType: FIELD_TYPES.TEXTBOX,
      l1FunnelStage:
        modelType === PaymentMethodFormType.AGGREGATOR
          ? L1_FUNNEL_STAGE.MDR_RATES_AFFORDABILITY_CATEGORY
          : L1_FUNNEL_STAGE.VAS_CATEGORY,
      l2FunnelStage: L2_FUNNEL_STAGE[key.toUpperCase()],
    },
  });
};

export const handleMdrVasFormSubmitAnalytics = (
  modelType: PaymentMethodFormType.DIRECT | PaymentMethodFormType.AGGREGATOR,
) => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label: 'Save & Continue',
      section: 'Payment Method & Service Selection',
      subSection:
        modelType === PaymentMethodFormType.AGGREGATOR
          ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
          : L2_FUNNEL_STAGE.DIRECT_MODEL,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage:
        modelType === PaymentMethodFormType.AGGREGATOR
          ? L2_FUNNEL_STAGE.AGGREGATOR_MODEL
          : L2_FUNNEL_STAGE.DIRECT_MODEL,
    },
  });
};

export const handleNachSubmitAnalytics = () => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label: 'Save & Continue',
      section: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      subSection: L2_FUNNEL_STAGE.NACH,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage: L2_FUNNEL_STAGE.NACH,
    },
  });
};

export const handleNachSkipAnalytics = () => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
    action: ANALYTICS_ACTIONS.CLICKED,
    properties: {
      label: 'Skip & add later',
      section: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      subSection: L2_FUNNEL_STAGE.NACH,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage: L2_FUNNEL_STAGE.NACH,
    },
  });
};

export const handleNachFormViewAnalytics = () => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.FORM_PAGE,
    action: ANALYTICS_ACTIONS.VIEWED,
    properties: {
      formName: 'Nach Form',
      section: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      subSection: L2_FUNNEL_STAGE.ONBOARDING_MODEL,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage: L2_FUNNEL_STAGE.ONBOARDING_MODEL,
    },
  });
  trackEvent({
    eventName: ANALYTICS_EVENTS.PAGE,
    action: ANALYTICS_ACTIONS.VIEWED,
    properties: {
      pageType: PAGE_TYPES.ADDITIONAL_SALES_COMMENTS,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage: L2_FUNNEL_STAGE.ADDITIONAL_SALES_COMMENTS,
    },
  });
};

export const handleNachSalesCommentAnalytics = () => {
  trackEvent({
    eventName: ANALYTICS_EVENTS.FORM_FIELD_FILL,
    action: ANALYTICS_ACTIONS.INITIATED,
    properties: {
      formName: 'Additional Sales Comments',
      fieldName: 'Additional Sales Comments',
      fieldType: FIELD_TYPES.TEXTBOX,
      l1FunnelStage: L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
      l2FunnelStage: L2_FUNNEL_STAGE.ADDITIONAL_SALES_COMMENTS,
    },
  });
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
