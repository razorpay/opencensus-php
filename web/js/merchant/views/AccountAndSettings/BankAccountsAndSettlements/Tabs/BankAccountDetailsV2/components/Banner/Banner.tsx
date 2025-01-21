import React, { useEffect } from 'react';
import { data, BannerType, trackBannerDisplayed, isBankAccountReq } from './config';
import { Alert, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { BankData } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import User from 'merchant/models/User';
import { OpenModalType } from 'common/typings';
import { settlementConfig } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import {
  DEFAULT_SETTLEMENT_SUB_TITLE,
  DEFAULT_SETTLEMENT_TITLE,
  isSettlementSOHBlockEnabled,
  SETTLEMENT_BANNER_BANK_UPDATE_MESSAGE,
  SETTLEMENT_HOLD_MESSAGE,
} from 'merchant/views/Settlements/components/utils';
import { useSplitzService } from 'common/splitz';
import { description } from 'common/ui/item/pair';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import {
  fireContactSupportFohEvent,
  showContactSupport,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';
import { useFohTicket } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/store';
import useFohTicketData from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/useFohTicketData';

export interface BannerProps {
  type: BannerType;
  openModal: OpenModalType;
  bankAccount: BankData | null;
  user: User;
  workflowEta?: string;
  settlementConfig?: settlementConfig;
  bankAccountChangeStatus?: boolean;
}
const Banner = ({
  type,
  openModal,
  bankAccount,
  user,
  workflowEta = '--',
  settlementConfig,
  bankAccountChangeStatus,
}: BannerProps): JSX.Element | null => {
  const splitz = useSplitzService();
  const { fohTicketId, fohTicketStatus } = useFohTicket();
  const { isLoading } = useFohTicketData();

  useEffect(() => {
    trackBannerDisplayed(type);
  }, []);

  if (!bankAccount && isBankAccountReq(type)) {
    return null;
  }

  const contactSupport = () => ({
    onClick: (): void => {
      trackBankAccountUpdateEvent({
        objectName: 'Banner Contact Support',
        actionName: 'Clicked',
      });
      fireContactSupportFohEvent(fohTicketId);
    },
    text: 'Contact support',
  });

  const getAlertProps = () => {
    const commonProps = {
      isDismissible: false,
      isFullWidth: true,
      description: '',
      title: '',
    };

    let color: 'negative' | 'information' = 'negative';

    if (bankAccountChangeStatus && type === BannerType.SOH) {
      color = 'information';
    }

    switch (type) {
      case BannerType.RISK_FOH:
        return {
          ...commonProps,
          color,
          title: settlementConfig?.sub_title || DEFAULT_SETTLEMENT_TITLE.FOH,
          description: DEFAULT_SETTLEMENT_SUB_TITLE.FOH,
          actions: {
            ...(showContactSupport(fohTicketStatus) && fohTicketId && !isLoading
              ? { primary: { ...contactSupport() } }
              : undefined),
          },
        };
      case BannerType.BLOCK:
        return {
          ...commonProps,
          color,
          title: settlementConfig?.sub_title || DEFAULT_SETTLEMENT_TITLE.BLOCK,
          description: DEFAULT_SETTLEMENT_SUB_TITLE.BLOCK,
        };
      case BannerType.SOH_CONTACT_SUPPORT:
        return {
          ...commonProps,
          color,
          title: settlementConfig?.sub_title || DEFAULT_SETTLEMENT_TITLE.SOH_CONTACT_SUPPORT,
          description: DEFAULT_SETTLEMENT_SUB_TITLE.SOH_CONTACT_SUPPORT,
        };

      case BannerType.SOH:
        return {
          ...commonProps,
          color,
          title: bankAccountChangeStatus
            ? DEFAULT_SETTLEMENT_TITLE.SOH_POST_BA_UPDATE
            : settlementConfig?.sub_title || DEFAULT_SETTLEMENT_TITLE.SOH,
          description: bankAccountChangeStatus
            ? DEFAULT_SETTLEMENT_SUB_TITLE.SOH_POST_BA_UPDATE
            : DEFAULT_SETTLEMENT_SUB_TITLE.SOH,
        };
      default:
        return commonProps;
    }
  };
  const alertProps = data({
    type,
    openModal,
    bankAccount,
    user,
    workflowEta,
    settlementConfig: {},
    bankAccountChangeStatus: false,
  });
  const overrideAlertProps = getAlertProps();

  return isSettlementSOHBlockEnabled(splitz) && overrideAlertProps ? (
    <Alert {...overrideAlertProps} />
  ) : (
    <Alert {...alertProps} />
  );
};

export default connect(
  (state) => ({ bankAccount: state.profile.bankAccount, user: state.session.user }),
  {
    openModal: fnOpenModal,
  },
)(Banner);
