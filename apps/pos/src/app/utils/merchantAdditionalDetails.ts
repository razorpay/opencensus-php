import { FieldErrors } from 'react-hook-form';
import {
  MerchantModularOnboardingDetailsSuccessResponse,
  ModularOnboardingField,
} from 'apps/pos/src/app/types/modular';
import {
  FieldRules,
  MODULAR_ADDITIONAL_DETAILS_FIELDS,
} from 'apps/pos/src/app/types/MerchantAdditionalDetails';
import { getComponentFromStep } from 'apps/pos/src/app/utils/modularConfig';
import { isStringValue } from 'apps/pos/src/app/utils/modularTypeResolvers';

interface GetInitialMerchantAdditionalDetailsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export const getInitialMerchantAdditionalDetails = ({
  modularConfig,
}: GetInitialMerchantAdditionalDetailsProps): Record<string, string> | null => {
  if (!modularConfig) return null;
  const additionalDetailsComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    component: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_COMPONENT,
  });

  if (!additionalDetailsComponent) return null;
  const fields = additionalDetailsComponent.fields;
  const allFieldValues = fields.reduce((acc, field) => {
    if (!acc[field.name]) {
      acc[field.name] = isStringValue(field) ? field.stringValue : '';
    }
    return acc;
  }, {});
  return allFieldValues;
};

export const getInitialMerchantAdditionalDetailsPosEkyc = ({
  modularConfig,
}: GetInitialMerchantAdditionalDetailsProps): Record<string, string> | null => {
  if (!modularConfig) return null;
  const additionalDetailsComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    component: MODULAR_ADDITIONAL_DETAILS_FIELDS.PARTNER_ADDITIONAL_DETAILS_COMPONENT,
  });

  if (!additionalDetailsComponent) return null;
  const fields = additionalDetailsComponent.fields;
  const allFieldValues = fields.reduce((acc, field) => {
    if (!acc[field.name]) {
      acc[field.name] = isStringValue(field) ? field.stringValue : '';
    }
    return acc;
  }, {});
  return allFieldValues;
};

interface FieldRulesProps {
  field: ModularOnboardingField;
  omcValue?: string;
}

export const getFieldRules = ({ field, omcValue }: FieldRulesProps): FieldRules | undefined => {
  if (!field) return;
  if (field.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.SAP_CODE_FIELD) {
    return omcValue ? { required: true } : { required: false };
  }
  const rules: FieldRules = { required: field?.meta?.validations?.[0].type === 'isRequired' };
  if (field.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.PHONE_NUMBER_FIELD) {
    rules.pattern = /^\d{10}$/;
  }
  if (field.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.MANAGER_CASHIER_NAME_FIELD) {
    rules.pattern = /^(?=.*[a-z0-9])[a-z0-9 ]+$/i; //space inside the regex is intentional to allow spaces in name
  }
  return rules;
};

interface FieldErrorTextProps {
  item: ModularOnboardingField;
  errors: FieldErrors<Record<string, string>>;
  omcValue?: string;
}
export const getFieldErrorText = ({ item, errors, omcValue }: FieldErrorTextProps) => {
  if (!item) return '';
  let error = item?.meta?.validations?.[0].errorMessage;
  if (
    item.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.PHONE_NUMBER_FIELD &&
    errors?.[MODULAR_ADDITIONAL_DETAILS_FIELDS.PHONE_NUMBER_FIELD]?.type === 'pattern'
  ) {
    error = 'Please enter a valid number';
  }
  if (
    item.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.MANAGER_CASHIER_NAME_FIELD &&
    errors?.[MODULAR_ADDITIONAL_DETAILS_FIELDS.MANAGER_CASHIER_NAME_FIELD]?.type === 'pattern'
  ) {
    error = 'Please enter a valid name';
  }
  if (omcValue && item.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.SAP_CODE_FIELD) {
    error = 'SAP code is required';
  }
  return error;
};

interface NecessityIndicatorProps {
  field: ModularOnboardingField;
  omcValue?: string;
}

export const getNecessityIndicator = ({ field, omcValue }: NecessityIndicatorProps) => {
  if (!field) return 'none';
  if (field.isRequired) return 'required';
  if (field.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.SAP_CODE_FIELD && omcValue) {
    return 'required';
  }
  return 'none';
};

interface AdditionalDetailsFieldsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
  omcValue?: string;
}

interface AdditionalDetailsFieldsPosEkycProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export const getAdditionalDetailFields = ({
  modularConfig,
  omcValue,
}: AdditionalDetailsFieldsProps) => {
  let additionalDetailsFields: ModularOnboardingField[] = [];

  if (!modularConfig) return additionalDetailsFields;
  const additionalDetailsComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    component: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_COMPONENT,
  });

  additionalDetailsFields = additionalDetailsComponent?.fields ?? [];

  if (!omcValue) {
    const filteredFields = additionalDetailsComponent?.fields.filter(
      (field) => field.name !== MODULAR_ADDITIONAL_DETAILS_FIELDS.SAP_CODE_FIELD,
    );
    additionalDetailsFields = filteredFields ?? [];
  }

  // Get acquirer preference options from meta instead of component meta [this needs to be handled separately]
  const processedFields = additionalDetailsFields.map((field) => {
    if (field.name === MODULAR_ADDITIONAL_DETAILS_FIELDS.ACQUIRER_PREFERENCE_FIELD) {
      const actualAcquirerPreferenceOptions =
        additionalDetailsComponent?.meta.acquirerPreferenceOptions ?? [];

      return {
        ...field,
        meta: {
          ...field.meta,
          options: actualAcquirerPreferenceOptions,
        },
      } as ModularOnboardingField;
    }

    return field;
  });

  return processedFields;
};

export const getAdditionalDetailFieldsPosEkyc = ({
  modularConfig,
}: AdditionalDetailsFieldsPosEkycProps) => {
  if (!modularConfig) return [];
  const additionalDetailsComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    component: MODULAR_ADDITIONAL_DETAILS_FIELDS.PARTNER_ADDITIONAL_DETAILS_COMPONENT,
  });

  return additionalDetailsComponent?.fields;
};

export const trimWhitespace = (obj: Record<string, unknown>) => {
  return Object.fromEntries(
    Object.entries(obj).map(([key, value]) => [
      key,
      typeof value === 'string' ? value.trim() : value,
    ]),
  );
};
