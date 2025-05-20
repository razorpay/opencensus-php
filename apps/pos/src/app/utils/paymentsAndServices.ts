import {
  BrandItem,
  MerchantModularOnboardingDetailsSuccessResponse,
  ModularOnboardingField,
  ModularOnboardingOption,
} from 'apps/pos/src/app/types/modular';
import { getComponentFromStep, getFieldFromComponent } from 'apps/pos/src/app/utils/modularConfig';
import {
  PaymentMethodFormType,
  PaymentMethodsFieldKeyNames,
  PaymentMethodFormStringValue,
  PricingStepComponents,
  MODULAR_PRICING_FIELDS,
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
import { isBrandItem, isStringValue } from './modularTypeResolvers';

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
  if (!stdRates) return { differences: {}, isStdRateEdited: false };
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

interface GetStoreTypes {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export const getStoreTypes = ({ modularConfig }: GetStoreTypes): ModularOnboardingOption[] => {
  if (!modularConfig) return [];
  const brandEmiField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: PricingStepComponents.BRAND_EMI_COMPONENT,
    fieldName: MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
  });
  if (!brandEmiField) return [];
  return brandEmiField.meta?.options ?? [];
};

interface GetBrandNameFields {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}
export const getAllBrandEmiFields = ({
  modularConfig,
}: GetBrandNameFields): ModularOnboardingField[] => {
  if (!modularConfig) return [];
  const brandEmiComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: PricingStepComponents.BRAND_EMI_COMPONENT,
  });
  if (!brandEmiComponent) return [];
  return brandEmiComponent.fields;
};

interface GetBrandEmiField {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
  fieldName: string;
}

export const getBrandEmiField = ({ modularConfig, fieldName }: GetBrandEmiField) => {
  if (!modularConfig) return null;
  return getFieldFromComponent({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: PricingStepComponents.BRAND_EMI_COMPONENT,
    fieldName,
  });
};

export const getBrandEmiCcDcEnabledStatus = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
): boolean => {
  if (!modularConfig) return false;
  let acquisitionModel: PaymentMethodFormType = PaymentMethodFormType.DIRECT;
  const acquisitionModelField = getFieldFromComponent({
    modularConfig,
    component: PricingStepComponents.ACQUISITION_MODEL_COMPONENT,
    fieldName: MODULAR_PRICING_FIELDS.ACQUISITION_MODEL_FIELD,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
  });
  if (!acquisitionModelField) return false;
  if (isStringValue(acquisitionModelField)) {
    acquisitionModel = acquisitionModelField.stringValue as PaymentMethodFormType;
  }
  let pricingComponent =
    acquisitionModel === PaymentMethodFormType.DIRECT
      ? PricingStepComponents.VAS_RATES_COMPONENT
      : PricingStepComponents.MDR_VAS_RATES_COMPONENT;
  const brandEmiCcRateField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: pricingComponent,
    fieldName: PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD,
  });
  const brandEmiDcRateField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: pricingComponent,
    fieldName: PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD,
  });
  if (brandEmiCcRateField && brandEmiDcRateField) {
    //basically, if both fields are disabled, then the brand emi form should be disabled
    return brandEmiCcRateField.isDisabled && brandEmiDcRateField.isDisabled ? false : true;
  }
  return false;
};

interface GetAllAddedBrandsParams {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}
export interface Brand extends BrandItem {
  label: string;
}
export const getAllAddedBrands = ({ modularConfig }: GetAllAddedBrandsParams): Brand[] => {
  const brandDetailsSummaryField = getBrandEmiField({
    modularConfig,
    fieldName: MODULAR_PRICING_FIELDS.BRAND_DETAILS_SUMMARY,
  });
  const brandNameField = getBrandEmiField({
    modularConfig,
    fieldName: MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD,
  });
  const brandEmiComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: PricingStepComponents.BRAND_EMI_COMPONENT,
  });
  const merchantGst = brandEmiComponent?.meta.merchantGstField;
  const allBrands = brandNameField?.meta?.options;
  if (!brandDetailsSummaryField || !allBrands) return [];
  const updatedList = isBrandItem(brandDetailsSummaryField)
    ? brandDetailsSummaryField.addedBrands.map((item) => {
        const option = allBrands.find((option) => option.value === item.name);
        if (option)
          return { ...item, label: option.label, merchantGst: merchantGst ?? item.merchantGst };
        return { ...item, label: item.name, merchantGst: merchantGst ?? item.merchantGst };
      })
    : [];
  return updatedList;
};

export const getSelectedStoreName = (storeTypeField?: ModularOnboardingField): string => {
  if (!storeTypeField) return '';
  const options = storeTypeField.meta?.options;
  const storeType = isStringValue(storeTypeField) ? storeTypeField.stringValue : '';
  return options?.find((option) => option.value === storeType)?.label ?? '';
};

export const updateValuesForUncheckedRates = (formValues: Record<string, unknown>) => {
  const formValuesCopy = { ...formValues };
  // this for loop handles the case where the user has unchecked the checkbox for a rate field eg: if vas_cc_emi_rate_enabled_field is unchecked, then vas_cc_emi_rate_field should be set to null
  for (const key in formValuesCopy) {
    if (key.endsWith('_enabled_field')) {
      const baseKey = key.replace('_enabled_field', '_field');
      if (!formValuesCopy[key] && formValuesCopy.hasOwnProperty(baseKey)) {
        formValuesCopy[baseKey] = null;
      }
    }
  }
  // this part handles unchecking for brand emi and emi plus rate fields
  if (formValuesCopy[PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD] === false) {
    formValuesCopy[PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD] = null;
    formValuesCopy[PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD] = null;
  }
  if (formValuesCopy[PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD] === false) {
    formValuesCopy[PaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD] = null;
    formValuesCopy[PaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD] = null;
  }
  return formValuesCopy;
};

export const parseHtmlString = (input?: string): { from: string; comment: string } => {
  if (!input) return { from: '', comment: '' };
  const index = input.indexOf(':');
  const parser = new DOMParser();
  if (index === -1) {
    const doc = parser.parseFromString(input, 'text/html');
    const comment = doc.body?.textContent?.trim() ?? '';
    return { from: '', comment };
  }
  const doc = parser.parseFromString(input.slice(index + 1).trim(), 'text/html');
  const comment = doc.body?.textContent?.trim() ?? '';
  const from = input.slice(0, index).trim();
  return { from, comment };
};
