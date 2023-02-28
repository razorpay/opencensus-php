import React, { useEffect } from 'react';
import { data, BannerType, trackBannerDisplayed, isBankAccountReq } from './config';
import { Alert } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { BankData } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import User from 'merchant/models/User';
import { OpenModalType } from 'common/typings';

export interface BannerProps {
  type: BannerType;
  openModal: OpenModalType;
  bankAccount: BankData | null;
  user: User;
  workflowEta?: string;
}

const Banner = ({
  type,
  openModal,
  bankAccount,
  user,
  workflowEta = '--',
}: BannerProps): JSX.Element | null => {
  useEffect(() => {
    trackBannerDisplayed(type);
  }, []);

  if (!bankAccount && isBankAccountReq(type)) {
    return null;
  }

  const alertProps = data({ type, openModal, bankAccount, user, workflowEta });
  return <Alert {...alertProps} />;
};

export default connect(
  (state) => ({ bankAccount: state.profile.bankAccount, user: state.session.user }),
  {
    openModal: fnOpenModal,
  },
)(Banner);
