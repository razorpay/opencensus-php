import { STEPS, NOT_APPLICABLE, IEC_CODE_REGEX, STEPS_INITIAL_STATE } from './constants';
import { ActivationModalState } from './types';

export const validatePurposeCodeStep = (
  state: ActivationModalState,
): { isValid: boolean; state: Partial<ActivationModalState> } => {
  const step = { ...state.steps[STEPS.PURPOSE_CODE] };

  if (!step.fields.purposeCode) {
    return {
      isValid: false,
      state: {
        steps: {
          ...state.steps,
          [STEPS.PURPOSE_CODE]: {
            ...step,
            validationState: {
              ...step.validationState,
              purposeCode: {
                state: 'error',
                errorText: 'Please select a purpose code',
              },
            },
          },
        },
      },
    };
  }

  return {
    isValid: true,
    state: {
      steps: {
        ...state.steps,
        [STEPS.PURPOSE_CODE]: {
          ...step,
          validationState: STEPS_INITIAL_STATE[STEPS.PURPOSE_CODE].validationState,
        },
      },
    },
  };
};

export const validateIecCode = (iecCode: string | undefined): boolean => {
  if (!iecCode) {
    return false;
  }

  if (iecCode === NOT_APPLICABLE) {
    return true;
  }

  return iecCode.length >= 3 ? IEC_CODE_REGEX.test(iecCode) : false;
};

export const validateIecCodeStep = (
  state: ActivationModalState,
): { isValid: boolean; state: Partial<ActivationModalState> } => {
  let isValid = true;
  let step = { ...state.steps[STEPS.IEC_CODE] };

  if (!step.fields.iecCodeOption) {
    return {
      isValid: false,
      state: {
        steps: {
          ...state.steps,
          [STEPS.IEC_CODE]: {
            ...step,
            validationState: {
              ...step.validationState,
              iecCodeOption: {
                state: 'error',
                errorText: 'Please select an option',
              },
            },
          },
        },
      },
    };
  }

  if (!validateIecCode(step.fields.iecCode)) {
    isValid = false;
    step = {
      ...step,
      validationState: {
        ...step.validationState,
        iecCode: {
          state: 'error',
          errorText: 'Please enter a valid IEC code',
        },
      },
    };
  }

  if (step.fields.iecCodeOption === NOT_APPLICABLE && !step.fields.acceptNotApplicableTnc) {
    isValid = false;
    step = {
      ...step,
      validationState: {
        ...step.validationState,
        acceptNotApplicableTnc: {
          state: 'error',
          errorText: 'Please accept the terms and conditions',
        },
      },
    };
  }

  if (!step.fields.acceptTnc) {
    isValid = false;
    step = {
      ...step,
      validationState: {
        ...step.validationState,
        acceptTnc: {
          state: 'error',
          errorText: 'Please accept the terms and conditions',
        },
      },
    };
  }

  if (isValid) {
    step = {
      ...step,
      validationState: STEPS_INITIAL_STATE[STEPS.IEC_CODE].validationState,
    };
  }

  return {
    isValid,
    state: {
      steps: {
        ...state.steps,
        [STEPS.IEC_CODE]: step,
      },
    },
  };
};

export const validateInternationalDetailsStep = (
  state: ActivationModalState,
): { isValid: boolean; state: Partial<ActivationModalState> } => {
  const step = { ...state.steps[STEPS.INTERNATIONAL_DETAILS] };

  if (!step.fields.acceptTnc) {
    return {
      isValid: false,
      state: {
        steps: {
          ...state.steps,
          [STEPS.INTERNATIONAL_DETAILS]: {
            ...step,
            validationState: {
              ...step.validationState,
              acceptTnc: {
                state: 'error',
                errorText: 'Please accept the terms and conditions',
              },
            },
          },
        },
      },
    };
  }

  return {
    isValid: true,
    state: {
      steps: {
        ...state.steps,
        [STEPS.INTERNATIONAL_DETAILS]: {
          ...step,
          validationState: STEPS_INITIAL_STATE[STEPS.INTERNATIONAL_DETAILS].validationState,
        },
      },
    },
  };
};

export const validateVideoKycStep = (
  state: ActivationModalState,
): { isValid: boolean; state: Partial<ActivationModalState> } => {
  const step = { ...state.steps[STEPS.VIDEO_KYC] };

  if (!step.fields.owner) {
    return {
      isValid: false,
      state: {
        steps: {
          ...state.steps,
          [STEPS.VIDEO_KYC]: {
            ...step,
            validationState: {
              ...step.validationState,
              owner: {
                state: 'error',
                errorText: 'Please select an option',
              },
            },
          },
        },
      },
    };
  }

  return {
    isValid: true,
    state: {
      steps: {
        ...state.steps,
        [STEPS.VIDEO_KYC]: {
          ...step,
          validationState: STEPS_INITIAL_STATE[STEPS.VIDEO_KYC].validationState,
        },
      },
    },
  };
};

export const validateSteps = (
  state: ActivationModalState,
): { isValid: boolean; state: Partial<ActivationModalState> } => {
  if (state.current === STEPS.PURPOSE_CODE) {
    return validatePurposeCodeStep(state);
  }

  if (state.current === STEPS.IEC_CODE) {
    return validateIecCodeStep(state);
  }

  if (state.current === STEPS.INTERNATIONAL_DETAILS) {
    return validateInternationalDetailsStep(state);
  }

  if (state.current === STEPS.VIDEO_KYC) {
    return validateVideoKycStep(state);
  }

  return {
    isValid: true,
    state,
  };
};
