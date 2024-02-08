import { LeafListItem } from 'common/typings';
import {
  VA_USD,
  VA_SWIFT,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import { KYC_BUSSINESS_TYPES } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import { REQUESTABLE, GREYED } from 'merchant/views/Settings/PaymentMethods/constants';

/**
 * Checks if instant bank transfer is activated in the instrument list.
 *
 * @param {LeafListItem} leafInstrument
 * @return {boolean} - Returns true if instant bank transfer is activated, false otherwise.
 */
export const isInstantBankTransferActivated = (
  leafInstrument: LeafListItem | undefined,
): boolean => {
  if (leafInstrument?.slug === 'international') {
    const instantBankTransfer = leafInstrument.leafList?.find(
      (item) => item.slug === 'instantbanktransfer',
    );

    return (
      instantBankTransfer?.list.every((item) => ![REQUESTABLE, GREYED].includes(item.status)) ??
      false
    );
  }

  return false;
};

/**
 * Checks if the money saver accounts are activated.
 *
 * @param {Object} b2bExportsAccounts - The B2B exports accounts object.
 * @param {boolean} b2bExportsAccounts.accountsDeactivated - Indicates if the accounts are deactivated.
 * @param {Object[]} b2bExportsAccounts.data - The data array.
 * @param {string} b2bExportsAccounts.data.va_currency - The currency of the account.
 * @return {boolean} Returns true if the money saver accounts are activated, otherwise false.
 */
export const isMoneySaverAccountsActivated = (
  b2bExportsAccounts:
    | {
        accountsDeactivated: boolean;
        isLoading: boolean;
        data: { va_currency: string }[];
      }
    | undefined,
): boolean => {
  if (!b2bExportsAccounts) {
    return false;
  }

  if (b2bExportsAccounts.accountsDeactivated || b2bExportsAccounts.isLoading) {
    return true;
  }

  if (!b2bExportsAccounts.data?.length) {
    return false;
  }

  return (
    b2bExportsAccounts.data?.every((item) => [VA_USD, VA_SWIFT].includes(item.va_currency)) ?? false
  );
};

/**
 * Checks if the more international methods are visible.
 *
 * @param {LeafListItem} leafInstrument - The leaf instrument.
 * @param {{ accountsDeactivated: boolean; isLoading: boolean; data: { va_currency: string }[]; }} b2bExportsAccounts - The B2B exports accounts.
 * @return {boolean}
 */
export const isMoreInternationalMethodsVisible = (state: {
  leafInstrument: LeafListItem;
  b2bExportsAccounts: {
    accountsDeactivated: boolean;
    isLoading: boolean;
    data: { va_currency: string }[];
  };
}): boolean => {
  return !(
    isInstantBankTransferActivated(state.leafInstrument) &&
    isMoneySaverAccountsActivated(state.b2bExportsAccounts)
  );
};

export const getDefaultTab = (business_type = '13'): number => {
  if (KYC_BUSSINESS_TYPES.includes(Number(business_type))) {
    return 0;
  }
  return 2;
};
