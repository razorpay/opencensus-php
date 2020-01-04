import { combineReducers } from 'redux';
import { logListReducer } from './logs';

export default combineReducers({
  logs: logListReducer,
});
