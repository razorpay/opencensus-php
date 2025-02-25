import React from 'react';
import moment from 'moment';

import Amount from 'common/ui/Amount';
import { getItem, setItem } from 'common/utils/localStorage';
import { isProductionEnv } from 'common/utils/rzp-utils';
import Repayments from 'merchant/models/Capital/Repayments';
import store from 'merchant/store';

import { getNextRepayBreakup } from './OverviewFooter/utils/index';
import WithdrawalConfig from './WithdrawalConfigModel';
import {
  BASE_SLIDE_CONTENT_BY_VARIANT,
  CASH_ADVANCE_CAROUSEL_SLIDES,
  CHECKOUT_SRC,
  COLLECTIONS_BALANCE_TYPE,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  COLLECTIONS_PRODUCT_TYPES,
  DEV_BASE_URL,
  REPAYMENT_FREQUENCY_TYPES,
  SLIDE_COLORS,
} from './constants';

export const getSlideByRule = () => {
  return [CASH_ADVANCE_CAROUSEL_SLIDES.REGULAR_WITHDRAWAL_BENEFIT_PROMPT];
  /* switch (rule) {
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.EXHAUSTED_WITHDRAWAL_BALANCE:
      return [CASH_ADVANCE_CAROUSEL_SLIDES.REGULAR_WITHDRAWAL_BENEFIT_PROMPT];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAYMENTS_TAB_SHOW_DUE:
      return [CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAYMENTS_TAB_SHOW_NO_DUE:
      return [CASH_ADVANCE_CAROUSEL_SLIDES.FULL_DAY_AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.TODAY_FIRST_REPAYMENT_FAILURE:
      return [
        CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT,
        CASH_ADVANCE_CAROUSEL_SLIDES.AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT,
      ];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.FULL_DAY_REPAYMENT_FAILURE:
      return [
        'NON_ZERO_DUE_AMOUNT_PROMPT',
        CASH_ADVANCE_CAROUSEL_SLIDES.FULL_DAY_AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT,
      ];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.THREE_FULL_DAY_REPAYMENT_FAILURE:
      return [
        CASH_ADVANCE_CAROUSEL_SLIDES.THREE_DAY_REPAYMENT_FAILED_PROMPT,
        CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT,
      ];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.ELIGIBLE_YET_INACTIVE_LAST_FEW_DAYS:
      return [
        CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT,
        CASH_ADVANCE_CAROUSEL_SLIDES.WITHDRAW_PROMPT_DUE_TO_INACTIVITY,
      ];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.NO_WITHDRAWALS:
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_INITIATED_WITHDRAWAL:
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_NON_REPAID_DISBURSED_WITHDRAWAL:
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_AUTO_REPAID_DISBURSED_WITHDRAWAL:
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_MANUAL_REPAID_DISBURSED_WITHDRAWAL:
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.FIRST_WITHDRAWAL_LAST_REPAYMENT_PENDING:
      return [
        CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT,
        CASH_ADVANCE_CAROUSEL_SLIDES.REGULAR_WITHDRAWAL_BENEFIT_PROMPT,
        CASH_ADVANCE_CAROUSEL_SLIDES.AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT,
      ];
    case CASH_ADVANCE_CAROUSEL_VIEW_RULES.REPAYMENT_TAB_VIEW:
      return [CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT];
    default:
      return [CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT];
  } */
};

