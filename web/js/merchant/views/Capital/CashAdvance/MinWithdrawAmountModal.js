import React from 'react';

import WithdrawalErrorReason from './WithdrawalErrorReason';

const config = {
  description:
    'Your current withdrawable balance is lesser than Minimum withdrawal amount. You should have higher balance to withdraw more money.',
  texts: {
    amount: {
      left: {
        title: 'Withdrawal Balance',
        subtitle: 'Current',
      },
      right: {
        title: 'Minimum Withdrawal',
        subtitle: 'Amount',
      },
    },
    footerText: 'Repay pending due repayments to withdraw more.',
  },
  comparsionIcon: '<',
};

function MinWithdrawAmountModal({ amount, minWithdrawalAmount, ...rest }) {
  return (
    <WithdrawalErrorReason
      amounts={{
        requested: amount,
        threshold: minWithdrawalAmount,
      }}
      {...config}
      {...rest}
    />
  );
}

export default MinWithdrawAmountModal;
