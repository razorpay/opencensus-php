import { combineReducers } from 'redux';

import { configListReducer } from './configs';
import { logListReducer } from './logs';

export default combineReducers({
  logs: logListReducer,
  configs: configListReducer,
});
