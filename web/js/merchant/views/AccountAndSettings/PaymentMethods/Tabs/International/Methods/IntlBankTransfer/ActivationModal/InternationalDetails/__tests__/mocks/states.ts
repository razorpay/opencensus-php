import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';

import { STEPS } from '../../constants';

export const setPurposeCodeAndIecCode = () => {
  const state = useActivationState.getState();
  useActivationState.setState({
    ...state,
    steps: {
      ...state.steps,
      [STEPS.INTERNATIONAL_DETAILS]: {
        ...state.steps?.[STEPS.INTERNATIONAL_DETAILS],
        fields: {
          purposeCode: 'TEST_PURPOSE_CODE',
          iecCode: 'TEST_IEC_CODE',
          purposeCodeDesc: 'TEST_PURPOSE_CODE_DESC',
          acceptTnc: '',
        },
      },
    },
  });
};
