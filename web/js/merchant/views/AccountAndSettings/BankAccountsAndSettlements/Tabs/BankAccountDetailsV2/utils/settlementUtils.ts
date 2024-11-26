import {
  BankAccountDetailsPropsInterface,
  SettlementInfoPayload,
  SettlementInfoType,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';
import IUser from 'merchant/models/User';

/**
 * if merchant has `opgsp_import_flow` feature enabled then disable Change bank account.
 * Because Bank account for such merchants will be added during onboarding and
 * merchant is not allowed to update that. It can only be done via admin dashboard.
 */

export const isOpgspImportMerchantFn = ({ tags }: IUser): boolean =>
  !!tags?.some((tag) => tag.toLowerCase() === 'opgsp_import_flow');

export const getSettlementStatus = ({
  global_hold_config,
  hold,
  bankAccount,
  user,
}: SettlementInfoPayload): SettlementInfoType => {
  const {
    activation_status,
    activation_form_milestone,
    merchant: { hold_funds },
  } = user;
  const status: SettlementInfoType = {
    isHold: false,
    hold_type: null,
    message: '',
  };
  // istanbul ignore else
  if (activation_status !== 'activated') {
    // Non - Activated merchants
    status.isHold = true;
    status.hold_type = BannerType.COMPLETE_KYC;
    if (activation_form_milestone === 'L2' && bankAccount) {
      status.message = 'Complete KYC with bank account';
    } else if ((activation_form_milestone === 'L1' || !activation_form_milestone) && !bankAccount) {
      status.message = 'Complete KYC with no bank account';
    }
  } else if (global_hold_config?.status || hold_funds) {
    // Risk FOH
    status.isHold = true;
    status.hold_type = BannerType.RISK_FOH;
    status.message = 'Contact support team for settlements';
  } else if (hold?.status) {
    // SOH
    status.isHold = true;
    status.hold_type = BannerType.SOH;
    status.message = 'Resume update with new bank account';
  }
  return status;
};

export const getUpdateStatus = ({
  org,
  user,
  isOpgspImportMerchant,
  settlementStatus,
  isBankUpdateEnabled,
  isBlockBankAccountUpdate,
}: Pick<BankAccountDetailsPropsInterface, 'org' | 'user'> & {
  isBankUpdateEnabled: boolean | null;
  isOpgspImportMerchant: boolean;
  settlementStatus: SettlementInfoType;
  isBlockBankAccountUpdate: boolean;
}): boolean => {
  const { isHold, hold_type } = settlementStatus;
  const isHideRequestChange = org.features.includes('block_account_update');
  return !!(
    !isOpgspImportMerchant &&
    (!isHold || (isHold && hold_type === BannerType.SOH)) &&
    !isBlockBankAccountUpdate &&
    user.activation_status === 'activated' &&
    !isHideRequestChange &&
    isBankUpdateEnabled
  );
};
