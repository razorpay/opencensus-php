import React from 'react';

import WithdrawalErrorReason from './WithdrawalErrorReason';

const config = {
  description:
    'Selected withdrawal amount is greater than Maximum withdrawal amount that can be withdrawn at a single time.',
  texts: {
    amount: {
      left: {
        title: 'Selected Amount',
        subtitle: 'Current',
      },
      right: {
        title: 'Maximum Withdrawal',
        subtitle: 'amount at single time',
      },
    },
    footerText: 'On-time repayments will increase your credit limit.',
  },
  comparsionIcon: '>',
};

function MaxWithdrawError({ amount, maxWithdrawalAmount, ...rest }) {
  return (
    <WithdrawalErrorReason
      amounts={{
        requested: amount,
        threshold: maxWithdrawalAmount,
      }}
      {...config}
      {...rest}
    />
  );
}

export default MaxWithdrawError;
