import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';
import { STEPS_INITIAL_STATE } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates/constants';

import { STEPS } from '../../constants';

export const setPurposeCodeState = () => {
  const state = useActivationState.getState();
  useActivationState.setState({
    ...state,
    steps: {
      ...state.steps,
      [STEPS.PURPOSE_CODE]: {
        ...state.steps?.[STEPS.PURPOSE_CODE],
        fields: {
          purposeCode: 'P0101',
          purposeCodeDesc: 'P0101 - Purpose Code 1',
        },
      },
    },
  });
};

export const resetActivationState = () => {
  useActivationState.setState({
    steps: STEPS_INITIAL_STATE,
    current: 1,
  });
};
