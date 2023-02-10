import { INPUT_VALIDATION_STATES } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';

const INPUT_NAME_TO_EVENT_MAP = {
  account_number: 'Bank Account Number',
  ifsc_code: 'Bank Account IFSC Code',
  beneficiary_name: 'Bank Beneficiary Name',
};

const trackInputOnBlur = (e): void => {
  trackBankAccountUpdateEvent({
    objectName: INPUT_NAME_TO_EVENT_MAP[e.name],
    actionName: 'Entered',
  });
};

export const INPUT_FIELDS_PROPS = {
  account_number: {
    name: 'account_number',
    label: 'Account Number',
    errorText: 'Enter a 9-18 digit bank account number only',
    showClearButton: false,
    isRequired: true,
    onBlur: trackInputOnBlur,
  },
  account_number_confirmation: {
    name: 'account_number_confirmation',
    label: 'Confirm account number',
    errorText: 'Account number does not match',
    showClearButton: false,
    isRequired: true,
  },
  ifsc_code: {
    name: 'ifsc_code',
    label: 'Branch IFSC code',
    errorText: 'Incorrect IFSC code. Try again',
    showClearButton: false,
    isRequired: true,
    onBlur: trackInputOnBlur,
  },
  beneficiary_name: {
    name: 'beneficiary_name',
    label: 'Beneficiary Name',
    errorText: 'Enter alphabets (a-z) and spaces only',
    showClearButton: false,
    isRequired: true,
    onBlur: trackInputOnBlur,
  },
};

export const formInitState = {
  inputData: {
    account_number: '',
    account_number_confirmation: '',
    ifsc_code: '',
    beneficiary_name: '',
  },
  inputValidation: {
    account_number: INPUT_VALIDATION_STATES.NONE,
    account_number_confirmation: INPUT_VALIDATION_STATES.NONE,
    ifsc_code: INPUT_VALIDATION_STATES.NONE,
    beneficiary_name: INPUT_VALIDATION_STATES.NONE,
  },
  bvs_error_code: '',
  retries: 0,
};
