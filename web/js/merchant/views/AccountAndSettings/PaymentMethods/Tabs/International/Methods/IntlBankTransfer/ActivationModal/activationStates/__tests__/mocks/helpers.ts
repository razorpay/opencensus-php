import {
  STEPS,
  STEPS_INITIAL_STATE,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates/constants';

import type { ActivationModalState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates/types';

export const getMockPurposeCodeStep = (purposeCode?: string): ActivationModalState =>
  ({
    current: STEPS.PURPOSE_CODE,
    steps: {
      [STEPS.PURPOSE_CODE]: {
        fields: {
          purposeCode: purposeCode || '',
        },
        validationState: STEPS_INITIAL_STATE[STEPS.PURPOSE_CODE].validationState,
      },
    },
  } as ActivationModalState);

export const getMockIecCodeStep = ({
  iecCode,
  iecCodeOption,
  acceptTnc,
  acceptNotApplicableTnc,
}: {
  iecCode?: string;
  iecCodeOption?: string;
  acceptTnc?: string;
  acceptNotApplicableTnc?: string;
}): ActivationModalState =>
  ({
    current: STEPS.IEC_CODE,
    steps: {
      [STEPS.IEC_CODE]: {
        fields: {
          iecCode: iecCode || '',
          iecCodeOption: iecCodeOption || '',
          acceptTnc: acceptTnc || '',
          acceptNotApplicableTnc: acceptNotApplicableTnc || '',
        },
        validationState: STEPS_INITIAL_STATE[STEPS.IEC_CODE].validationState,
      },
    },
  } as ActivationModalState);

export const getMockInternationalDetailsStep = (acceptTnc?: string): ActivationModalState =>
  ({
    current: STEPS.INTERNATIONAL_DETAILS,
    steps: {
      [STEPS.INTERNATIONAL_DETAILS]: {
        fields: {
          acceptTnc: acceptTnc || '',
        },
        validationState: STEPS_INITIAL_STATE[STEPS.INTERNATIONAL_DETAILS].validationState,
      },
    },
  } as ActivationModalState);

export const getMockVideoKycStep = (owner?: string): ActivationModalState =>
  ({
    current: STEPS.VIDEO_KYC,
    steps: {
      [STEPS.VIDEO_KYC]: {
        fields: {
          owner: owner || '',
        },
        validationState: STEPS_INITIAL_STATE[STEPS.VIDEO_KYC].validationState,
      },
    },
  } as ActivationModalState);

export const getMockAllSteps = ({
  current,
  iecCode,
  iecCodeOption,
  acceptTnc,
  acceptNotApplicableTnc,
  owner,
  purposeCode,
}: {
  current: number;
  iecCode?: string;
  iecCodeOption?: string;
  acceptTnc?: string;
  acceptNotApplicableTnc?: string;
  owner?: string;
  purposeCode?: string;
}): ActivationModalState =>
  ({
    current,
    steps: {
      [STEPS.PURPOSE_CODE]: getMockPurposeCodeStep(purposeCode).steps[STEPS.PURPOSE_CODE],
      [STEPS.VIDEO_KYC]: getMockVideoKycStep(owner).steps[STEPS.VIDEO_KYC],
      [STEPS.IEC_CODE]: getMockIecCodeStep({
        iecCode,
        iecCodeOption,
        acceptTnc,
        acceptNotApplicableTnc,
      }).steps[STEPS.IEC_CODE],
      [STEPS.INTERNATIONAL_DETAILS]:
        getMockInternationalDetailsStep(acceptTnc).steps[STEPS.INTERNATIONAL_DETAILS],
    },
  } as ActivationModalState);
