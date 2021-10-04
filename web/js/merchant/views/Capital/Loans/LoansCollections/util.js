import api from './api';
import {
  COLLECTIONS_PAYMENT_MODE,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  INSTALLMENT_STATUS,
  PAYMENT_STATUS,
  PLAN_STATUS,
} from './constants';
import { titleCase } from 'common/utils/rzp-utils';
import moment from 'moment';

export function fetchRepayments(product_entity_reference_id) {
  const ITEMS_PER_PAGE = 100;

  const fetchPaginatedRepayments = (skip) => {
    return api.getRepayments({
      product_entity_reference_id,
      count: ITEMS_PER_PAGE,
      skip,
    });
  };

  const fetchAllRepayments = async (allRepayments = [], start = 0) => {
    const skip = start * ITEMS_PER_PAGE;
    const {
      data: { repayments = [] },
    } = await fetchPaginatedRepayments(skip);
    allRepayments = [...allRepayments, ...repayments];
    return repayments.length === ITEMS_PER_PAGE
      ? fetchAllRepayments(allRepayments, start + 1)
      : allRepayments;
  };

  return fetchAllRepayments();
}

//eslint-disable-next-line
export async function fetchPlanAndInstallment() {
  const response = {};
  return api
    .getPlans()
    .then(({ data: { plans = [] } }) => {
      const loanPlan = plans.length
        ? plans.find(({ status }) => status === PLAN_STATUS.CREATED) || plans[plans.length - 1]
        : null;
      if (!loanPlan) return Promise.reject('No plan');

      response.plan = loanPlan;
      return api.getInstallment(loanPlan.id);
    })
    .then(({ data }) => {
      if (!data.installments.length) return Promise.reject('No installments');
      response.installments = data;
      return Promise.resolve(response);
    })
    .catch(() => Promise.reject('Failed to fetch plan and installment'));
}

//eslint-disable-next-line
export async function fetchLoanData() {
  const response = {};
  return fetchPlanAndInstallment()
    .then((res) => {
      response.installments = res.installments;
      response.plan = res.plan;
      const { PARTIALLY_PAID, PENDING, CREATED } = INSTALLMENT_STATUS;
      const currentInstallment =
        findFrom(res.installments.installments, [PARTIALLY_PAID, PENDING]) ||
        findFrom(res.installments.installments, [CREATED]);
      const installmentId = currentInstallment && currentInstallment.id;
      const product_entity_reference_id = res.plan.product_entity_reference_id; // Change this
      return Promise.all([
        fetchRepayments(product_entity_reference_id),
        currentInstallment ? api.getUpcomingPayments(installmentId) : { data: { schedule: [] } },
      ]);
    })
    .then(([repaymentsResponse, upcomingPaymentsResponse]) => {
      response.repayments = repaymentsResponse.filter(isRepaymentSuccess);
      response.upcomingPayments = upcomingPaymentsResponse.data;
      return Promise.resolve(response);
    })
    .catch(() => Promise.reject('Failed to fetch loan data'));

  /* 
  Response - {
      plans,
      installments,
      repayments,
      upcomingPayments
    } 
  */
}

export function isAutomaticRepayment(repayment) {
  return repayment.payment_mode === COLLECTIONS_PAYMENT_MODE.AUTO;
}

export function isManualRepayment(repayment) {
  return repayment.payment_mode === COLLECTIONS_PAYMENT_MODE.MANUAL;
}

export function isRepaymentFailed(repayment) {
  return repayment.status === PAYMENT_STATUS.FAILED;
}

export function isRepaymentSuccess(repayment) {
  return repayment.status === PAYMENT_STATUS.SUCCESS || repayment.status === PAYMENT_STATUS.SETTLED;
}

export function getCollectionMethod(repayment) {
  const { payment_meta, payment_reference_type } = repayment;
  if (isManualRepayment(repayment)) {
    if (payment_reference_type === COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER) {
      if (payment_meta) {
        return `${
          payment_meta.method !== 'upi'
            ? titleCase(payment_meta.method)
            : payment_meta.method.toUpperCase()
        }`;
      } else {
        return 'Netbanking / UPI';
      }
    } else {
      return 'Settlement Balance';
    }
  } else {
    return 'Auto-paid via Settlement Balance';
  }
}

export function sortRepayments(repayments) {
  return repayments.sort((a, b) => a.created_at - b.created_at);
}

export function getGroupedRepayments(repayments, n) {
  const sortedRepayments = sortRepayments(repayments).reverse();
  const groupedRepayments = {};
  const payment_mode_key = {
    PAYMENT_MODE_MANUAL: 'manual',
    PAYMENT_MODE_AUTOCOLLECTION: 'auto',
  };

  sortedRepayments.forEach((repayment, i) => {
    const day = moment.unix(sortedRepayments[i].created_at).format('D MMM');
    const paymentMode = payment_mode_key[repayment.payment_mode];
    const otherPaymentMode = paymentMode === 'manual' ? 'auto' : 'manual';
    const dayExists = day in groupedRepayments;
    const manualRepayment = isManualRepayment(repayment);
    const repaymentExists = dayExists && groupedRepayments[day][paymentMode].length;

    if (dayExists) {
      if (manualRepayment || (!repaymentExists && isRepaymentSuccess(repayment))) {
        groupedRepayments[day][paymentMode].push(repayment);
      }
    } else {
      groupedRepayments[day] = {
        [paymentMode]: [repayment],
        [otherPaymentMode]: [],
      };
    }
  });

  const lastNRepayments = Object.values(groupedRepayments)
    .reduce((acc, cur) => {
      return [...acc, ...cur.manual, ...cur.auto];
    }, [])
    .slice(0, n);

  return sortRepayments(lastNRepayments).reverse();
}

export function calculateLoanBreakup(installments) {
  const value = installments.reduce(
    (prev, cur) => {
      prev.totalPrincipalAmount += Number(cur.principal_amount);
      prev.totalPrincipalAmountCollected += Number(cur.principal_collected_amount);
      prev.totalInterestAmount += Number(cur.interest_amount);
      prev.totalInterestAmountCollected += Number(cur.interest_collected_amount);
      return prev;
    },
    {
      totalPrincipalAmount: 0,
      totalPrincipalAmountCollected: 0,
      totalInterestAmount: 0,
      totalInterestAmountCollected: 0,
    },
  );
  const {
    totalPrincipalAmount,
    totalPrincipalAmountCollected,
    totalInterestAmount,
    totalInterestAmountCollected,
  } = value;
  return {
    totalPrincipalAmount,
    totalPrincipalAmountCollected,
    totalInterestAmount,
    totalInterestAmountCollected,
  };
}

export function getTimeDifferenceIn(endDate, currentDate, format) {
  return moment.unix(parseInt(endDate, 10)).diff(moment.unix(parseInt(currentDate, 10)), format);
}

export function isPastDate(date) {
  const today = new Date();
  const ms = today.getTime();
  return ms > date;
}

export function findFrom(data, statusTypes) {
  return data.find(({ status }) => statusTypes.includes(status));
}

export const getRepaymentsWithOutstandingBalance = (repayments, outstandingBalance) => {
  return repayments
    .map((r) => {
      if (isRepaymentSuccess(r)) outstandingBalance = Number(outstandingBalance) - Number(r.amount);
      return {
        ...r,
        outstandingBalance,
      };
    })
    .reverse();
};
