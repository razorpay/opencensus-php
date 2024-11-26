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

interface BrandFieldRules {
  fieldName: string;
  brandEmiFields: ModularOnboardingField[];
  optionalBrandFields: ModularOnboardingField[];
}
export const getFieldRules = ({
  fieldName,
  brandEmiFields,
  optionalBrandFields,
}: BrandFieldRules) => {
  //if a field present in brandemifields is also present in optionalfuelds array, then it is an optional field.
  const isBrandEmiField = brandEmiFields.find((field) => field.name === fieldName);
  let isOptionalField: ModularOnboardingField | null = null;
  if (optionalBrandFields?.length) {
    isOptionalField =
      optionalBrandFields.find((field) => field.name === isBrandEmiField?.name) ?? null;
  }
  if (isOptionalField) return { required: false, pattern: /^[a-zA-Z0-9]+$/ };
  if (isBrandEmiField) return { required: true, pattern: /^[a-zA-Z0-9]+$/ };
  return { required: false };
};
