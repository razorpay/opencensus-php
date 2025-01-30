import React from 'react';
import { TextInput } from '@razorpay/blade/components';

import { useRuleMutations } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';

import {
  TEXT,
  ACTION_INDEX,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock/constants';

type PaymentParamsProps = {
  validationError: string | undefined;
  defaultValue: string;
};
export const PaymentParams: React.FC<PaymentParamsProps> = ({ defaultValue, validationError }) => {
  const mutations = useRuleMutations();

  return (
    <TextInput
      placeholder={TEXT.PAYMENT_METHODS.PLACEHOLDER}
      accessibilityLabel={TEXT.PAYMENT_METHODS.A11Y_LABEL}
      name="params"
      errorText={validationError}
      validationState={validationError ? 'error' : 'none'}
      defaultValue={defaultValue}
      onChange={({ value = '' }) => {
        mutations.actions.update(
          'params',
          {
            value: value.split(',').map((val) => val.trim()),
          },
          ACTION_INDEX,
        );
      }}
    />
  );
};
