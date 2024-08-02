import { FieldErrors } from 'react-hook-form';
import {
  MerchantModularOnboardingDetailsSuccessResponse,
  ModularOnboardingField,
} from '../types/modular';
import { FieldRules } from '../typings/MerchantAdditionalDetails';
import { getComponentFromStep } from './modularConfig';
import { isStringValue } from './modularTypeResolvers';

interface GetInitialMerchantAdditionalDetailsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export const getInitialMerchantAdditionalDetails = ({
  modularConfig,
}: GetInitialMerchantAdditionalDetailsProps): Record<string, string> | null => {
  if (!modularConfig) return null;
  const additionalDetailsComponent = getComponentFromStep({
    modularConfig,
    step: 'additional_details_step',
    component: 'additional_details_component',
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
  omcValue: string;
}

export const getFieldRules = ({ field, omcValue }: FieldRulesProps): FieldRules | undefined => {
  if (!field) return;
  if (field.name === 'additional_details_sap_code_field') {
    return omcValue ? { required: true } : { required: false };
  }
  const rules: FieldRules = { required: field?.meta?.validations?.[0].type === 'isRequired' };
  if (field.name === 'additional_details_cashier_mobile_number_field') {
    rules.pattern = /^\d{10}$/;
  }
  return rules;
};

interface FieldErrorTextProps {
  item: ModularOnboardingField;
  errors: FieldErrors<Record<string, string>>;
  omcValue: string;
}
export const getFieldErrorText = ({ item, errors, omcValue }: FieldErrorTextProps) => {
  if (!item) return '';
  let error = item?.meta?.validations?.[0].errorMessage;
  if (
    item.name === 'additional_details_cashier_mobile_number_field' &&
    errors?.additional_details_cashier_mobile_number_field?.type === 'pattern'
  ) {
    error = 'Please enter a valid number';
  }
  if (omcValue && item.name === 'additional_details_sap_code_field') {
    error = 'SAP code is required';
  }
  return error;
};

interface NecessityIndicatorProps {
  field: ModularOnboardingField;
  omcValue: string;
}

export const getNecessityIndicator = ({ field, omcValue }: NecessityIndicatorProps) => {
  if (!field) return 'none';
  if (field.isRequired) return 'required';
  if (field.name === 'additional_details_sap_code_field' && omcValue) {
    return 'required';
  }
  return 'none';
};

interface AdditionalDetailsFieldsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
  omcValue: string;
}

export const getAdditionalDetailFields = ({
  modularConfig,
  omcValue,
}: AdditionalDetailsFieldsProps) => {
  if (!modularConfig) return [];
  const additionalDetailsComponent = getComponentFromStep({
    modularConfig,
    step: 'additional_details_step',
    component: 'additional_details_component',
  });

  if (!omcValue) {
    const filteredFields = additionalDetailsComponent?.fields.filter(
      (field) => field.name !== 'additional_details_sap_code_field',
    );
    return filteredFields;
  }

  return additionalDetailsComponent?.fields;
};
