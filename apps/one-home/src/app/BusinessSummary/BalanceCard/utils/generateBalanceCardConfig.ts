import { paiseToRupees } from '@libs/shared-utils';

type BalanceDataSummary = {
  last_updated?: string;
  input_time?: string;
  current_data?: string;
  percentage_change?: number;
};

type BalanceAccount = {
  value: number;
  type: 'unmasked' | 'masked';
  currency: 'INR';
  title: string;
};

type BalanceData = {
  data_summary?: BalanceDataSummary;
  locked_current_account: boolean;
  locked_settlement_account: boolean;
  current_account_balance?: string;
  settlement_account_balance?: string;
  current_account_present: boolean;
  settlement_account_present: boolean;
};

type BalanceConfig = {
  type:
    | 'ideal'
    | 'locked_current'
    | 'pg_only'
    | 'x_only'
    | 'pg_only_hidden'
    | 'x_only_hidden'
    | 'pg_hidden_x_only'
    | 'pg_locked_x_locked';
  totalBalance: BalanceAccount;
  currentAccount: BalanceAccount | null;
  settlementAccount: BalanceAccount | null;
  showButton: boolean;
};

const generateBalanceCardConfig = (balanceData?: BalanceData): BalanceConfig | null => {
  if (!balanceData) return null;

  const {
    data_summary,
    locked_current_account,
    locked_settlement_account,
    current_account_balance,
    settlement_account_balance,
    current_account_present,
    settlement_account_present,
  } = balanceData;

  const currency: 'INR' = 'INR';
  const totalBalanceValue = paiseToRupees(Number(data_summary?.current_data || 0));
  const currentAccountBalanceValue = paiseToRupees(Number(current_account_balance || 0));
  const settlementAccountBalanceValue = paiseToRupees(Number(settlement_account_balance || 0));

  const totalBalance: BalanceAccount = {
    value: totalBalanceValue,
    type: 'unmasked',
    currency,
    title: 'TOTAL BALANCE',
  };

  //ideal
  if (
    current_account_present &&
    settlement_account_present &&
    !locked_current_account &&
    !locked_settlement_account
  ) {
    return {
      type: 'ideal',
      totalBalance,
      currentAccount: {
        value: currentAccountBalanceValue,
        type: 'unmasked',
        currency,
        title: 'Current A/c',
      },
      settlementAccount: {
        value: settlementAccountBalanceValue,
        type: 'unmasked',
        currency,
        title: 'Settlement A/c',
      },
      showButton: false,
    };
  }

  //locked current and Settlement Balance
  if (
    current_account_present &&
    locked_current_account &&
    settlement_account_present &&
    !locked_settlement_account
  ) {
    return {
      type: 'locked_current',
      totalBalance,
      currentAccount: { value: 0, type: 'masked', currency, title: 'Current A/c' },
      settlementAccount: {
        value: settlementAccountBalanceValue,
        type: 'unmasked',
        currency,
        title: 'Settlement A/c',
      },
      showButton: false,
    };
  }

  //PG only, show settlement account balance
  if (!current_account_present && settlement_account_present && !locked_settlement_account) {
    return {
      type: 'pg_only',
      totalBalance: {
        value: settlementAccountBalanceValue,
        type: 'unmasked',
        currency,
        title: 'SETTLEMENT A/c BALANCE',
      },
      currentAccount: null,
      settlementAccount: null,
      showButton: true,
    };
  }

  //PG locked, X Only, show current account balance
  if (
    current_account_present &&
    locked_settlement_account &&
    !locked_current_account &&
    settlement_account_present
  ) {
    return {
      type: 'pg_hidden_x_only',
      totalBalance,
      currentAccount: {
        value: currentAccountBalanceValue,
        type: 'unmasked',
        currency,
        title: 'Current A/c',
      },
      settlementAccount: {
        value: settlementAccountBalanceValue,
        type: 'masked',
        currency,
        title: 'Settlement A/c',
      },
      showButton: false,
    };
  }

  //X Only, show current account balance
  if (current_account_present && !settlement_account_present && !locked_current_account) {
    return {
      type: 'x_only',
      totalBalance: {
        value: currentAccountBalanceValue,
        type: 'unmasked',
        currency,
        title: 'CURRENT A/c BALANCE',
      },
      currentAccount: null,
      settlementAccount: null,
      showButton: false,
    };
  }

  //PG Only Hidden
  if (!current_account_present && settlement_account_present && locked_settlement_account) {
    return {
      type: 'pg_only_hidden',
      totalBalance: { value: 0, type: 'masked', currency, title: 'SETTLEMENT A/c BALANCE' },
      currentAccount: null,
      settlementAccount: null,
      showButton: false,
    };
  }

  //X Only Hidden
  if (current_account_present && !settlement_account_present && locked_current_account) {
    return {
      type: 'x_only_hidden',
      totalBalance: { value: 0, type: 'masked', currency, title: 'CURRENT A/c BALANCE' },
      currentAccount: null,
      settlementAccount: null,
      showButton: false,
    };
  }

  //Pg locked, X Locked
  if (
    settlement_account_present &&
    current_account_present &&
    locked_current_account &&
    locked_settlement_account
  ) {
    return {
      type: 'pg_locked_x_locked',
      totalBalance: { value: 0, type: 'masked', currency, title: 'TOTAL BALANCE' },
      currentAccount: {
        value: currentAccountBalanceValue,
        type: 'masked',
        currency,
        title: 'Current A/c',
      },
      settlementAccount: {
        value: settlementAccountBalanceValue,
        type: 'masked',
        currency,
        title: 'Settlement A/c',
      },
      showButton: false,
    };
  }

  //TODO:
  //1. show PG Growth page, => no settlement_account_present,
  //2. [No PG and No X]

  return null;
};

export default generateBalanceCardConfig;
