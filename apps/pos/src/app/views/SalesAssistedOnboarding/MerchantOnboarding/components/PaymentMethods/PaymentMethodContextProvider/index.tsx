import React, { useContext, useEffect, useMemo, useState } from 'react';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';
import { useToast } from '@razorpay/blade/components';
import { NachFormKeyNames, NachFormObject, NachFormProps } from '../NACHForm/NACHForm';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import {
  MerchantModularOnboardingDetailsSuccessResponse,
  ModularOnboardingField,
} from 'apps/pos/src/app/types/modular';
import KYCRedirectionLoader from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantRegistration/KYCRedirectionLoader';
import {
  PaymentMethodForm,
  PaymentMethodFormProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/PaymentMethodForm/PaymentMethodForm';
import {
  isArrayOfDocumentsUpload,
  isBooleanValue,
  isDocumentUpload,
  isStringArrayValue,
  isStringValue,
  isBrandItem,
} from 'apps/pos/src/app/utils/modularTypeResolvers';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import { processFilesForModularSave } from 'apps/pos/src/app/components/SalesFileUpload/helper';
import OnboardingModel from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/OnboardingModel/OnboardingModel';
import {
  getComponentFromStep,
  getFieldFromComponent,
  processFormDataForModularSubmit,
} from 'apps/pos/src/app/utils/modularConfig';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import PageError from 'apps/pos/src/app/components/PageError';
import {
  AggregatorModelForm,
  DirectModelForm,
  MODULAR_PRICING_FIELDS,
  PaymentMethodFormStringValue,
  PaymentMethodFormType,
  PaymentMethodsFieldKeyNames,
  PricingStepComponents,
} from 'apps/pos/src/app/types/PaymentsAndService';
import {
  extractPricingRates,
  getStandardPosPricingRates,
  handleCheckboxAnalytics,
  handleCustomRatesAnalytics,
  handleFileUploadAnalytics,
  handleMdrVasFormSubmitAnalytics,
  handleNachFileUploadAnalytics,
  handleNachSalesCommentAnalytics,
  handleNachSkipAnalytics,
  handleNachSubmitAnalytics,
  hasEditedStandardRates,
  replaceEmptyValues,
  updateValuesForUncheckedRates,
  validatePricingRates,
} from 'apps/pos/src/app/utils/paymentsAndServices';
import { isKycQualified } from 'apps/pos/src/app/utils/merchantActivation';
import {
  AggregatorModelFormKeys,
  CheckboxEnabledFormKeys,
  CustomPricingUploadKeys,
  DirectModelFormKeys,
} from 'apps/pos/src/app/constants/PaymentsAndService';
import { BrandEmiFormData } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/BrandEMIFormContainer';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { SpiltzContext } from 'shell/SpiltzServiceContext';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';

const createDefaultForm = (type: PaymentMethodFormType): PaymentMethodForm => {
  const defaultFormValue: PaymentMethodFormStringValue = {
    checked: true,
    value: '0',
    defaultValue: '0',
    isRequired: true,
    isDisabled: false,
    isHidden: false,
    description: '',
    title: '',
    shouldShowCheckbox: true,
    shouldShowValueInput: true,
  };

  let tempForm: DirectModelForm | AggregatorModelForm = {
    [PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: {
      ...defaultFormValue,
      shouldShowCheckbox: false,
    },
    [PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: {
      ...defaultFormValue,
      shouldShowCheckbox: false,
    },
    [PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: {
      ...defaultFormValue,
      shouldShowCheckbox: false,
    },
    [PaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: {
      ...defaultFormValue,
      shouldShowCheckbox: false,
    },
    [PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD]: {
      checked: true,
      value: [],
      defaultValue: '0',
      isRequired: true,
      isDisabled: false,
      isHidden: false,
      description: '',
      title: '',
      shouldShowCheckbox: false,
      shouldShowValueInput: false,
    },
  };

  if (type === PaymentMethodFormType.AGGREGATOR) {
    tempForm = {
      ...tempForm,
      [PaymentMethodsFieldKeyNames.DEBIT_CARD_RUPAY_MDR_RATE_FIELD]: { ...defaultFormValue },
      [PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD]:
        { ...defaultFormValue },
      [PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD]:
        { ...defaultFormValue },
      [PaymentMethodsFieldKeyNames.CREDIT_CARD_MDR_RATE_FIELD]: { ...defaultFormValue },
      [PaymentMethodsFieldKeyNames.PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD]:
        { ...defaultFormValue },
      [PaymentMethodsFieldKeyNames.UPI_MDR_RATE_FIELD]: { ...defaultFormValue },
    };
  }

  const methodForm: PaymentMethodForm = {
    type,
    form: tempForm,
  };
  return methodForm;
};

interface GetFieldValueProps {
  field: ModularOnboardingField;
  defaultValues: Record<string, number> | undefined;
}
const getFieldValue = ({ field, defaultValues }: GetFieldValueProps) => {
  const handleArrayValue = (f) => {
    if (Array.isArray(f.stringArrayValue) && !f.stringArrayValue.length) return [];
    if (Array.isArray(f.arrayOfDocumentsUploadValue) && !f.arrayOfDocumentsUploadValue.length)
      return [];
    if (isStringArrayValue(f)) return f.stringArrayValue;
    if (isArrayOfDocumentsUpload(f)) return f.arrayOfDocumentsUploadValue;
  };
  const value = isBooleanValue(field)
    ? field.booleanValue
    : (isStringValue(field) && field.stringValue.toString()) ||
      handleArrayValue(field) ||
      defaultValues?.[field.name]?.toString() ||
      '0';
  return value;
};

const getFieldCheckedStatus = ({ field, defaultValues }: GetFieldValueProps) => {
  const handleArrayValue = (f) => {
    if (Array.isArray(f.stringArrayValue) && !f.stringArrayValue.length) return false;
    if (Array.isArray(f.arrayOfDocumentsUploadValue) && !f.arrayOfDocumentsUploadValue.length)
      return false;
    if (isStringArrayValue(f)) return true;
    if (isArrayOfDocumentsUpload(f)) return true;
  };
  const value = isBooleanValue(field)
    ? field.booleanValue
    : (isStringValue(field) && field.stringValue.toString() && true) ||
      handleArrayValue(field) ||
      (defaultValues?.[field.name]?.toString() && true) ||
      false;
  return value;
};

const shouldShowCheckbox = (field: ModularOnboardingField) => {
  const hideCheckboxFields = [
    PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD,
    PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD,
    PaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD,
    PaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD,
  ];
  return !hideCheckboxFields.includes(field.name as PaymentMethodsFieldKeyNames);
};

const shouldShowValueInput = (field: ModularOnboardingField) => {
  const hideValueBoxFields = [
    PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD,
    PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD,
  ];
  return !hideValueBoxFields.includes(field.name as PaymentMethodsFieldKeyNames);
};

const populateFormWithModularConfigData = (
  form: PaymentMethodForm,
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const newForm: PaymentMethodForm = createDefaultForm(form.type);
  const formCopy = { ...newForm, form: { ...newForm.form } };
  const component = getComponentFromStep({
    modularConfig,
    step: 'pricing_step',
    component:
      form.type === PaymentMethodFormType.AGGREGATOR
        ? PricingStepComponents.MDR_VAS_RATES_COMPONENT
        : PricingStepComponents.VAS_RATES_COMPONENT,
  });

  const defaultValues = component?.meta.defaultValues;

  if (component) {
    const formKeys = [
      ...AggregatorModelFormKeys,
      ...DirectModelFormKeys,
      ...CustomPricingUploadKeys,
      ...CheckboxEnabledFormKeys,
    ];
    const fields = component?.fields.filter((field) =>
      formKeys.includes(field.name as PaymentMethodsFieldKeyNames),
    );
    for (const key in formCopy.form) {
      if (formCopy.form.hasOwnProperty(key)) {
        const isKeyPresentInModularConfig = fields.find((item) => item.name === key);
        if (!isKeyPresentInModularConfig) {
          delete formCopy.form[key];
        }
      }
    }
    const getFieldTitle = (key: string) => {
      if (key === PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD) return 'Brand EMI';
      if (key === PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD) return 'EMI Plus';
    };
    fields?.forEach((f) => {
      if (f && f.name && f.meta) {
        formCopy.form[f.name] = {
          checked: getFieldCheckedStatus({ field: f, defaultValues }),
          value: getFieldValue({ field: f, defaultValues }),
          defaultValue: defaultValues?.[f.name] || null,
          isRequired: f.isRequired,
          isDisabled: f.isDisabled,
          isHidden: f.isHidden,
          description: f.meta.description,
          title: f.meta.title || getFieldTitle(f.name),
          shouldShowCheckbox: shouldShowCheckbox(f),
          shouldShowValueInput: shouldShowValueInput(f),
        };
      }
      if (formCopy.form[f.name].value === 'false') formCopy.form[f.name].value = false;
      if (formCopy.form[f.name].value === 'true') formCopy.form[f.name].value = true;
    });
  }
  return formCopy;
};

const createDefaultBrandEmiForm = (): BrandEmiFormData => {
  return {
    [MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD]: '',
    [MODULAR_PRICING_FIELDS.BRAND_DETAILS_FIELD]: [],
  };
};
const createDefaultNACHForm = (): NachFormObject => {
  return {
    [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: '',
    [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: [],
  };
};

const populateNACHFormWithModularConfigData = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
) => {
  const newForm: NachFormObject = createDefaultNACHForm();

  const component = getComponentFromStep({
    modularConfig,
    step: 'pricing_step',
    component: 'nach_form_component',
  });
  if (component) {
    const fields = component?.fields;

    fields?.forEach((f) => {
      if (f && f.name && f.meta) {
        if (f.name === NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD) {
          if (isStringArrayValue(f)) newForm[f.name] = f.stringArrayValue;
          else if (isArrayOfDocumentsUpload(f)) {
            newForm[f.name] = f.arrayOfDocumentsUploadValue;
          } else if (isDocumentUpload(f)) {
            newForm[f.name] = [f.documentUploadValue];
          }
        } else if (f.name === NachFormKeyNames.NACH_FORM_COMMENTS_FIELD && isStringValue(f))
          newForm[f.name] = f.stringValue;
      }
    });
  }
  return newForm;
};

const populateBrandEmiFormWithModularConfigData = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const defaultForm = createDefaultBrandEmiForm();
  const storeTypeField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: PricingStepComponents.BRAND_EMI_COMPONENT,
    fieldName: MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD,
  });
  const brandDetailsSummaryField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_PRICING_FIELDS.PRICING_STEP,
    component: PricingStepComponents.BRAND_EMI_COMPONENT,
    fieldName: MODULAR_PRICING_FIELDS.BRAND_DETAILS_SUMMARY,
  });
  defaultForm.brand_details_field = isBrandItem(brandDetailsSummaryField)
    ? brandDetailsSummaryField.addedBrands
    : [];
  defaultForm.store_type_field = isStringValue(storeTypeField) ? storeTypeField.stringValue : '';
  return defaultForm;
};

const deriveMethodTypeFromModularConfig = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
): {
  paymentMethodType: PaymentMethodFormType;
  aquisitionModelFields: ModularOnboardingField | null;
} => {
  const component = getComponentFromStep({
    modularConfig,
    step: 'pricing_step',
    component: 'acquisition_model_component',
  });
  if (component) {
    const methodType =
      isStringValue(component?.fields[0]) &&
      (component?.fields[0].stringValue as PaymentMethodFormType);
    const aquisitionModelFields = component?.fields[0];
    return {
      paymentMethodType: methodType || PaymentMethodFormType.AGGREGATOR,
      aquisitionModelFields,
    };
  }
  return { paymentMethodType: PaymentMethodFormType.AGGREGATOR, aquisitionModelFields: null };
};

