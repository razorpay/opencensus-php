import moment from 'moment';
import { merchantFetch, merchantFetchWithContentType } from 'merchant/utils/ajax';
import {
  calculateInitialDateRange,
  getQuery,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/utils';

const REDUCER_NAMESPACE = 'rto_analytics';

export const ACTIONS = {
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

  SET_TIME_RANGE: `${REDUCER_NAMESPACE}:TIME_RANGE:UPDATE`,
};

export const setTimeRange = (from, to) => {
  return {
    type: ACTIONS.SET_TIME_RANGE,
    payload: { from, to },
  };
};

export const fetchWidgetData = (
  widget,
  aggregation_type,
  startTime,
  endTime,
  additionalInfo = {},
) => {
  if (!startTime && !endTime) {
    const [start, end] = calculateInitialDateRange();
    startTime = moment(start).add(5, 'hours').add(30, 'minutes').unix();
    endTime = moment(end).unix();
  }

  const currentUnix = moment().unix();

  endTime = endTime > currentUnix ? currentUnix : endTime;

  const query = getQuery(widget, aggregation_type, startTime, endTime, additionalInfo);

  return {
    type: ACTIONS.FETCH_WIDGET_DATA,
    widget:
      widget === 'rto_rate' && additionalInfo?.premagic_flag
        ? 'pre_vs_post_magic_rto_rate'
        : widget,
    payload: merchantFetch({
      url: '1cc/rto_prediction_service/dashboard',
      method: 'post',
      data: { widgets: query },
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
