import type { Fact } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const stringValidator = (value: unknown): boolean | string => {
  if (!Boolean(value)) {
    return 'Value cannot be empty';
  }

  return true;
};

export const numberValidator = (value: unknown): boolean | string => {
  try {
    // using parseFloat to support all numerical values
    // this may include decimal values like `discountPercentage`
    // and integer values such as `orderAmount`
    const num = parseFloat(value as string);

    if (!num) {
      return 'Please enter a number';
    }

    return true;
  } catch (err) {
    return false;
  }
};

export const booleanValidator = (value: unknown): boolean | string => {
  const val = typeof value === 'string' ? value.toLowerCase().trim() : '';
  if (val === 'false' || val === 'true' || val === 'yes' || val === 'no') {
    return true;
  }

  return false;
};

export const validators: Record<Fact['type'], (value: unknown) => boolean | string> = {
  string: stringValidator,
  number: numberValidator,
  boolean: booleanValidator,
};
