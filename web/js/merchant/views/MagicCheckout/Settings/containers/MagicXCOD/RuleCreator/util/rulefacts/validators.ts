import type {
  ValidationResult,
  Fact,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const stringValidator = (value: unknown): boolean | ValidationResult => {
  if (!Boolean(value)) {
    return { valid: false, reasons: ['Value cannot be empty'] };
  }

  return true;
};

export const numberValidator = (value: unknown): boolean | ValidationResult => {
  try {
    // using parseFloat to support all numerical values
    // this may include decimal values like `discountPercentage`
    // and integer values such as `orderAmount`
    const num = parseFloat(value as string);

    if (!num) {
      return { valid: false, reasons: ['Please enter a number'] };
    }

    return true;
  } catch (err) {
    return { valid: false, reasons: [] };
  }
};

export const booleanValidator = (value: unknown): boolean | ValidationResult => {
  const val = typeof value === 'string' ? value.toLowerCase().trim() : '';
  if (val === 'false' || val === 'true' || val === 'yes' || val === 'no') {
    return true;
  }

  return { valid: false, reasons: [] };
};

export const validators: Record<Fact['type'], (value: unknown) => boolean | ValidationResult> = {
  string: stringValidator,
  number: numberValidator,
  boolean: booleanValidator,
};
