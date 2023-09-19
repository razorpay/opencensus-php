import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import {
  lifeTimeWidgetsDataFormatter,
  widgetsDataFormatter,
  preVsPostMagicRTORateFormatter,
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
  loading: false,
};

const initialState = {
  startTime: null,
  endTime: null,
  loading: false,
  order_split: {
    ...defaultWidgetState,
  },
  order_split_cumulative: {
    ...defaultWidgetState,
  },
  manual_risk_order_split: {
    ...defaultWidgetState,
  },
  manual_risk_order_split_cumulative: {
    ...defaultWidgetState,
  },
  manual_review_order_split: {
    ...defaultWidgetState,
  },
  feedback_rate: {
    ...defaultWidgetState,
  },
  flagged_reason: {
    ...defaultWidgetState,
  },
  rto_reasons: {
    ...defaultWidgetState,
  },
  cod_rate: {
    ...defaultWidgetState,
  },
  risky_orders: {
    ...defaultWidgetState,
  },
  rto_rate: {
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
  prepay_order: {
    ...defaultWidgetState,
  },
  pre_vs_post_magic_rto_rate: {
    ...defaultWidgetState,
  },
  timedWidgetsFetching: false,
};

export default function magicRTOAnalyticsReducer(state = initialState, action) {
  switch (action.type) {
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
      return merge(state, {
        [action.widget]: { ...state[action.widget], loading: true, timedWidgetsFetching: true },
      });
    case ACTIONS.FETCH_WIDGET_ERROR:
      return merge(state, {
        [action.widget]: {
          ...state[action.widget],
          loading: false,
          data: [],
          timedWidgetsFetching: false,
        },
      });
    case ACTIONS.FETCH_WIDGET_SUCCESS: {
      const {
        payload: {
          data: { data: widgetData },
        },
      } = action;

      const updatedObj = widgetData?.[0]?.hasOwnProperty('premagic_rto_rate')
        ? preVsPostMagicRTORateFormatter(widgetData[0])
        : widgetsDataFormatter(widgetData);

      return merge(state, { ...updatedObj, timedWidgetsFetching: false });
    }
    default:
      return state;
  }
}
