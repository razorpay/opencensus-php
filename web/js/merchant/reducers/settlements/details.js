// Todo: delete this file, it's available in @dashboard/shared-utils
import Settlement from 'merchant/models/Settlement';
import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const SETTLEMENT_FETCH = 'SETTLEMENT_FETCH';
const SETTLEMENT_BREAKUP_FETCH = 'SETTLEMENT_BREAKUP_FETCH';
const SETTLEMENT_SCHEDULE_FETCH = 'SETTLEMENT_SCHEDULE_FETCH';
const HOLIDAY_LIST_FETCH = 'HOLIDAY_LIST_FETCH';
const SETTLEMENT_CONFIG_FETCH = 'SETTLEMENT_CONFIG_FETCH';
const ONDEMAND_BLOCKED_FETCH = 'ONDEMAND_BLOCKED_FETCH';
const SETTLEMENT_TIMELINE_FETCH = 'SETTLEMENT_TIMELINE_FETCH';
const PREVIOUS_SETTLEMENTS_FETCH = 'PREVIOUS_SETTLEMENTS_FETCH';

const INVALID_MERCHANT_CALL = 'INVALID_MERCHANT_CALL';

export const fetchItem = (id) => {
  const settlement = new Settlement();

  return {
    type: SETTLEMENT_FETCH,
    payload: settlement.fetch(id),
  };
};

export const fetchSchedule = () => {
  const settlement = new Settlement();
  return {
    type: SETTLEMENT_SCHEDULE_FETCH,
    payload: settlement.fetchSettlementSchedule(),
  };
};

export const fetchBreakupDetails = (params) => {
  const settlement = new Settlement(params);
  return {
    type: SETTLEMENT_BREAKUP_FETCH,
    payload: settlement.fetchBreakupDetails(),
  };
};

export const fetchHolidayList = () => {
  return {
    type: HOLIDAY_LIST_FETCH,
    payload: merchantFetch('settlement/holidays'),
  };
};

export const fetchSettlementConfig = () => {
  return {
    type: SETTLEMENT_CONFIG_FETCH,
    payload: merchantFetch({
      url: 'settlements/dashboard/merchant_config/get',
      method: 'post',
    }),
  };
};

export const fetchOnDemandBlocked = () => {
  return {
    type: ONDEMAND_BLOCKED_FETCH,
    payload: merchantFetch('settlements/ondemand/merchant/config'),
  };
};

export const fetchSettlementTimeline = (payload) => {
  return {
    type: SETTLEMENT_TIMELINE_FETCH,
    payload: merchantFetch({
      url: 'settlements/fetch_details',
      method: 'post',
      data: payload,
    }),
  };
};

export const fetchPreviousSettlements = (params) => {
  return {
    type: PREVIOUS_SETTLEMENTS_FETCH,
    payload: merchantFetch({
      url: `settlements`,
      method: 'get',
      data: params,
    }),
  };
};

const initialState = {
  loading: true,
  settlement: {},
  error: null,
  breakupDetails: {
    loading: false,
    items: [],
    error: null,
    isBreakupNew: null,
  },
  schedule: {
    loading: false,
    data: [],
    error: null,
  },
  holidayList: {
    loading: true,
    data: {},
    error: null,
  },
  config: {
    loading: false,
    data: {},
    error: null,
  },
  settleNowButtonDisabled: {
    loading: false,
    data: {},
  },
  timeline: {
    loading: false,
    data: null,
    error: null,
  },
  previousSettlements: {
    loading: true,
    data: {},
    error: null,
  },
};

export const isBreakupNew = (obj) => {
  delete obj.amountInINR;
  delete obj.resourceUrl;
  delete obj.resourceIdField;

  let newResponse = false;

  if ('tax' in obj && 'fee' in obj) newResponse = true;
  else newResponse = false;

  return newResponse;
};