export const getSlideContent = (slideId, withdrawalConfig, upcomingRepayments, balances = []) => {
  const availableWithdrawalBalance = new WithdrawalConfig(withdrawalConfig).withdrawableBalance;
  let totalPrincipalAmount = 0;
  let totalInterestAmount = 0;
  const internalCreditLimit = withdrawalConfig?.configuration?.internal_credit_limit;

  balances.forEach(({ balance_type, balance_amount }) => {
    if (balance_type === COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_PRINCIPAL)
      totalPrincipalAmount += Number(balance_amount);
    else if (balance_type === COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_INTEREST)
      totalInterestAmount += Number(balance_amount);
  });

  const outstandingBalance = totalInterestAmount + totalPrincipalAmount;

  switch (slideId) {
    case CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT: {
      let lastDate = '';
      let last = upcomingRepayments ? upcomingRepayments[upcomingRepayments.length - 1] : null;

      if (last && moment().unix() > last.repayment_date) last = null;
      if (last) lastDate = moment.unix(last.repayment_date).format('MMMM D');

      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.blue],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.NON_ZERO_DUE_AMOUNT_PROMPT,
        title: 'Total Owed Amount',
        subTitle: <Amount value={outstandingBalance} />,
        body:
          outstandingBalance > 0 ? (
            <span>
              This amount will be deducted on a daily basis from your settlement balance{' '}
              {`${lastDate ? `by ${lastDate}.` : '.'}`}
            </span>
          ) : (
            'No owed amount.'
          ),
        cta: {
          text: 'View Details',
          actionId: 'VIEW_WITHDRAWAL_DETAILS',
        },
        rightDataPair: [
          {
            label: 'Due Principal',
            value: <Amount value={totalPrincipalAmount} />,
          },
          {
            label: 'Due Interest',
            value: <Amount value={totalInterestAmount} />,
          },
        ],
      };
    }
    case CASH_ADVANCE_CAROUSEL_SLIDES.ZERO_OUTSTANDING_BALANCE:
      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.blue],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.ZERO_OUTSTANDING_BALANCE,
        title: 'Automatic Repayment',
        subTitle: 'From Settlement Balance',
        body: 'Your future due amount will be deducted in parts, from your settlement balance on a daily basis.',
        cta: {
          text: 'Withdraw',
          actionId: 'WITHDRAW',
        },
      };
    case CASH_ADVANCE_CAROUSEL_SLIDES.WITHDRAW_PROMPT_DUE_TO_INACTIVITY:
      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.green],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.WITHDRAW_PROMPT_DUE_TO_INACTIVITY,
        title: 'Withdrawal Balance',
        subTitle: <Amount value={availableWithdrawalBalance} />,
        body: 'Hey, You haven’t withdrawn for last 3 days. Start withdrawing more to increase the chances of getting higher withdrawal limit.',
      };
    case CASH_ADVANCE_CAROUSEL_SLIDES.REGULAR_WITHDRAWAL_BENEFIT_PROMPT:
      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.green],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.REGULAR_WITHDRAWAL_BENEFIT_PROMPT,
        title: 'Total Credit Limit',
        subTitle: <Amount value={internalCreditLimit} />,
        body: 'Your continuous withdrawals and regular on-time due repayments will increase the chances of getting higher withdrawal limit.',
      };
    case CASH_ADVANCE_CAROUSEL_SLIDES.AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT: {
      const { nextRepayInterestAmount = 0, nextRepayPrincipalAmount = 0 } = getNextRepayBreakup({
        data: upcomingRepayments,
      });
      const nextRepayableAmount = nextRepayPrincipalAmount + nextRepayInterestAmount;

      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.red],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT,
        backgroundPattern: true,
        color: SLIDE_COLORS.red,
        title: 'Scheduled Repayment',
        subTitle: <Amount value={nextRepayableAmount} />,
        body:
          nextRepayableAmount > 0
            ? 'If we are not able to collect this Next repayable amount on time,' +
              " We will try again next day to collect the delayed amount along with next day's interest."
            : 'No pending amount needs to be repaid.',
      };
    }
    case CASH_ADVANCE_CAROUSEL_SLIDES.FULL_DAY_AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT: {
      const { nextRepayInterestAmount = 0, nextRepayPrincipalAmount = 0 } = getNextRepayBreakup({
        data: upcomingRepayments,
      });
      const nextRepayableAmount = nextRepayPrincipalAmount + nextRepayInterestAmount;

      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.red],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.FULL_DAY_AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT,
        title: 'Today’s Repayable Amount',
        subTitle: nextRepayableAmount && <Amount value={nextRepayableAmount} />,
        body: 'Due to the low settlement balance, today’s repayable amount has not been collected. You can repay the amount manually by clicking on the button below.',
      };
    }
    case CASH_ADVANCE_CAROUSEL_SLIDES.THREE_DAY_REPAYMENT_FAILED_PROMPT: {
      const { nextRepayInterestAmount = 0, nextRepayPrincipalAmount = 0 } = getNextRepayBreakup({
        data: upcomingRepayments,
      });
      const nextRepayableAmount = nextRepayPrincipalAmount + nextRepayInterestAmount;

      return {
        ...BASE_SLIDE_CONTENT_BY_VARIANT[SLIDE_COLORS.red],
        id: CASH_ADVANCE_CAROUSEL_SLIDES.THREE_DAY_REPAYMENT_FAILED_PROMPT,
        title: 'Due Repayment Amount',
        subTitle: nextRepayableAmount && <Amount value={nextRepayableAmount} />,
        body: 'You have missed your repayments for the last 3 days due to low settlement balance. Repay now to avoid getting additional fees.',
      };
    }
    default:
      return null;
  }
};

