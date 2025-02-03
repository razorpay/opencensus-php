import { useEffect } from 'react';

import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';
import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/analytics';

import { STEPS } from './constants';

export const useInternationalDetails = () => {
  const purposeCode = useActivationState(
    (state) => state.steps[STEPS.INTERNATIONAL_DETAILS].fields.purposeCode,
  );
  const purposeCodeDesc = useActivationState(
    (state) => state.steps[STEPS.INTERNATIONAL_DETAILS].fields.purposeCodeDesc,
  );
  const iecCode = useActivationState(
    (state) => state.steps[STEPS.INTERNATIONAL_DETAILS].fields.iecCode,
  );
  const acceptTnc = useActivationState(
    (state) => state.steps[STEPS.INTERNATIONAL_DETAILS].fields.acceptTnc,
  );
  const validationState = useActivationState(
    (state) => state.steps[STEPS.INTERNATIONAL_DETAILS].validationState,
  );
  const setStepFields = useActivationState((state) => state.setStepFields);
  const setStepValidationState = useActivationState((state) => state.setStepValidationState);

  const handleAcceptTnc = ({ isChecked }: { isChecked: boolean }): void => {
    setStepFields(STEPS.INTERNATIONAL_DETAILS, {
      acceptTnc: isChecked ? 'yes' : '',
    });

    setStepValidationState(STEPS.INTERNATIONAL_DETAILS, 'acceptTnc', {
      state: 'none',
      errorText: '',
    });

    track('clicked', { objectName: 'international details accept tnc', isChecked });
  };

  useEffect(() => {
    track('render', { objectName: 'international details step' });
  }, []);

  return {
    iecCode,
    acceptTnc,
    purposeCode,
    purposeCodeDesc,
    validationState,
    handleAcceptTnc,
  };
};
