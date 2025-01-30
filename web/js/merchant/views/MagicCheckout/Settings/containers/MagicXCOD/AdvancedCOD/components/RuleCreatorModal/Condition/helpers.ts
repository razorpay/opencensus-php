import { RupeeIcon, type TextInputProps } from '@razorpay/blade/components';

import type {
  Condition,
  Fact,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const getTextInputPropsForValue = (
  condition: Condition,
  fact: Fact | undefined,
): Pick<TextInputProps, 'accessibilityLabel' | 'placeholder' | 'suffix' | 'leadingIcon'> => {
  return {
    accessibilityLabel: `Enter ${fact?.label || 'value'}`,
    placeholder: fact?.placeholder || `Enter ${fact?.label || 'value'}`,
    suffix: condition.fact === 'weight' ? 'grams' : undefined,
    leadingIcon:
      condition.fact === 'subtotal' || condition.fact === 'orderAmount' ? RupeeIcon : undefined,
  };
};
