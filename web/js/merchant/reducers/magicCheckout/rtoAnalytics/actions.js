import { merchantFetch, merchantFetchWithContentType } from 'merchant/utils/ajax';
import {
  getDefaultQuery,
  getTimedQuery,
  calculateInitialDateRange,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/utils';

const REDUCER_NAMESPACE = 'rto_analytics';

export const ACTIONS = {
  FETCH_ALL_WIDGETS: `${REDUCER_NAMESPACE}:FETCH:WIDGETS`,
  FETCH_ALL_WIDGETS_ERROR: `${REDUCER_NAMESPACE}:FETCH:WIDGETS::ERROR`,
  FETCH_ALL_WIDGETS_SUCCESS: `${REDUCER_NAMESPACE}:FETCH:WIDGETS::SUCCESS`,
  FETCH_ALL_WIDGETS_PENDING: `${REDUCER_NAMESPACE}:FETCH:WIDGETS::PENDING`,

  UNBLOCK_VALUE: `${REDUCER_NAMESPACE}:UNBLOCK:VALUE`,
  UNBLOCK_VALUE_ERROR: `${REDUCER_NAMESPACE}:UNBLOCK:VALUE::ERROR`,
  UNBLOCK_VALUE_SUCCESS: `${REDUCER_NAMESPACE}:UNBLOCK:VALUE::SUCCESS`,
  UNBLOCK_VALUE_PENDING: `${REDUCER_NAMESPACE}:UNBLOCK:VALUE::PENDING`,

  BLOCK_VALUE: `${REDUCER_NAMESPACE}:BLOCK:VALUE`,
  BLOCK_VALUE_ERROR: `${REDUCER_NAMESPACE}:BLOCK:VALUE::ERROR`,
  BLOCK_VALUE_SUCCESS: `${REDUCER_NAMESPACE}:BLOCK:VALUE::SUCCESS`,
  BLOCK_VALUE_PENDING: `${REDUCER_NAMESPACE}:BLOCK:VALUE::PENDING`,

  FETCH_WIDGET_DATA: `${REDUCER_NAMESPACE}:FETCH:WIDGET`,
  FETCH_WIDGET_SUCCESS: `${REDUCER_NAMESPACE}:FETCH:WIDGET::SUCCESS`,
  FETCH_WIDGET_PENDING: `${REDUCER_NAMESPACE}:FETCH:WIDGET::PENDING`,
  FETCH_WIDGET_ERROR: `${REDUCER_NAMESPACE}:FETCH:WIDGET::ERROR`,

  FETCH_TIMED_WIDGETS_DATA: `${REDUCER_NAMESPACE}:FETCH:TIMED:WIDGETS`,
  FETCH_TIMED_WIDGETS_DATA_SUCCESS: `${REDUCER_NAMESPACE}:FETCH:TIMED:WIDGETS::SUCCESS`,
  FETCH_TIMED_WIDGETS_DATA_ERROR: `${REDUCER_NAMESPACE}:FETCH:TIMED:WIDGETS::ERROR`,
  FETCH_TIMED_WIDGETS_DATA_PENDING: `${REDUCER_NAMESPACE}:FETCH:TIMED:WIDGETS::PENDING`,

  SET_TIME_RANGE: `${REDUCER_NAMESPACE}:TIME_RANGE:UPDATE`,
};

export const setTimeRange = (from, to) => {
  return {
    type: ACTIONS.SET_TIME_RANGE,
    payload: { from, to },
  };
};

export const fetchWidgetData = (widget, queryObj) => {
  const query = { widgets: [queryObj] };
  return {
    type: ACTIONS.FETCH_WIDGET_DATA,
    widget,
    payload: merchantFetch({
      url: '1cc/rto_prediction_service/dashboard',
      method: 'post',
      data: query,
    }),
  };
};

const CUSTOM_HEADERS = {
  'X-Creator-Id': window.rzp_user?.user?.id,
  'X-Creator-Type': window.rzp_user?.role,
};

export const unblockValue = (payload) => {
  const { attribute_type, attribute_value } = payload;
  return {
    type: ACTIONS.UNBLOCK_VALUE,
    payload: merchantFetch({
      url: `1cc/rto_prediction_service/cod_eligibility_attribute/blacklist/${attribute_type}/${attribute_value}`,
      method: 'delete',
    }),
    data: payload,
    widget: `rto_by_${attribute_type}`,
  };
};

export const blockValue = (payload) => {
  const { attribute_type } = payload?.cod_eligibility_attributes?.[0];
  return {
    type: ACTIONS.BLOCK_VALUE,
    payload: merchantFetchWithContentType({
      url: '1cc/rto_prediction_service/cod_eligibility_attribute/blacklist/upsert/bulk',
      method: 'post',
      data: payload,
      headers: CUSTOM_HEADERS,
    }),
    data: payload?.cod_eligibility_attributes?.[0],
    widget: `rto_by_${attribute_type}`,
  };
};
export const fetchTimedWidgetsData = (startTime, endTime) => {
  const query = getTimedQuery(startTime, endTime);

  return {
    type: ACTIONS.FETCH_TIMED_WIDGETS_DATA,
    payload: merchantFetch({
      url: '1cc/rto_prediction_service/dashboard',
      method: 'post',
      data: query,
    }),
  };
};

export const fetchAllWidgetData = (startTime, endTime) => {
  if (!startTime || !endTime) {
    const [start, end] = calculateInitialDateRange();
    startTime = start;
    endTime = end;
  }
  const query = getDefaultQuery(startTime, endTime);
  return {
    type: ACTIONS.FETCH_ALL_WIDGETS,
    payload: merchantFetch({
      url: '1cc/rto_prediction_service/dashboard',
      method: 'post',
      data: query,
    }),
  };
};