export function computePrincipalAndInterest(repaymentBreakups, key = 'breakup_amount') {
  const response = {};

  repaymentBreakups.forEach((breakup) => {
    const type = breakup.balance_type;
    const amount = Number(breakup[key]);

    if (response[type]) response[type] += amount;
    else response[type] = amount;
  });

  return response;
}

const LOC_FIRST_TIME_REPAYMENT_PREFERENCE_SET = 'LOC_FIRST_TIME_REPAYMENT_PREFERENCE_SET';

export const getFirstTimeRepaymentPreferenceKey = () => {
  const merchantId = store.getState()?.session?.user?.current;
  return `${LOC_FIRST_TIME_REPAYMENT_PREFERENCE_SET}--${merchantId}`;
};

export const setFirstTimeRepaymentPreference = () => {
  setItem(getFirstTimeRepaymentPreferenceKey(), 'true');
};

export const getFirstTimeRepaymentPreference = () => {
  return getItem(getFirstTimeRepaymentPreferenceKey());
};

export const showSettings = (user, repaymentFrequency) => {
  return Boolean(
    !user?.isCashOnCardEnabled && repaymentFrequency === REPAYMENT_FREQUENCY_TYPES.CUSTOM,
  );
};

// const NEW_MERCHANT_TIMESTAMP = 1661970600;

export const isMerchantNew = () => {
  /**
   * Hardcoding this to false to enable withdrawals for
   * all merchants
   *
   * TODO: do this based on splitz experiment
   */
  return false;
  // if (!liveByDate) return false;
  // return moment(liveByDate).unix().valueOf() > NEW_MERCHANT_TIMESTAMP;
};

export const isADayAgo = (date) => {
  if (!date) return false;
  const yesterday = moment().subtract(1, 'd');
  return moment(date).isBefore(yesterday);
};

export const isLenderLiquiloans = (withdrawalConfiguration) => {
  return (
    withdrawalConfiguration?.data?.configuration?.custom_partner_fields?.partner_id === 'LIQUILOANS'
  );
};

export const waitUntil = (condition, cb) => {
  const timer = setInterval(() => {
    if (condition()) {
      clearInterval(timer);
      cb();
    }
  }, 100);
};

export const devStackCheckoutConfig = {
  api: `${DEV_BASE_URL}/api/`,
  frameApi: `${DEV_BASE_URL}/api/`,
  frame: 'https://api-cc.func.razorpay.in/test/checkout.html',
  js: 'http://checkout.pronav.in/dist/',
};

const isProd = isProductionEnv();

export const loadCheckoutScript = () => {
  return new Promise((resolve, reject) => {
    if (window.Razorpay) {
      resolve('');
      return;
    }

    const scriptAlreadyPresent = document.querySelector(
      `script[src="https://checkout.razorpay.com/v1/checkout.js"]`,
    );

    if (!scriptAlreadyPresent) {
      // for dev ENV
      if (!isProd) {
        window.Razorpay = devStackCheckoutConfig;
      }
      const script = document.createElement('script');
      script.src = CHECKOUT_SRC;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    } else {
      waitUntil(() => window.Razorpay, resolve);
    }
  });
};

export const createAndProcessRepayment = async (paymentParams) => {
  const repaymentInstance = new Repayments();

  try {
    const {
      data: { payment_reference_id: order_id },
    } = await repaymentInstance.createRepayment(paymentParams);
    if (!order_id) {
      return Promise.reject(new Error('No Order Id found'));
    }
    return new Promise((resolve, reject) => {
      const razorpayInstance = new window.Razorpay({
        order_id,
        handler: (response) =>
          repaymentInstance.updateRepayment(response).then(resolve).catch(reject),
        modal: {
          ondismiss: reject,
        },
      });
      razorpayInstance.open();
    });
  } catch (error) {
    return Promise.reject(error);
  }
};

export const handleRepayment = async ({ repayAmount, merchantId, withdrawalId, metadata = {} }) => {
  try {
    await loadCheckoutScript();
    const paymentParams = {
      credit_id: merchantId,
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      currency: 'INR',
      payment_reference_type: COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER,
      product_entity_type: 'PRODUCT_ENTITY_TYPE_WITHDRAWAL',
      product_entity_reference_id: withdrawalId,
      amount: repayAmount,
      metadata,
    };
    return createAndProcessRepayment(paymentParams);
  } catch (error) {
    return Promise.reject(error);
  }
};
