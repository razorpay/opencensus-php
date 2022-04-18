import { getFixedINRAmount } from 'common/utils/rzp-utils';
import store from '../../../../store';
import analyticsService from '@razorpay/commander-services/analytics';

const trackEvent = (obj) => {
  const {
    session: { user },
  } = store.getState();

  try {
    analyticsService.track({
      ...obj,
      properties: {
        ...obj.properties,
        loc_flag: user.isLOCEnabled,
        withdraw_flag: user.isWithdrawFeatureEnabled,
      },
    });
  } catch (e) {
    // handle error
  }
};

export const trackOverviewTab = () =>
  trackEvent({
    objectName: 'Overview Tab',
    actionName: 'Visited',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
    },
  });

export const trackWithdrawAmountUpdated = (amount) =>
  trackEvent({
    objectName: 'Withdraw Amount',
    actionName: 'Updated',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_amount: amount,
    },
  });

export const trackRepayDateClicked = (date) =>
  trackEvent({
    objectName: 'Repay by Date',
    actionName: 'Clicked',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_date: date,
    },
  });

export const trackRepayDateUpdated = (date) =>
  trackEvent({
    objectName: 'Repay by Date',
    actionName: 'Updated',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_date: date,
    },
  });

export const trackShowBreakup = (data) => {
  const { interest, principle } = data;
  trackEvent({
    objectName: 'Show Breakup',
    actionName: 'Clicked',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      total_repayable: principle + interest,
      principal_amount: principle,
      interest_amount: interest,
    },
  });
};

export const trackHideBreakup = () =>
  trackEvent({
    objectName: 'Hide Breakup',
    actionName: 'Clicked',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
    },
  });

export const trackWithdrawNow = ({ amount, date }) =>
  trackEvent({
    objectName: 'Withdraw Now',
    actionName: 'Clicked',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_amount: amount,
      withdrawal_date: date,
    },
  });

export const trackWithdrawNowConfirm = ({ amount, date }) =>
  trackEvent({
    objectName: 'Withdraw Confirm',
    actionName: 'Clicked',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_amount: amount,
      withdrawal_date: date,
    },
  });

export const trackWithdrawNowCancel = ({ amount, date }) =>
  trackEvent({
    objectName: 'Withdraw Cancel',
    actionName: 'Clicked',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_amount: amount,
      withdrawal_date: date,
    },
  });

export const trackWithdrawStatus = ({ amount, date, status }) =>
  trackEvent({
    objectName: 'Withdrawal Status',
    actionName: 'Acknowledged',
    screen: 'Cash Advance',
    properties: {
      tab: 'Overview',
      location: 'Cash Advance',
      withdrawal_amount: amount,
      withdrawal_date: date,
      status,
    },
  });

export const trackViewRepayments = (fromWhere) => {
  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'View Repayments',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
      },
    });
  }
};

export const trackRepayNow = (fromWhere) => {
  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Repay Now',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
      },
    });
  }
};

export const trackRepayConfirm = (fromWhere, data) => {
  const {
    repayAmount,
    nextRepayInterestAmount,
    nextRepayPrincipalAmount,
    totalPrincipalAmount,
    totalInterestAmount,
    repayType,
    isSettlementActiveAndHasBalance,
    isBankBalanceActiveAndHasBalance,
    settlementBalance,
    bankBalance,
  } = data;

  const repayMode = isSettlementActiveAndHasBalance
    ? 'Settlement Balance'
    : isBankBalanceActiveAndHasBalance
    ? 'Netbanking/UPI'
    : '';

  const customAmount = isSettlementActiveAndHasBalance
    ? settlementBalance.customAmount
    : isBankBalanceActiveAndHasBalance
    ? bankBalance.customAmount
    : null;

  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Repay Confirm',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        repayment_via: repayMode,
        amount_to_be_repaid: getFixedINRAmount(repayAmount),
        custom_amount: getFixedINRAmount(customAmount),
        repay_type: repayType,
        next_repayable: getFixedINRAmount(nextRepayPrincipalAmount + nextRepayInterestAmount),
        total_owed: getFixedINRAmount(totalPrincipalAmount + totalInterestAmount),
      },
    });
  }
};

