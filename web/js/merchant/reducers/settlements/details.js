import Settlement from 'merchant/models/Settlement';
import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const SETTLEMENT_FETCH = 'SETTLEMENT_FETCH';
const SETTLEMENT_BREAKUP_FETCH = 'SETTLEMENT_BREAKUP_FETCH';
const SETTLEMENT_SCHEDULE_FETCH = 'SETTLEMENT_SCHEDULE_FETCH';
const HOLIDAY_LIST_FETCH = 'HOLIDAY_LIST_FETCH';

export const fetchItem = id => {
  let settlement = new Settlement();

  return {
    type: SETTLEMENT_FETCH,
    payload: settlement.fetch(id),
  };
};

export const fetchSchedule = () => {
  let settlement = new Settlement();
  return {
    type: SETTLEMENT_SCHEDULE_FETCH,
    payload: settlement.fetchSettlementSchedule(),
  };
};

export const fetchBreakupDetails = params => {
  let settlement = new Settlement(params);
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

let initialState = {
  loading: true,
  settlement: {},
  error: null,
  breakupDetails: {
    loading: false,
    items: [],
    error: null,
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
};

export default function(state = initialState, action) {
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
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::SUCCESS`:
      return set(state, 'breakupDetails', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::ERROR`:
      return set(state, 'breakupDetails', {
        loading: false,
        items: [],
        error: action.payload.errors,
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

    default:
      return state;
  }
}
