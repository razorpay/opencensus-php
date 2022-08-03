import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import {
  TIMED_WIDGETS,
  lifeTimeWidgetsDataFormatter,
  widgetsDataFormatter,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/utils';

const defaultWidgetState = {
  loading: false,
  data: null,
  updatedAt: null,
};

const lifetimeWidgetsDefaultState = {
  data: null,
  updatedAt: null,
  modalLoading: false,
};

const initialState = {
  startTime: null,
  endTime: null,
  loading: true,
  order_split: {
    ...defaultWidgetState,
  },
  order_split_cumulative: {
    ...defaultWidgetState,
  },
  feedback_rate: {
    ...defaultWidgetState,
  },
  flagged_reason: {
    ...defaultWidgetState,
  },
  cost_saving: {
    ...defaultWidgetState,
  },
  intelligence_stat_performance: {
    ...defaultWidgetState,
  },
  rto_by_zipcode: {
    ...lifetimeWidgetsDefaultState,
  },
  rto_by_ip: {
    ...lifetimeWidgetsDefaultState,
  },
  timedWidgetsFetching: false,
};

export default function magicRTOAnalyticsReducer(state = initialState, action) {
  switch (action.type) {
    case ACTIONS.FETCH_ALL_WIDGETS_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.FETCH_ALL_WIDGETS_ERROR:
      return merge(state, { loading: false });
    case ACTIONS.FETCH_ALL_WIDGETS_SUCCESS: {
      const {
        data: { data: widgetsData },
      } = action.payload;

      const updateObj = widgetsDataFormatter(widgetsData);

      return merge(state, { loading: false, ...updateObj });
    }
    case ACTIONS.BLOCK_VALUE_PENDING:
    case ACTIONS.UNBLOCK_VALUE_PENDING:
      return merge(state, {
        loading: false,
        [action.widget]: { ...state[action.widget], modalLoading: true },
      });
    case ACTIONS.BLOCK_VALUE_SUCCESS:
    case ACTIONS.UNBLOCK_VALUE_SUCCESS: {
      const updateObj = lifeTimeWidgetsDataFormatter(
        action.data,
        action.widget,
        state[action.widget],
      );

      return merge(state, { loading: false, ...updateObj });
    }
    case ACTIONS.BLOCK_VALUE_ERROR:
    case ACTIONS.UNBLOCK_VALUE_ERROR:
      return merge(state, {
        loading: false,
        [action.widget]: { ...state[action.widget], modalLoading: false },
      });
    case ACTIONS.SET_TIME_RANGE:
      return merge(state, { startTime: action.payload?.from, endTime: action.payload?.to });
    case ACTIONS.FETCH_WIDGET_PENDING:
      return merge(state, { [action.widget]: { ...state[action.widget], loading: true } });
    case ACTIONS.FETCH_WIDGET_ERROR:
      return merge(state, {
        [action.widget]: { ...state[action.widget], loading: false, data: [] },
      });
    case ACTIONS.FETCH_WIDGET_SUCCESS: {
      const {
        widget,
        payload: {
          data: { data: widgetData },
        },
      } = action;

      return merge(state, {
        [widget]: {
          ...state[widget],
          data: widgetData[0][widget],
          loading: false,
          updatedAt: Number(widgetData[0].updated_at),
        },
      });
    }
    case ACTIONS.FETCH_TIMED_WIDGETS_DATA_PENDING:
    case ACTIONS.FETCH_TIMED_WIDGETS_DATA_ERROR: {
      const updateObj = {};
      TIMED_WIDGETS.forEach((widget) => {
        updateObj[widget] = { ...state[widget], loading: !state[widget].loading };
      });
      updateObj.order_split_cumulative = {
        ...state.order_split_cumulative,
        loading: !state.order_split_cumulative.loading,
      };

      return merge(state, { ...updateObj, timedWidgetsFetching: true });
    }
    case ACTIONS.FETCH_TIMED_WIDGETS_DATA_SUCCESS: {
      const {
        data: { data: widgetsData },
      } = action.payload;
      const updateObj = widgetsDataFormatter(widgetsData);
      return merge(state, { timedWidgetsFetching: false, ...updateObj });
    }
    default:
      return state;
  }
}
