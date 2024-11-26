import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import {
  AccountData,
  useAccountHookInterface,
  UseBankAccountDetailsInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { getAccountData } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/accountsInfo';
import {
  getSettlementStatus,
  getUpdateStatus,
  isOpgspImportMerchantFn,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/settlementUtils';
import { useEffect, useMemo, useState } from 'react';

const getAccounts = ({ bankAccount }) => ({
  active: bankAccount?.id ? [bankAccount] : [],
});

export const useBankAccountDetails = ({
  org,
  user,
  isDataSettled,
  bankAccount,
  settlementConfig,
  isBankUpdateEnabled,
}: UseBankAccountDetailsInterface): useAccountHookInterface => {
  const [bankAccountDetails, setBankAccountDetails] = useState<useAccountHookInterface>({
    previousBanks: {},
    activeBank: {},
    settlementStatus: {},
    isUpdateEnable: false,
  });

  const isOpgspImportMerchant = useMemo(() => {
    return isOpgspImportMerchantFn(user);
  }, [user.tags]);

  const { abExperiments } = useSplitzService();

  useEffect(() => {
    if (isDataSettled) {
      const { global_hold_config, hold } = settlementConfig?.data?.config?.features || {};
      const settlementStatus = getSettlementStatus({ global_hold_config, hold, bankAccount, user });
      const accounts = getAccounts({ bankAccount });
      const { previous: previousBanks, active: activeBank } = Object.keys(accounts).reduce(
        (accumulator, each) => {
          accumulator[each] = getAccountData({
            type: each,
            data: accounts[each],
            isSettlementOnhold: settlementStatus.isHold,
          });
          return accumulator;
        },
        {
          previous: {},
          active: {},
        } as {
          previous: AccountData;
          active: AccountData;
        },
      );
      const isUpdateEnable = getUpdateStatus({
        org,
        user,
        isOpgspImportMerchant,
        settlementStatus,
        isBankUpdateEnabled,
        isBlockBankAccountUpdate: isExperimentEnabled(abExperiments?.block_bank_account_update),
      });
      setBankAccountDetails((prevState) => ({
        ...prevState,
        previousBanks,
        activeBank,
        settlementStatus,
        isUpdateEnable,
      }));
    }
  }, [
    user,
    org,
    isDataSettled,
    bankAccount,
    settlementConfig,
    isOpgspImportMerchant,
    isBankUpdateEnabled,
  ]);

  return { ...bankAccountDetails };
};
