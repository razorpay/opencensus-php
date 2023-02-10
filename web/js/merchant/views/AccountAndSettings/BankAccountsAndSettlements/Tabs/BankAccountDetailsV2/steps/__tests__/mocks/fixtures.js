import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import React from 'react';

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form',
  () => ({
    __esModule: true,
    default: ({ setView }) => (
      <>
        <div>Bank Account Form</div>
        <button onClick={() => setView('LOADING_VIEW')}>Change View</button>
      </>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep',
  () => ({
    __esModule: true,
    default: ({ setView, goBack }) => (
      <>
        <div>Loading Step</div>
        <button onClick={() => setView('NEEDS_CLARIFICATION')}>Change View</button>
        <button onClick={goBack}>Go Back</button>
      </>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/NeedsClarification',
  () => ({
    __esModule: true,
    default: () => <div>Needs Clarification Step</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/PennyTestingRetry',
  () => ({
    __esModule: true,
    default: () => <div>Penny Testing Retry Flow</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/UploadProofs',
  () => ({
    __esModule: true,
    default: () => <div>Upload Documents Flow</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError',
  () => ({
    __esModule: true,
    default: () => <div>BVS Input Errors</div>,
  }),
);

export const stepConfigMocks = {
  [BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM]: 'Bank Account Form',
  [BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_RETRY]: 'Penny Testing Retry Flow',
  [BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW]: 'Loading Step',
  [BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_INPUT_ERROR]: 'BVS Input Errors',
  [BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_SUCCESS]: 'Loading Step',
  [BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF]: 'Upload Documents Flow',
  [BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION]: 'Needs Clarification Step',
};
