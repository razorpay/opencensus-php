import Form from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form';
import InputError from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError';
import LoadingStep from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep';
import NeedsClarification from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/NeedsClarification';
import PennyTestingRetry from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/PennyTestingRetry';
import UploadProofs from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/UploadProofs';
import {
  BANK_ACCOUNT_UPDATE_STEPS,
  LOADING_STATE,
  StepsConfigInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React from 'react';

const PennyTestingSuccess = (): JSX.Element => (
  <LoadingStep type={LOADING_STATE.PENNY_TESTING_SUCCESS} />
);

export const stepConfig: StepsConfigInterface = {
  [BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM]: {
    component: Form,
    header: {
      title: 'Add your new bank account details',
    },
  },
  [BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_RETRY]: {
    component: PennyTestingRetry,
    header: {
      close: true,
    },
    isCentered: true,
    minHeight: 642,
  },
  [BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW]: {
    component: LoadingStep,
    header: {
      close: true,
    },
    isCentered: true,
    minHeight: 642,
  },
  [BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_INPUT_ERROR]: {
    component: InputError,
    header: {
      close: true,
    },
    isCentered: true,
    minHeight: 642,
  },
  [BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_SUCCESS]: {
    component: PennyTestingSuccess,
    header: {
      close: true,
    },
    isCentered: true,
    minHeight: 642,
  },
  [BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF]: {
    component: UploadProofs,
    header: {
      title: 'Submit bank account proof for verification',
    },
  },
  [BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION]: {
    component: NeedsClarification,
    header: {
      title: 'Update details as per the instructions below',
    },
  },
};
