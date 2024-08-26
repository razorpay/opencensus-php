import React, { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from '@razorpay/blade/components';
import { NachFormKeyNames, NachFormObject, NachFormProps } from '../NACHForm/NACHForm';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
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
} from 'apps/pos/src/app/utils/modularTypeResolvers';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import { processFilesForModularSave } from 'apps/pos/src/app/components/SalesFileUpload/helper';
import OnboardingModel from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/OnboardingModel/OnboardingModel';
import {
  getComponentFromStep,
  processFormDataForModularSubmit,
} from 'apps/pos/src/app/utils/modularConfig';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import PageError from 'apps/pos/src/app/components/PageError';
import {
  AggregatorModelForm,
  DirectModelForm,
  PaymentMethodFormType,
  PaymentMethodFormValue,
  PaymentMethodsFieldKeyNames,
  PricingStepComponents,
} from 'apps/pos/src/app/types/PaymentsAndService';
import {
  extractPricingRates,
  getStandardPosPricingRates,
  hasEditedStandardRates,
  replaceEmptyValues,
  validatePricingRates,
} from 'apps/pos/src/app/utils/paymentsAndServices';

const createDefaultForm = (type: PaymentMethodFormType): PaymentMethodForm => {
  const defaultFormValue: PaymentMethodFormValue = {
    checked: true,
    value: 0,
    defaultValue: '0',
    isRequired: true,
    isDisabled: false,
    isHidden: false,
    description: '',
    title: '',
  };

  let tempForm: DirectModelForm | AggregatorModelForm = {
    [PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD]: { ...defaultFormValue },
    [PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD]: { ...defaultFormValue },
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

const getFieldValue = (field, defaultValues: Record<string, number> | undefined) => {
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

const getFieldCheckedStatus = (field, defaultValues: Record<string, number> | undefined) => {
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

const populateFormWithModularConfigData = (
  form: PaymentMethodForm,
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const newForm: PaymentMethodForm = createDefaultForm(form.type);

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
    const fields = component?.fields;
    fields?.forEach((f) => {
      if (f && f.name && f.meta) {
        newForm.form[f.name] = {
          checked: getFieldCheckedStatus(f, defaultValues),
          value: getFieldValue(f, defaultValues),
          defaultValue: defaultValues?.[f.name] || null,
          isRequired: f.isRequired,
          isDisabled: f.isDisabled,
          isHidden: f.isHidden,
          description: f.meta.description,
          title: f.meta.title,
        };
      }
      if (newForm.form[f.name].value === 'false') newForm.form[f.name].value = false;
      if (newForm.form[f.name].value === 'true') newForm.form[f.name].value = true;
    });
  }
  return newForm;
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

const deriveMethodTypeFromModularConfig = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const component = getComponentFromStep({
    modularConfig,
    step: 'pricing_step',
    component: 'acquisition_model_component',
  });
  if (component) {
    const methodType = isStringValue(component?.fields[0]) && component?.fields[0].stringValue;
    return methodType;
  }
  return PaymentMethodFormType.AGGREGATOR;
};

const PaymentMethodContextProvider = ({ component, nach }): JSX.Element => {
  const toast = useToast();
  const navigate = useNavigate();
  const { states, handlers } = useOnboardingContext();
  const { isModularLoading, isRefetching, isUpdateModularLoading, modularConfig, merchantDetails } =
    states;
  const isFormDisabled = !!merchantDetails?.activation?.posActivationStatus;
  const [paymentMethodType, setPaymentMethodType] = useState<PaymentMethodFormType>(
    PaymentMethodFormType.AGGREGATOR,
  );
  const [methodForm, setMethodForm] = useState<PaymentMethodForm>(
    createDefaultForm(paymentMethodType),
  );
  const [nachForm, setNachForm] = useState<NachFormObject>(createDefaultNACHForm());
  const [modelIsOpen, setModelIsOpen] = useState<boolean>(!nach && !isFormDisabled);
  const standardRatesRef = useRef<Record<string, string> | null>();

  const updateConfigHandler = (form) => {
    const payload: any = {};

    Object.keys(form).forEach((k) => {
      if (k !== PaymentMethodsFieldKeyNames.PREVIOUS_CUSTOM_RATES_DOCUMENTS_FIELD) {
        payload[k] = form[k].value;
      }
    });

    const { handleProceedToNextComponent, updateModularConfig } = handlers;
    const redirectToNachPage = (data) => {
      const newNACH = populateNACHFormWithModularConfigData(data);
      setNachForm(newNACH);
      handleProceedToNextComponent();
    };
    payload.modular_callback = redirectToNachPage;

    const pricingRates = extractPricingRates(payload);
    const { errFieldName } = validatePricingRates(pricingRates);
    if (errFieldName) {
      toast.show({
        color: 'negative',
        content: `Please enter valid ${form[errFieldName]?.title} value`,
      });
      return;
    }
    const { isRateEdited } = hasEditedStandardRates({
      stdRates: standardRatesRef.current,
      currentRates: pricingRates,
    });
    const customRateProof = payload[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD];
    const isCustomProofPresent = () => {
      if (customRateProof === '0') return false;
      if (Array.isArray(customRateProof) && customRateProof?.length) return true;
      return false;
    };
    if (isRateEdited && !isCustomProofPresent()) {
      toast.show({
        color: 'negative',
        content: 'Please upload custom pricing proof',
      });
      return;
    }
    const updatedPayload = processFormDataForModularSubmit(payload);
    delete updatedPayload[PaymentMethodsFieldKeyNames.MDR_VAS_PRICING_FIELD];
    if (isFormDisabled) {
      const newNACH = populateNACHFormWithModularConfigData(modularConfig);
      setNachForm(newNACH);
      handleProceedToNextComponent();
      return;
    }
    updateModularConfig(updatedPayload);
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
      modular_callback: (data: MerchantModularOnboardingDetailsSuccessResponse) => {
        setPaymentMethodType(type);
        const newForm = populateFormWithModularConfigData(createDefaultForm(type), data);
        setMethodFormValue('form', newForm.form);
        setMethodFormValue('type', type);
      },
    });
  };

  const onFileUploadChange = (files: FileItem[]) => {
    const newForm = JSON.parse(JSON.stringify(methodForm.form));
    newForm[PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD].value =
      processFilesForModularSave(files);
    setMethodFormValue('form', newForm);
  };

  const onFieldCheckboxChange = (key: string) => {
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
    return newForm;
  };

  const onFieldInputChange = (key: string, value: any) => {
    const newForm = JSON.parse(JSON.stringify(methodForm.form));
    newForm[key].value = value;
    if (!newForm[key].value) {
      newForm[key].checked = false;
    } else {
      newForm[key].checked = true;
    }
    if (
      key === PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD ||
      key === PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD
    ) {
      const updatedForm = autoCheckVasRateEnabledFields(key, newForm);
      setMethodFormValue('form', updatedForm);
      return;
    }
    setMethodFormValue('form', newForm);
  };

  const updateFormValues = () => {
    const { form } = methodForm;
    const newForm = JSON.parse(JSON.stringify(form));
    replaceEmptyValues(newForm, standardRatesRef.current);
    setMethodFormValue('form', newForm);
    return newForm;
  };

  const onFormSubmitClick = () => {
    const updatedForm = updateFormValues();
    updateConfigHandler(updatedForm);
  };

  const onNachTextAreaChange = (event) => {
    setNachForm((prev) => {
      const newNACHForm: NachFormObject = JSON.parse(JSON.stringify(prev));
      newNACHForm[NachFormKeyNames.NACH_FORM_COMMENTS_FIELD] = event.value;
      return newNACHForm;
    });
  };

  const onNachFileUploadChange = (files: FileItem[]) => {
    setNachForm((prev) => {
      const newNACHForm: NachFormObject = JSON.parse(JSON.stringify(prev));
      newNACHForm.nach_form_document_field = processFilesForModularSave(files);
      return newNACHForm;
    });
  };

  const onNachSubmitClick = () => {
    const payload: any = {
      ...nachForm,
    };
    payload[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD] =
      payload[NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD][0];
    const { handleProceedToNextComponent, updateModularConfig } = handlers;
    payload.modular_callback = handleProceedToNextComponent;
    updateModularConfig(payload);
  };

  const onNachSkipClick = () => {
    handlers.handleProceedToNextComponent();
  };

  const shouldRenderComponent = () => {
    if (states.isModularLoading || states.isUpdateModularLoading || modelIsOpen) return false;
    return true;
  };

  const contextValue = {
    // props for form
    isModularLoading,
    methodForm,
    onFileUploadChange,
    onFieldCheckboxChange,
    onFieldInputChange,
    onFormSubmitClick,
    isFormDisabled,

    // props for NACH
    onNachTextAreaChange,
    onNachFileUploadChange,
    onNachSubmitClick,
    onNachSkipClick,
    nachForm,
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
      const newForm = populateFormWithModularConfigData(contextValue.methodForm, modularConfig);
      const initialMethodType = deriveMethodTypeFromModularConfig(
        modularConfig,
      ) as PaymentMethodFormType;
      setPaymentMethodType(initialMethodType);
      const newNach = populateNACHFormWithModularConfigData(modularConfig);
      setNachForm(newNach);
      setMethodFormValue('form', newForm.form);
      setMethodFormValue('type', initialMethodType);
    }
  }, [isModularLoading, isUpdateModularLoading, isRefetching, modularConfig, nach]);

  useEffect(() => {
    if (modularConfig && !standardRatesRef.current) {
      const componentName =
        paymentMethodType === PaymentMethodFormType.AGGREGATOR
          ? PricingStepComponents.MDR_VAS_RATES_COMPONENT
          : PricingStepComponents.VAS_RATES_COMPONENT;
      standardRatesRef.current = getStandardPosPricingRates({
        modularConfig,
        componentName,
      });
    }
  }, [modularConfig]);

  useEffect(() => {
    handlers.refetchModularConfig();
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
      />
      {shouldRenderComponent() ? <RenderComponent {...contextValue} /> : null}
    </>
  );
};

export default PaymentMethodContextProvider;
