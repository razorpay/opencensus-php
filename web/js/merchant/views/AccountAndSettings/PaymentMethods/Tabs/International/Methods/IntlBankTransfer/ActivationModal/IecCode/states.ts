import {
  useActivationState,
  validateIecCode,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';

import { NOT_APPLICABLE, STEPS } from './constants';

export const useIecCode = () => {
  const iecCode = useActivationState((state) => state.steps[STEPS.IEC_CODE].fields.iecCode);
  const iecCodeOption = useActivationState(
    (state) => state.steps[STEPS.IEC_CODE].fields.iecCodeOption,
  );
  const acceptNotApplicableTnc = useActivationState(
    (state) => state.steps[STEPS.IEC_CODE].fields.acceptNotApplicableTnc,
  );
  const acceptTnc = useActivationState((state) => state.steps[STEPS.IEC_CODE].fields.acceptTnc);
  const validationState = useActivationState(
    (state) => state.steps[STEPS.IEC_CODE].validationState,
  );
  const setStepFields = useActivationState((state) => state.setStepFields);
  const setStepValidationState = useActivationState((state) => state.setStepValidationState);
  const isReadOnly = useActivationState((state) => state.steps[STEPS.IEC_CODE].isReadOnly);

  const handleSelectOption = ({ value }: { value: string }): void => {
    setStepFields(STEPS.IEC_CODE, {
      iecCodeOption: value,
      iecCode: value === NOT_APPLICABLE ? NOT_APPLICABLE : '',
    });

    setStepValidationState(STEPS.IEC_CODE, 'iecCodeOption', {
      state: 'none',
      errorText: '',
    });

    setStepValidationState(STEPS.IEC_CODE, 'iecCode', {
      state: 'none',
      errorText: '',
    });
  };

  const handleValueChange = ({ value }: { value?: string }) => {
    setStepFields(STEPS.IEC_CODE, {
      iecCode: value ?? '',
    });

    if (!validateIecCode(value)) {
      setStepValidationState(STEPS.IEC_CODE, 'iecCode', {
        state: 'error',
        errorText: 'Please enter a valid IEC code',
      });
    } else {
      setStepValidationState(STEPS.IEC_CODE, 'iecCode', {
        state: 'none',
        errorText: '',
      });
    }
  };

  const handleAcceptTnc = ({ isChecked }: { isChecked: boolean }): void => {
    setStepFields(STEPS.IEC_CODE, {
      acceptTnc: isChecked ? 'yes' : '',
    });

    setStepValidationState(STEPS.IEC_CODE, 'acceptTnc', {
      state: 'none',
      errorText: '',
    });
  };

  const handleAcceptNotApplicableTnc = ({ isChecked }: { isChecked: boolean }): void => {
    setStepFields(STEPS.IEC_CODE, {
      acceptNotApplicableTnc: isChecked ? 'yes' : '',
    });

    setStepValidationState(STEPS.IEC_CODE, 'acceptNotApplicableTnc', {
      state: 'none',
      errorText: '',
    });
  };

  return {
    iecCode,
    acceptTnc,
    isReadOnly,
    iecCodeOption,
    validationState,
    acceptNotApplicableTnc,
    handleAcceptTnc,
    handleValueChange,
    handleSelectOption,
    handleAcceptNotApplicableTnc,
  };
};
