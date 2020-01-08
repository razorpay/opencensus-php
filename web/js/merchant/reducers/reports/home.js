import { combineReducers } from 'redux';

import { merchantLogListReducer, partnerLogListReducer } from './logs';
import { merchantConfigListReducer, partnerConfigListReducer } from './configs';

export const merchantReportsReducer = combineReducers({
  logs: merchantLogListReducer,
  configs: merchantConfigListReducer,
});

export const partnerReportsReducer = combineReducers({
  logs: partnerLogListReducer,
  configs: partnerConfigListReducer,
});
