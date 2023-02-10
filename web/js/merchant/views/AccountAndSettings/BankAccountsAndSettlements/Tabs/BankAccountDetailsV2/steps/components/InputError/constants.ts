import {
  BankAccountUpdateErrorCodeEnum,
  BankAccountUpdateErrorData,
  BVS_INPUT_ERROR_ICON,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';

import BvsBankError from 'assets/bvs-bank-error.svg';
import BvsUserError from 'assets/bvs-bank-user-error.svg';

const invalidBankDetails = {
  title: 'Couldn’t verify bank account',
  subTitle: 'The given bank account couldn’t be verified. Try again with another account.',
};

export const mapErrorCodeToData: {
  [key in BankAccountUpdateErrorCodeEnum]: BankAccountUpdateErrorData;
} = {
  [BankAccountUpdateErrorCodeEnum.INVALID_BENEFICIARY_ACCOUNT_NUMBER]: {
    title: invalidBankDetails.title,
    subTitle: invalidBankDetails.subTitle,
    icon: BVS_INPUT_ERROR_ICON.BANK_ERROR,
  },
  [BankAccountUpdateErrorCodeEnum.INVALID_ACCOUNT]: {
    title: invalidBankDetails.title,
    subTitle: invalidBankDetails.subTitle,
    icon: BVS_INPUT_ERROR_ICON.BANK_ERROR,
  },
  [BankAccountUpdateErrorCodeEnum.INVALID_IFSC_OR_NBIN]: {
    title: invalidBankDetails.title,
    subTitle: invalidBankDetails.subTitle,
    icon: BVS_INPUT_ERROR_ICON.BANK_ERROR,
  },
  [BankAccountUpdateErrorCodeEnum.ACCOUNT_BLOCKED]: {
    title: 'Account blocked or frozen',
    subTitle: "The given bank account couldn't be verified. Try again with another account.",
    icon: BVS_INPUT_ERROR_ICON.USER_ERROR,
  },
  [BankAccountUpdateErrorCodeEnum.NRE_ACCOUNT]: {
    title: 'NRE account not supported',
    subTitle: "The given bank account couldn't be verified. Try again with another account.",
    icon: BVS_INPUT_ERROR_ICON.BANK_ERROR,
  },
  [BankAccountUpdateErrorCodeEnum.ACCOUNT_CLOSED]: {
    title: 'Account closed',
    subTitle: 'The given bank account is closed. Try again with another account.',
    icon: BVS_INPUT_ERROR_ICON.BANK_ERROR,
  },
};

export const BVS_ICONS = {
  [BVS_INPUT_ERROR_ICON.BANK_ERROR]: BvsBankError,
  [BVS_INPUT_ERROR_ICON.USER_ERROR]: BvsUserError,
};
