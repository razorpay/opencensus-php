import moment from 'moment';
import {
  STATUSES,
  CASH_ADVANCE_CAROUSEL_VIEW_RULES,
  CASH_ADVANCE_SECTIONS,
  PAYMENT_MODES,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  REPAYMENT_STATUES,
} from './constants';
import WithdrawalConfig from './WithdrawalConfigModel';

const ruleValidators = {
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.NO_WITHDRAWALS]({ view, withdrawals }) {
    return view === CASH_ADVANCE_SECTIONS.WITHDRAWALS && withdrawals.length === 0;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_INITIATED_WITHDRAWAL]({ view, withdrawals }) {
    return (
      view === CASH_ADVANCE_SECTIONS.WITHDRAWALS &&
      withdrawals.length === 1 &&
      withdrawals[0].status === STATUSES.INITIATED
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_PROCESSED_WITHDRAWAL]({ view, withdrawals }) {
    return (
      view === CASH_ADVANCE_SECTIONS.WITHDRAWALS &&
      withdrawals.length === 1 &&
      withdrawals[0].status === STATUSES.PROCESSED
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_NON_REPAID_DISBURSED_WITHDRAWAL]({
    withdrawals,
    repayments,
  }) {
    return (
      withdrawals.length === 1 &&
      (withdrawals[0].status === STATUSES.PROCESSED ||
        withdrawals[0].status === STATUSES.MANUALLY_PROCESSED) &&
      repayments.length === 0
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_AUTO_REPAID_DISBURSED_WITHDRAWAL]({
    withdrawals,
    repayments,
  }) {
    const isFirstSuccessfulRepaidWithdrawal =
      withdrawals.length === 1 &&
      (withdrawals[0].status === STATUSES.REPAID ||
        withdrawals[0].status === STATUSES.PARTIALLY_REPAID);

    const isFirstRepayment = repayments.length === 1;
    const isFirstRepaymentAutoCollected =
      withdrawals.length === 1 &&
      withdrawals[0].repayments &&
      withdrawals[0].repayments.find(
        (repayment) =>
          repayment.id === repayments[0].id &&
          repayment.payment_reference_type === COLLECTIONS_PAYMENT_REFERENCE_TYPE.CREDIT_REPAYMENT,
      );

    return isFirstSuccessfulRepaidWithdrawal && isFirstRepayment && isFirstRepaymentAutoCollected;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_MANUAL_REPAID_DISBURSED_WITHDRAWAL]({
    withdrawals,
    repayments,
  }) {
    const isFirstSuccessfulRepaidWithdrawal =
      withdrawals.length === 1 &&
      (withdrawals[0].status === STATUSES.REPAID ||
        withdrawals[0].status === STATUSES.PARTIALLY_REPAID);

    const isFirstRepayment = repayments.length === 1;
    const isFirstRepaymentManuallyCollected =
      withdrawals.length === 1 &&
      withdrawals[0].repayments &&
      withdrawals[0].repayments.find(
        (repayment) =>
          repayment.id === repayments[0].id &&
          repayment.payment_reference_type === COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER,
      );

    return (
      isFirstSuccessfulRepaidWithdrawal && isFirstRepayment && isFirstRepaymentManuallyCollected
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_WITHDRAWAL_LAST_REPAYMENT_PENDING]({
    withdrawals,
    repayments,
  }) {
    const isFirstSuccessfulRepaidWithdrawal =
      withdrawals.length === 1 &&
      (withdrawals[0].status === STATUSES.REPAID ||
        withdrawals[0].status === STATUSES.PARTIALLY_REPAID);

    const isFirstRepayment = repayments.length === 1;
    const isFirstRepaymentStatusPending =
      withdrawals.length === 1 &&
      withdrawals[0].repayments &&
      withdrawals[0].repayments.filter(
        (repayment) => repayment.status === REPAYMENT_STATUES.STATUS_PENDING,
      ).length === 1;

    return isFirstSuccessfulRepaidWithdrawal && isFirstRepayment && isFirstRepaymentStatusPending;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.EXHAUSTED_WITHDRAWAL_BALANCE]({ withdrawalConfig }) {
    return (
      new WithdrawalConfig(withdrawalConfig).withdrawableBalance <
      withdrawalConfig.configuration.min_withdraw_amount
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.ELIGIBLE_YET_INACTIVE_LAST_FEW_DAYS]({
    withdrawals,
    withdrawalConfig,
  }) {
    if (withdrawals.length === 0) return false;

    const availableWithdrawalBalance =
      withdrawalConfig.principal_outstanding_balance - withdrawalConfig.internal_credit_limit;
    const todayDate = moment().endOf('day');
    const lastWithdrawal = moment(withdrawals[0].created_at);
    const diffDays = todayDate.diff(lastWithdrawal, 'days');

    return diffDays >= 3 && availableWithdrawalBalance >= withdrawalConfig.min_withdraw_amount;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAY_FAILED_MORE_THAN_THREE]({ withdrawals, repayments }) {
    if (withdrawals.length === 0) return false;

    return (
      withdrawals[0].staus !== STATUSES.REPAID &&
      repayments.filter((repayment) => repayment.status === REPAYMENT_STATUES.STATUS_FAILIED)
        .length > 3
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAYMENTS_TAB_SHOW_DUE]({ view, withdrawalConfig }) {
    return (
      view === CASH_ADVANCE_SECTIONS.REPAYMENTS &&
      withdrawalConfig.principal_outstanding_balance > 0
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAYMENTS_TAB_SHOW_NO_DUE]({ view, withdrawalConfig }) {
    return (
      view === CASH_ADVANCE_SECTIONS.REPAYMENTS &&
      withdrawalConfig.principal_outstanding_balance === 0
    );
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.TODAY_FIRST_REPAYMENT_FAILURE]({ repayments, view }) {
    const todayFailedRepayments = repayments.filter((repayment) => {
      const isOverviewSection = view === CASH_ADVANCE_SECTIONS.OVERVIEW;
      const hasRepayments = repayments.length > 0;
      const hasRepaidToday = moment().diff(repayment.created_at, 'days') === 0;
      const hasRepaymentFailed = repayment.status === REPAYMENT_STATUES.STATUS_FAILED;

      return isOverviewSection && hasRepayments && hasRepaidToday && hasRepaymentFailed;
    });

    return todayFailedRepayments.length === 1;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.FULL_DAY_REPAYMENT_FAILURE]({ repayments, view }) {
    const todayFailedRepayments = repayments.filter((repayment) => {
      const isOverviewSection = view === CASH_ADVANCE_SECTIONS.OVERVIEW;
      const hasRepayments = repayments.length > 0;
      const hasRepaidToday = moment().diff(repayment.created_at, 'days') === 0;
      const hasRepaymentFailed = repayment.status === REPAYMENT_STATUES.STATUS_FAILED;

      return isOverviewSection && hasRepayments && hasRepaidToday && hasRepaymentFailed;
    });

    // As everyday, repayment happens at 3 different times, if those fail,
    // we count it as full day failure and the same collection will be
    // attempted the next day
    return todayFailedRepayments.length >= 3;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.THREE_FULL_DAY_REPAYMENT_FAILURE]({ repayments, view }) {
    const lastThreeDaySuccessRepayments = repayments.filter((repayment) => {
      const isOverviewSection = view === CASH_ADVANCE_SECTIONS.OVERVIEW;
      const isRepaymentOccurredInLastThreeDays = moment().diff(repayment.created_at, 'days') <= 3;
      const isRepaymentProcessed = repayment.status === REPAYMENT_STATUES.STATUS_FAILED;

      return isOverviewSection && isRepaymentOccurredInLastThreeDays && isRepaymentProcessed;
    });

    const hasRepaymentsOccurredInLastThreeDays =
      repayments.filter((repayment) => repayment.payment_mode === PAYMENT_MODES.AUTO_COLLECTION)
        .length >= 9;

    // If atleast one repayment was successful, this should return false
    return hasRepaymentsOccurredInLastThreeDays && !lastThreeDaySuccessRepayments.length > 0;
  },
  [CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAYMENT_TAB_VIEW]({ view }) {
    return view === CASH_ADVANCE_SECTIONS.REPAYMENTS;
  },
};

export default ruleValidators;
