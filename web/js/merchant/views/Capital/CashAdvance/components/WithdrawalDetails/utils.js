import moment from 'moment';

export const getLastRepaidDate = (withdrawalDetails) => {
  const { data } = withdrawalDetails;

  if (!data.repayments || (data.repayments && data.repayments.length === 0)) return '--';

  const repaymentDates = data.repayments
    .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
    .map((repayment) => moment(repayment.created_at));
  return moment.max(repaymentDates).format('LL');
};

export const gaEventDispatcher = (eventObject) => {
  eventObject.eventCategory = 'Dashboard CA - Withdraw';
  window.rzpAnalytics?.(eventObject);
};

export const getAmountFromBalances = (balances = [], type) => {
  return balances?.find((item) => item?.balance_type === type)?.balance_amount || 0;
};

export const getAmountFromCollectedAmount = (amounts = [], type) => {
  return amounts?.find((item) => item?.type === type)?.amount || 0;
};
