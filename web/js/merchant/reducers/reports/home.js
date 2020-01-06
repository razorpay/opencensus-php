import { combineReducers } from 'redux';

import { logListReducer } from './logs';
import { configListReducer } from './configs';

export default combineReducers({
  logs: logListReducer,
  configs: configListReducer,
});