export const trackRepayCancel = (fromWhere, data) => {
  const {
    nextRepayInterestAmount,
    nextRepayPrincipalAmount,
    totalPrincipalAmount,
    totalInterestAmount,
    isSettlementActiveAndHasBalance,
    isBankBalanceActiveAndHasBalance,
    settlementBalance,
    bankBalance,
  } = data;

  const customAmount = isSettlementActiveAndHasBalance
    ? settlementBalance.customAmount
    : isBankBalanceActiveAndHasBalance
    ? bankBalance.customAmount
    : null;

  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Repay Cancel',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        custom_amount: getFixedINRAmount(customAmount),
        next_repayable: getFixedINRAmount(nextRepayPrincipalAmount + nextRepayInterestAmount),
        total_owed: getFixedINRAmount(totalPrincipalAmount + totalInterestAmount),
      },
    });
  }
};

export const trackSettlementAmountUpdated = (fromWhere, data) => {
  const { error, amount } = data;
  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Repay via Settlement Amount',
      actionName: 'Updated',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        settlement_amount: amount,
        Input_feild_error: error,
      },
    });
  }
};

export const trackChangeAmount = (fromWhere) => {
  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Change Repay Amount',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
      },
    });
  }
};

export const trackRepaymentSuccess = (fromWhere, data) => {
  const {
    bankAmount,
    interestAmount,
    principalAmount,
    repayAmount,
    settlementAmount,
    userRepayMethod,
  } = data;
  let repayMethod = '';

  if (settlementAmount !== 0) repayMethod += 'Settlement Balance ';
  if (bankAmount !== 0) repayMethod += userRepayMethod;

  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Repayment Success',
      actionName: 'Acknowledged',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        total_repaid: getFixedINRAmount(repayAmount),
        interest_repaid: getFixedINRAmount(interestAmount),
        principal_repaid: getFixedINRAmount(principalAmount),
        repayment_via: repayMethod,
      },
    });
  }
};

export const trackRepaymentFailure = (fromWhere, data) => {
  const {
    bankAmount,
    interestAmount,
    principalAmount,
    repayAmount,
    settlementAmount,
    userRepayMethod,
  } = data;
  let repayMethod = '';

  if (settlementAmount !== 0) repayMethod += 'Settlement Balance ';
  if (bankAmount !== 0) repayMethod += userRepayMethod;

  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Repayment Failure',
      actionName: 'Acknowledged',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        Tttal_repaid: getFixedINRAmount(repayAmount),
        interest_repaid: getFixedINRAmount(interestAmount),
        principal_repaid: getFixedINRAmount(principalAmount),
        repayment_via: repayMethod,
      },
    });
  }
};

export const trackRepaymentRetry = (fromWhere, data) => {
  const { bankAmount, repayAmount, settlementAmount, userRepayMethod } = data;
  let repayMethod = '';

  if (settlementAmount !== 0) repayMethod += 'Settlement Balance ';
  if (bankAmount !== 0) repayMethod += userRepayMethod;

  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Retry Repayment',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        repayment_amount: getFixedINRAmount(repayAmount),
        repayment_via: repayMethod,
      },
    });
  }
};

export const trackRepaymentClose = (fromWhere, type, data) => {
  const { bankAmount, repayAmount, settlementAmount, userRepayMethod } = data;
  let objectName;
  let repayMethod = '';

  if (type === 'icon') objectName = 'Retry Repayment Close Icon';
  else objectName = 'Retry Repayment Close';

  if (settlementAmount !== 0) repayMethod += 'Settlement Balance ';
  if (bankAmount !== 0) repayMethod += userRepayMethod;

  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName,
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        repayment_amount: getFixedINRAmount(repayAmount),
        repayment_via: repayMethod,
      },
    });
  }
};

export const trackCheckoutFlowCancel = (fromWhere, repayAmount) => {
  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Chekout Flow Cancel',
      actionName: 'Clicked',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        repayment_amount: getFixedINRAmount(repayAmount),
      },
    });
  }
};

export const trackCheckoutFlowSuccess = (fromWhere, repayAmount) => {
  if (fromWhere === '/capital/cash-advance/overview') {
    trackEvent({
      objectName: 'Chekout Flow Success',
      actionName: 'Acknowledged',
      screen: 'Cash Advance',
      properties: {
        tab: 'Overview',
        location: 'Cash Advance',
        Section: 'Repayment Summary',
        repayment_amount: getFixedINRAmount(repayAmount),
      },
    });
  }
};

export const trackLandingonCashAdvanceV1 = () =>
  trackEvent({
    objectName: 'Cash Advance Homepage current',
    actionName: 'Rendered',
    screen: 'Cash Advance || Home Screen',
    properties: {
      tab: 'Cash Advance Homescreen',
      location: 'Begin',
    },
  });
