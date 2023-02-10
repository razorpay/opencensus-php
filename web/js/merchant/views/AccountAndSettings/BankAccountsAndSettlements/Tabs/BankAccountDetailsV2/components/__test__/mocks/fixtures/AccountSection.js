import {
  ActiveAccountData,
  BANK_DATA,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/constants/data';

export const ActiveAccountDataOHS = {
  ...ActiveAccountData,
  banks: [BANK_DATA],
};

export const NoBankAccountData = {
  ...ActiveAccountData,
  banks: undefined,
};

export const PreviousAccountData = {
  ...ActiveAccountData,
  banks: [BANK_DATA, BANK_DATA, BANK_DATA],
};