const PaymentMethodContextProvider = ({ component, nach, brandEmi, addedBrands }): JSX.Element => {
  const toast = useToast();
  const navigate = useNavigate();
  const splitz = useContext(SpiltzContext);
  const isBrandEmiEnabled = splitz.abExperiments?.pos_brand_emi?.variables?.result === 'on';
  const { states, handlers } = useOnboardingContext();
  const { isModularLoading, isRefetching, isUpdateModularLoading, modularConfig, merchantDetails } =
    states;
  const isFormDisabled = isKycQualified(merchantDetails?.activation?.posActivationStatus);

  const [paymentMethodType, setPaymentMethodType] = useState<PaymentMethodFormType>(
    PaymentMethodFormType.AGGREGATOR,
  );
  const [aquisitionModelFields, setAquisitionModelFields] = useState<ModularOnboardingField | null>(
    null,
  );
  const [methodForm, setMethodForm] = useState<PaymentMethodForm>(
    createDefaultForm(paymentMethodType),
  );
  const [nachForm, setNachForm] = useState<NachFormObject>(createDefaultNACHForm());
  const [brandEmiForm, setBrandEmiForm] = useState<BrandEmiFormData>(createDefaultBrandEmiForm());
  const [modelIsOpen, setModelIsOpen] = useState<boolean>(!nach && !isFormDisabled);
  const standardRates = useMemo(() => {
    if (modularConfig) {
      const componentName =
        paymentMethodType === PaymentMethodFormType.AGGREGATOR
          ? PricingStepComponents.MDR_VAS_RATES_COMPONENT
          : PricingStepComponents.VAS_RATES_COMPONENT;
      return getStandardPosPricingRates({
        modularConfig,
        componentName,
      });
    }
  }, [modularConfig, paymentMethodType]);

  const updateConfigHandler = (form) => {
    const payload: any = {};

    Object.keys(form).forEach((k) => {
      if (k !== PaymentMethodsFieldKeyNames.PREVIOUS_CUSTOM_RATES_DOCUMENTS_FIELD) {
        payload[k] = form[k].value;
      }
    });

    const { handleProceedToNextComponent, updateModularConfig } = handlers;

    const redirectToNextPage = (data) => {
      const newNACH = populateNACHFormWithModularConfigData(data);
      setNachForm(newNACH);
      if (!methodForm.form[PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD].checked) {
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.NACH_FORM]: true,
          },
        });
        return;
      }
      if (
        methodForm.form[PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD].checked &&
        !brandEmiForm.brand_details_field.length
      ) {
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.BRAND_EMI_FORM]: true,
          },
        });
      } else if (
        methodForm.form[PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD].checked &&
        brandEmiForm.brand_details_field.length
      ) {
        handleProceedToNextComponent({
          __typeName: 'custom_routing',
          routerConditions: {
            [AvailableComponents.NACH_FORM]: true,
          },
        });
      }
    };

    payload.modular_callback = redirectToNextPage;
    payload[MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD] = moment().unix();

    const pricingRates = extractPricingRates(payload);
    const { errFieldName } = validatePricingRates(pricingRates);
    if (errFieldName) {
      toast.show({
        color: 'negative',
        content: `Please enter valid ${form[errFieldName]?.title} value`,
      });
      return;
    }
    const { isStdRateEdited } = hasEditedStandardRates({
      stdRates: standardRates,
      currentRates: pricingRates,
    });
    const customRateProof = payload[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD];
    const isCustomProofPresent = () => {
      if (customRateProof === '0') return false;
      if (Array.isArray(customRateProof) && customRateProof?.length) return true;
      return false;
    };
    if (isStdRateEdited && !isCustomProofPresent()) {
      toast.show({
        color: 'negative',
        content: 'Please upload custom pricing proof',
      });
      return;
    }
    const updatedPayload = processFormDataForModularSubmit(payload);
    updatedPayload[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD] =
      processFilesForModularSave(
        updatedPayload[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD] as FileItem[],
      );
    delete updatedPayload[PaymentMethodsFieldKeyNames.MDR_VAS_PRICING_FIELD];
    delete updatedPayload[PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD];

    if (isFormDisabled) {
      const newNACH = populateNACHFormWithModularConfigData(modularConfig);
      setNachForm(newNACH);
      handleProceedToNextComponent({
        __typeName: 'custom_routing',
        routerConditions: {
          [AvailableComponents.NACH_FORM]: true,
        },
      });
      return;
    }
    const modifiedPayload = updateValuesForUncheckedRates(updatedPayload);
    updateModularConfig(modifiedPayload);
  };

  const setMethodFormValue = (
    key: string,
    value: DirectModelForm | AggregatorModelForm | PaymentMethodFormType,
  ) => {
    setMethodForm((prev: PaymentMethodForm) => {
      const newMethodForm = JSON.parse(JSON.stringify(prev));
      newMethodForm[key] = value;
      return newMethodForm;
    });
  };

  const setPaymentMethodTypeHandler = (type: PaymentMethodFormType) => {
    handlers.updateModularConfig({
      acquisition_model_field: type,
      [MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD]: moment().unix(),
      modular_callback: (data: MerchantModularOnboardingDetailsSuccessResponse) => {
        setPaymentMethodType(type);
        const newForm = populateFormWithModularConfigData(createDefaultForm(type), data);
        setMethodFormValue('form', newForm.form);
        setMethodFormValue('type', type);
      },
    });
  };

  const onFileUploadChange = (files: FileItem[]) => {
    handleFileUploadAnalytics(paymentMethodType);
    const newForm = JSON.parse(JSON.stringify(methodForm.form));
    newForm[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value = files;
    setMethodFormValue('form', newForm);
  };

  const onFieldCheckboxChange = (key: string) => {
    handleCheckboxAnalytics({ key, modelType: paymentMethodType });
    const newForm = JSON.parse(JSON.stringify(methodForm.form));
    if (typeof newForm[key].value === 'boolean') {
      newForm[key].value = !newForm[key].value;
      newForm[key].checked = !newForm[key].checked;
    }
    setMethodFormValue('form', newForm);
  };

  const autoCheckVasRateEnabledFields = (key, form) => {
    const newForm = JSON.parse(JSON.stringify(form));
    if (key === PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD) {
      newForm[PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_ENABLED_FIELD].checked = true;
      newForm[PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_ENABLED_FIELD].value = true;
    }
    if (key === PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD) {
      newForm[PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_ENABLED_FIELD].checked = true;
      newForm[PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_ENABLED_FIELD].value = true;
    }
    if (
      key === PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD
    ) {
      newForm[PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD].checked = true;
      newForm[PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD].value = true;
    }
    if (
      key === PaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD
    ) {
      newForm[PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD].checked = true;
      newForm[PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD].value = true;
    }
    return newForm;
  };

  const onFieldInputChange = (key: string, value: any) => {
    handleCustomRatesAnalytics({ key, modelType: paymentMethodType });
    const newForm = JSON.parse(JSON.stringify(methodForm.form));
    newForm[key].value = value;
    if (!newForm[key].value) {
      newForm[key].checked = false;
    } else {
      newForm[key].checked = true;
    }
    if (
      key === PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD
    ) {
      const updatedForm = autoCheckVasRateEnabledFields(key, newForm);
      setMethodFormValue('form', updatedForm);
      return;
    }
    setMethodFormValue('form', newForm);
  };

  const onBrandEmiFieldInputChange = (key: string, value: any) => {
    if (key === MODULAR_PRICING_FIELDS.STORE_TYPE_FIELD) {
      setBrandEmiForm((prev) => ({ ...prev, store_type_field: value }));
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD,
        action: analyticsTypes.ANALYTICS_ACTIONS.SELECTED,
        properties: {
          formName: 'Brand EMI Form',
          fieldName: analyticsTypes.L2_FUNNEL_STAGE.TYPE_OF_STORE,
          fieldType: analyticsTypes.FIELD_TYPES.DROPDOWN,
          section: analyticsTypes.L1_FUNNEL_STAGE.VALUE_ADDED_SERVICES,
          subSection: 'Brand Information Form',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.VALUE_ADDED_SERVICES,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TYPE_OF_STORE,
        },
      });
    }
  };

  const updateFormValues = () => {
    const { form } = methodForm;
    const newForm = JSON.parse(JSON.stringify(form));
    replaceEmptyValues(newForm, standardRates);
    setMethodFormValue('form', newForm);
    return newForm;
  };

  const onFormSubmitClick = () => {
    handleMdrVasFormSubmitAnalytics(paymentMethodType);
    const updatedForm = updateFormValues();
    updateConfigHandler(updatedForm);
  };

  const onNachTextAreaChange = (event) => {
    handleNachSalesCommentAnalytics();
    setNachForm((prev) => {
      const newNACHForm: NachFormObject = JSON.parse(JSON.stringify(prev));
      newNACHForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD] = event.value;
      return newNACHForm;
    });
  };

  const onNachFileUploadChange = (files: FileItem[]) => {
    handleNachFileUploadAnalytics();
    setNachForm((prev) => {
      const newNACHForm: NachFormObject = JSON.parse(JSON.stringify(prev));
      newNACHForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD] = files;
      return newNACHForm;
    });
  };

  const onNachSubmitClick = () => {
    handleNachSubmitAnalytics();
    const payload: any = {
      ...nachForm,
    };
    payload[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD] = processFilesForModularSave(
      nachForm[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD],
    )[0];
    const { handleProceedToNextComponent, updateModularConfig } = handlers;
    payload.modular_callback = () => {
      setTimeout(() => {
        handleProceedToNextComponent();
      }, 500);
    };
    updateModularConfig(payload);
  };

  const onNachSkipClick = () => {
    handleNachSkipAnalytics();
    handlers.handleProceedToNextComponent();
  };

  const shouldRenderComponent = () => {
    if (states.isModularLoading || states.isUpdateModularLoading || modelIsOpen) return false;
    return true;
  };

  const handleViewBrandEMIForm = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'View Brand EMI Form',
        section: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        subSection: analyticsTypes.L2_FUNNEL_STAGE.ONBOARDING_MODEL,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.ONBOARDING_MODEL,
      },
    });
    const { handleProceedToNextComponent, updateModularConfig } = handlers;
    if (isFormDisabled) {
      handleProceedToNextComponent({
        __typeName: 'custom_routing',
        routerConditions: {
          [AvailableComponents.ADDED_BRAND_INFO]: true,
        },
      });
      return;
    }
    updateModularConfig({
      [MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD]: moment().unix(),
      [MODULAR_PRICING_FIELDS.MODULAR_CALLBACK]: handleProceedToNextComponent({
        __typeName: 'custom_routing',
        routerConditions: {
          [AvailableComponents.ADDED_BRAND_INFO]: true,
        },
      }),
    });
  };
  const contextValue = {
    // props for form
    isBrandEmiEnabled,
    isModularLoading,
    methodForm,
    onFileUploadChange,
    onFieldCheckboxChange,
    onFieldInputChange,
    onFormSubmitClick,
    isFormDisabled,
    handleViewBrandEMIForm,
    hasAddedBrandEMIData: !!brandEmiForm.brand_details_field.length,

    // props for NACH
    onNachTextAreaChange,
    onNachFileUploadChange,
    onNachSubmitClick,
    onNachSkipClick,
    nachForm,

    //props for brand emi
    brandEmiForm,
    onBrandEmiFieldInputChange,
  };

  useEffect(() => {
    if (
      !states.isModularLoading &&
      !states.isUpdateModularLoading &&
      !states.isRefetching &&
      modularConfig
    ) {
      if (nach) {
        if (
          !getComponentFromStep({
            modularConfig,
            step: 'pricing_step',
            component: 'nach_form_component',
          })
        ) {
          const stepObj = handlers.getStepConfigStepSlug();
          if (!isFormDisabled) {
            setModelIsOpen(true);
          }
          navigate(
            `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${states.merchantDetails?.id}/${stepObj?.slug}/${stepObj?.components[0].slug}`,
          );
          return;
        }
      }
      if (brandEmi || addedBrands) {
        setModelIsOpen(false);
      }
      const newForm = populateFormWithModularConfigData(contextValue.methodForm, modularConfig);

      const { paymentMethodType: initialMethodType, aquisitionModelFields } =
        deriveMethodTypeFromModularConfig(modularConfig);

      if (initialMethodType) {
        setPaymentMethodType(initialMethodType);
        setAquisitionModelFields(aquisitionModelFields);
      }
      const newNach = populateNACHFormWithModularConfigData(modularConfig);
      setNachForm(newNach);
      setMethodFormValue('form', newForm.form);
      setMethodFormValue('type', initialMethodType);
      const newBrandEmiForm = populateBrandEmiFormWithModularConfigData(modularConfig);
      setBrandEmiForm(newBrandEmiForm);
    }
  }, [isModularLoading, isUpdateModularLoading, isRefetching, modularConfig, nach, brandEmi]);

  useEffect(() => {
    handlers.refetchModularConfig();
  }, []);

  useEffect(() => {
    const data = createDefaultBrandEmiForm();
    setMethodForm((prev) => ({ ...prev, ...data }));
  }, []);

  const RenderComponent: React.FC<PaymentMethodFormProps | NachFormProps> = component;

  if (states.isModularFetchError) {
    return <PageError description="Failed to fetch pricing config. Please try again later" />;
  }

  return (
    <>
      <KYCRedirectionLoader
        isOpen={states.isModularLoading || states.isUpdateModularLoading}
        message="Loading..."
      />
      <OnboardingModel
        isOpen={modelIsOpen}
        setIsOpen={setModelIsOpen}
        setFormType={setPaymentMethodTypeHandler}
        acquisitionModelField={paymentMethodType}
        acquisitionModelFields={aquisitionModelFields}
      />
      {shouldRenderComponent() ? <RenderComponent {...contextValue} /> : null}
    </>
  );
};

export default PaymentMethodContextProvider;
