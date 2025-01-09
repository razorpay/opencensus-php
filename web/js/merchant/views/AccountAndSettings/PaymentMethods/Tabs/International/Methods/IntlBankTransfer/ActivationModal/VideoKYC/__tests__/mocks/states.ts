import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';

import { STEPS } from '../../constants';

export const setVideoKycState = () => {
  const state = useActivationState.getState();
  useActivationState.setState({
    ...state,
    steps: {
      ...state.steps,
      [STEPS.VIDEO_KYC]: {
        ...state.steps?.[STEPS.VIDEO_KYC],
        fields: {
          owner: '',
          promoterPanName: 'John Doe',
          webLink: 'https://example.com',
        },
      },
    },
  });
};
