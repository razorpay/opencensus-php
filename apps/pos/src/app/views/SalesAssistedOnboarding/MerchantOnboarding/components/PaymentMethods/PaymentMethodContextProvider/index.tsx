import React, { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
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
  isStringArrayValue,
  isStringValue,
} from 'apps/pos/src/app/utils/modularTypeResolvers';
import { NachFormKeyNames, NachFormObject, NachFormProps } from '../NACHForm/NACHForm';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import { processFilesForModularSave } from 'apps/pos/src/app/components/SalesFileUpload/helper';
import OnboardingModel from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/OnboardingModel/OnboardingModel';
import { useToast } from '@razorpay/blade/components';
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

  let value = isBooleanValue(field)
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

  let value = isBooleanValue(field)
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

  let component = getComponentFromStep({
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
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
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
  const { isModularLoading, isRefetching, isUpdateModularLoading, modularConfig } = states;
  const [paymentMethodType, setPaymentMethodType] = useState<PaymentMethodFormType>(
    PaymentMethodFormType.AGGREGATOR,
  );
  const [methodForm, setMethodForm] = useState<PaymentMethodForm>(
    createDefaultForm(paymentMethodType),
  );
  const [nachForm, setNachForm] = useState<NachFormObject>(createDefaultNACHForm());
  const [modelIsOpen, setModelIsOpen] = useState<boolean>(!nach);
  const standardRatesRef = useRef<Record<string, string> | null>();

  const updateConfigHandler = (form) => {
    const payload: any = {};

    let error = false;

    Object.keys(form).forEach((k) => {
      if (k !== PaymentMethodsFieldKeyNames.PREVIOUS_CUSTOM_RATES_DOCUMENTS_FIELD) {
        payload[k] = form[k].value;
      }
    });

    if (error) return;

    const { handleProceedToNextComponent, updateModularConfig } = handlers;
    payload['modular_callback'] = (data) => {
      const newNACH = populateNACHFormWithModularConfigData(data);
      setNachForm(newNACH);
      handleProceedToNextComponent();
    };

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
    } else {
      if (newForm[key].value === 0) newForm[key].value = Number(newForm[key].defaultValue);
      else newForm[key].value = 0;
    }
    if (newForm[key].value === 0 || newForm[key].value === false) newForm[key].checked = false;
    else newForm[key].checked = true;
    setMethodFormValue('form', newForm);
  };

  const onFieldInputChange = (key: string, value: any) => {
    const newForm = JSON.parse(JSON.stringify(methodForm.form));
    newForm[key].value = value;
    if (!newForm[key].value) {
      newForm[key].checked = false;
    } else {
      newForm[key].checked = true;
    }
    setMethodFormValue('form', newForm);
  };

  const updateFormValues = () => {
    const { form } = methodForm;
    const newForm = JSON.parse(JSON.stringify(form));
    const vasCCField = 'vas_cc_emi_rate_field';
    const vasDCField = 'vas_dc_emi_rate_field';
    if (newForm[vasCCField]?.checked) {
      newForm['vas_cc_emi_rate_enabled_field'].checked = true;
      newForm['vas_cc_emi_rate_enabled_field'].value = true;
    }
    if (newForm[vasDCField]?.checked) {
      newForm['vas_dc_emi_rate_enabled_field'].checked = true;
      newForm['vas_dc_emi_rate_enabled_field'].value = true;
    }
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
    payload['modular_callback'] = handleProceedToNextComponent;
    updateModularConfig(payload);
  };

  const onNachSkipClick = () => {
    handlers.handleProceedToNextComponent();
  };

  let contextValue = {
    // props for form

    methodForm,
    onFileUploadChange,
    onFieldCheckboxChange,
    onFieldInputChange,
    onFormSubmitClick,

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
          setModelIsOpen(true);
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

  return (
    <>
      {states.isModularLoading || states.isUpdateModularLoading ? (
        <KYCRedirectionLoader
          isOpen={states.isModularLoading || states.isUpdateModularLoading}
          message="Loading..."
        />
      ) : (
        <>
          <OnboardingModel
            isOpen={modelIsOpen}
            setIsOpen={setModelIsOpen}
            setFormType={setPaymentMethodTypeHandler}
          />
          {!modelIsOpen ? (
            !states.isModularFetchError ? (
              <RenderComponent {...contextValue} />
            ) : (
              <PageError description="Failed to fetch pricing config. Please try again later" />
            )
          ) : null}
        </>
      )}
    </>
  );
};

export default PaymentMethodContextProvider;