const calculateSettledAmountPerComponent = (items) => {
  return items.reduce((acc, item) => {
    const component = { ...item };
    let amount = component.amount;

    if (component.type === 'debit') {
      amount = -1 * amount;
    }

    const { tax, fee } = component;
    component.settled_amount = amount - tax - fee;

    acc.push(component);

    return acc;
  }, []);
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${SETTLEMENT_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${SETTLEMENT_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        settlement: action.payload,
        error: null,
      });

    case `${SETTLEMENT_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        settlement: initialState.settlement,
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::PENDING`:
      return set(state, 'breakupDetails', {
        loading: true,
        items: [],
        error: null,
        isBreakupNew: null,
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::SUCCESS`: {
      const updatedItems = action.payload.data.items.map((item) => {
        return item.component === 'settlement.ondemand'
          ? { ...item, component: 'ondemand settlement' }
          : item;
      });

      const val = isBreakupNew(action.payload.data.items[0]);
      const breakupItems = val ? calculateSettledAmountPerComponent(updatedItems) : updatedItems;

      return set(state, 'breakupDetails', {
        loading: false,
        items: breakupItems,
        error: null,
        isBreakupNew: val,
      });
    }

    case `${SETTLEMENT_BREAKUP_FETCH}::ERROR`:
      return set(state, 'breakupDetails', {
        loading: false,
        items: [],
        error: action.payload.errors,
        isBreakupNew: null,
      });

    case `${SETTLEMENT_SCHEDULE_FETCH}::PENDING`:
      return set(state, 'schedule', {
        loading: true,
        data: [],
        error: null,
      });

    case `${SETTLEMENT_SCHEDULE_FETCH}::SUCCESS`:
      return set(state, 'schedule', {
        loading: false,
        data: action.payload.data,
        error: null,
      });

    case `${SETTLEMENT_SCHEDULE_FETCH}::ERROR`:
      return set(state, 'schedule', {
        loading: false,
        data: [],
        error: action.payload.errors,
      });

    case `${HOLIDAY_LIST_FETCH}::PENDING`:
      return set(state, 'holidayList', {
        loading: true,
        data: {},
        error: null,
      });

    case `${HOLIDAY_LIST_FETCH}::SUCCESS`:
      return set(state, 'holidayList', {
        loading: false,
        data: action.payload.data,
        error: null,
      });

    case `${HOLIDAY_LIST_FETCH}::ERROR`:
      return set(state, 'holidayList', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${SETTLEMENT_CONFIG_FETCH}::PENDING`:
      return set(state, 'config', {
        loading: true,
        data: {},
        error: null,
      });

    case `${SETTLEMENT_CONFIG_FETCH}::SUCCESS`:
      return set(state, 'config', {
        loading: false,
        data: action.payload?.data,
        error: null,
      });

    case `${SETTLEMENT_CONFIG_FETCH}::ERROR`:
      return set(state, 'config', {
        loading: false,
        data: {},
        error: action.payload?.errors,
      });

    case `${ONDEMAND_BLOCKED_FETCH}::SUCCESS`:
      return merge(state, {
        settleNowButtonDisabled: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${ONDEMAND_BLOCKED_FETCH}::PENDING`:
      return set(state, 'settleNowButtonDisabled', {
        loading: true,
        data: initialState.settleNowButtonDisabled.data,
        error: null,
      });

    case `${ONDEMAND_BLOCKED_FETCH}::ERROR`:
      return set(state, 'settleNowButtonDisabled', {
        loading: false,
        error: action.payload.errors,
        data: initialState.settleNowButtonDisabled.data,
      });

    case `${SETTLEMENT_TIMELINE_FETCH}::PENDING`:
      return set(state, 'timeline', {
        loading: true,
        data: null,
        error: null,
      });

    case `${SETTLEMENT_TIMELINE_FETCH}::SUCCESS`:
      return set(state, 'timeline', {
        loading: false,
        data: action.payload?.data,
        error: null,
      });

    case `${SETTLEMENT_TIMELINE_FETCH}::ERROR`:
      return set(state, 'timeline', {
        loading: false,
        data: null,
        error: action.payload?.errors,
      });

    case `${PREVIOUS_SETTLEMENTS_FETCH}::PENDING`:
      return set(state, 'previousSettlements', {
        loading: true,
        data: {},
        error: null,
      });

    case `${PREVIOUS_SETTLEMENTS_FETCH}::SUCCESS`:
      return set(state, 'previousSettlements', {
        loading: false,
        data: action.payload?.data,
        error: null,
      });

    case `${PREVIOUS_SETTLEMENTS_FETCH}::ERROR`:
      return set(state, 'previousSettlements', {
        loading: false,
        data: null,
        error: action.payload?.errors,
      });

    case INVALID_MERCHANT_CALL:
      return state;

    default:
      return state;
  }
};
