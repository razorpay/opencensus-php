import {
  SET_DATE_RANGE,
  SET_METRIC,
  SET_CHART_OPTIONS,
  SET_INTERVAL,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import { EntityAnalyticsState, RiskAnalyticsAction } from './EntityAnalytics/types';

const riskAnalyticsReducer = (
  state: EntityAnalyticsState,
  action: RiskAnalyticsAction,
): EntityAnalyticsState & { data?: [] } => {
  switch (action.type) {
    case SET_DATE_RANGE:
      return { ...state, dateRange: action.payload };
    case SET_METRIC:
      return { ...state, metric: action.payload };
    case SET_CHART_OPTIONS:
      return { ...state, graphOptions: action.payload };
    case SET_INTERVAL:
      return { ...state, interval: action.payload };
    default:
      return state;
  }
};

export default riskAnalyticsReducer;
