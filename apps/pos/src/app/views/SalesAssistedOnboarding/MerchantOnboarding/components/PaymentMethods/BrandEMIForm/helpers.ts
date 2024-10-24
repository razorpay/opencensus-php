import { ModularOnboardingField } from 'apps/pos/src/app/types/modular';
import { FieldErrors } from 'react-hook-form';

interface FieldErrorTextProps {
  item: ModularOnboardingField;
  errors: FieldErrors<Record<string, string>>;
}
export const getFieldErrorText = ({ item, errors }: FieldErrorTextProps): string => {
  if (!item) return '';
  let error = 'Please enter a code';
  if (item.meta?.dataType === 'string' && errors?.[item.name]?.type === 'pattern') {
    error = 'Please enter a valid code';
  }
  return error;
};

export const getFieldRules = (fieldName: string, brandRelatedFields: ModularOnboardingField[]) => {
  const isRelatedField = brandRelatedFields.find((field) => field.name === fieldName);
  if (isRelatedField) return { required: true, pattern: /^[a-zA-Z0-9]+$/ };
  return { required: false };
};
