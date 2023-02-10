import { formInitState } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/constants';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import {
  saveBankAccountSuccess,
  saveBankAccountSuccessBvsInputError,
  saveBankAccountSuccessPennyTestFail,
  saveBankAccountSuccessTimeout,
} from './handlers';

export const validDataState = {
  ...formInitState,
  inputData: {
    account_number: '1234567890',
    account_number_confirmation: '1234567890',
    ifsc_code: 'HDFC0000009',
    beneficiary_name: 'Rzp Test QA Merchant',
  },
};

export const promoter_pan = 'FOEPS1199P';
export const company_pan = 'GOEPS1199S';

export const INPUT_FIELD_TO_DATA = {
  ACCOUNT_NUMBER: {
    name: 'Account Number',
    value: '123456',
    error: 'Enter a 9-18 digit bank account number only',
  },
  ACCOUNT_NUMBER_CONFIRMATION: {
    name: 'Confirm account number',
    value: '123456',
    error: 'Account number does not match',
  },
  IFSC_CODE: {
    name: 'Branch IFSC code',
    value: 'ICIC000',
    error: 'Incorrect IFSC code. Try again',
  },
  BENIFICARY_NAME: {
    name: 'Beneficiary Name',
    value: 'abc',
    error: 'Enter alphabets (a-z) and spaces only',
  },
};

export const FORM_SUBMIT_API_DATA = {
  PENNY_TESTING_SUCCESS: {
    viewToAssert: BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_SUCCESS,
    apiMockFixture: saveBankAccountSuccess,
  },
  PENNY_TESTING_FAIL_CREATE_WORKFLOW: {
    viewToAssert: BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_RETRY,
    apiMockFixture: saveBankAccountSuccessPennyTestFail,
  },
  PENNY_TESTING_FAIL_WITH_TIMEOUT_CREATE_WORKFLOW: {
    viewToAssert: BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_RETRY,
    apiMockFixture: saveBankAccountSuccessTimeout,
  },
  PENNY_TESTING_BVS_INPUT_ERROR: {
    viewToAssert: BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_INPUT_ERROR,
    apiMockFixture: saveBankAccountSuccessBvsInputError,
  },
  PENNY_TESTING_FAIL_NO_RETRY: {
    viewToAssert: BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF,
    apiMockFixture: saveBankAccountSuccessTimeout,
  },
};
