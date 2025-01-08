export type ValidationState = {
  state: 'none' | 'error' | undefined;
  errorText: string;
};

type ActivationStep<Fields extends Record<string, string>> = {
  isReadOnly: boolean;
  isCompleted: boolean;
  fields: Fields;
  submitBtnText?: string;
  validationState: {
    [key in keyof Fields]: ValidationState;
  };
};

export type ActivationModalState = {
  current: number;
  isSavingForm: boolean;
  steps: {
    1: ActivationStep<{
      purposeCode: string;
      purposeCodeDesc: string;
    }>;
    2: ActivationStep<{
      iecCodeOption: string;
      iecCode: string;
      acceptNotApplicableTnc: string;
      acceptTnc: string;
    }>;
    3: ActivationStep<{
      purposeCode: string;
      purposeCodeDesc: string;
      iecCode: string;
      acceptTnc: string;
    }>;
    4: ActivationStep<{
      owner: string;
      promoterPanName: string;
      webLink: string;
    }>;
  };
  hiddenSteps: Set<number>;
  validateStep: (step: number) => boolean;
  setCurrent: (current: number, isCompleted?: boolean) => void;
  setStepFields: (step: number, fields: Record<string, string>) => void;
  setStepCompleted: (step: number, isCompleted: boolean) => void;
  setStepReadOnly: (step: number, isReadOnly: boolean) => void;
  setIsSavingForm: (isSavingForm: boolean) => void;
  setStepValidationState: (step: number, key: string, value: ValidationState) => void;
  setStepSubmitBtnText: (step: number, text: string) => void;
  setHiddenSteps: (steps: number[]) => void;
};
