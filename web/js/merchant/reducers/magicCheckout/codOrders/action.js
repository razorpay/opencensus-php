import moment from 'moment';
import { merchantFetch } from 'merchant/utils/ajax';
import { calculateInitialDateRange } from 'merchant/reducers/magicCheckout/rtoAnalytics/utils';
import { MIN_START_DATE } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const REDUCER_NAMESPACE = 'RTO_RECOMMENDATION';
const CUSTOM_HEADERS = {
  'X-Dashboard-User-Id': window.rzp_user?.user?.id,
};

export const ACTIONS = {
  FETCH_ORDER_INFO: `${REDUCER_NAMESPACE}_ORDER_INFO_FETCH`,
  FETCH_ORDER_INFO_PENDING: `${REDUCER_NAMESPACE}_ORDER_INFO_FETCH::PENDING`,
  FETCH_ORDER_INFO_SUCCESS: `${REDUCER_NAMESPACE}_ORDER_INFO_FETCH::SUCCESS`,
  FETCH_ORDER_INFO_ERROR: `${REDUCER_NAMESPACE}_ORDER_INFO_FETCH::ERROR`,

  FETCH_COD_ORDERS: `${REDUCER_NAMESPACE}_COD_ORDERS_FETCH`,
  FETCH_COD_ORDERS_PENDING: `${REDUCER_NAMESPACE}_COD_ORDERS_FETCH::PENDING`,
  FETCH_COD_ORDERS_SUCCESS: `${REDUCER_NAMESPACE}_COD_ORDERS_FETCH::SUCCESS`,
  FETCH_COD_ORDERS_ERROR: `${REDUCER_NAMESPACE}_COD_ORDERS_FETCH::ERROR`,

  UPDATE_FILTERS: `${REDUCER_NAMESPACE}_UPDATE::FILTERS`,

  SET_TIME_RANGE: `${REDUCER_NAMESPACE}_SET::TIME_RANGE`,

  REVIEW_COD_ORDERS: `${REDUCER_NAMESPACE}_COD_ORDERS_REVIEW`,
  REVIEW_COD_ORDERS_PENDING: `${REDUCER_NAMESPACE}_COD_ORDERS_REVIEW::PENDING`,
  REVIEW_COD_ORDERS_SUCCESS: `${REDUCER_NAMESPACE}_COD_ORDERS_REVIEW::SUCCESS`,
  REVIEW_COD_ORDERS_ERROR: `${REDUCER_NAMESPACE}_COD_ORDERS_REVIEW::ERROR`,
};

export const updateFilters = (payload) => {
  return {
    type: ACTIONS.UPDATE_FILTERS,
    payload,
  };
};

export const fetchCODOrders = (payload) => {
  let { from, to } = payload;
  const duration = [-7, 'days'];
  if (!from || !to) {
    const [start, end] = calculateInitialDateRange(duration);
    //checking if the startDate is before the accepted minimum start date
    if (moment(start).unix() < moment(MIN_START_DATE).unix()) {
      from = moment(MIN_START_DATE).unix();
      to = moment(end).unix();
    } else {
      from = moment(start).unix();
      to = moment(end).unix();
    }
  }
  return {
    type: ACTIONS.FETCH_COD_ORDERS,
    payload: merchantFetch({
      url: '1cc/cod/orders',
      data: { ...payload, from, to },
    }),
    data: { from, to },
  };
};

export const setTimeRange = (from, to) => {
  let startUnix = moment(from).unix();
  let endUnix = moment(to).unix();
  //checking if the startDate is before the accepted minimum start date
  if (startUnix < moment(MIN_START_DATE).unix()) {
    startUnix = moment(MIN_START_DATE).unix();
  }

  if (endUnix > moment().unix()) {
    endUnix = moment().unix();
  }
  return {
    type: ACTIONS.SET_TIME_RANGE,
    payload: {
      from: startUnix,
      to: endUnix,
    },
  };
};

export const reviewCODOrders = (payload) => {
  return {
    type: ACTIONS.REVIEW_COD_ORDERS,
    payload: merchantFetch({
      url: '1cc/orders/cod/review',
      method: 'post',
      data: payload,
      header: CUSTOM_HEADERS,
    }),
    data: {
      reviewType: payload.action,
    },
  };
};

export const fetchOrderInfo = (payload) => ({
  type: ACTIONS.FETCH_ORDER_INFO,
  payload: merchantFetch({
    url: '1cc/cod/orders',
    data: payload,
  }),
});
