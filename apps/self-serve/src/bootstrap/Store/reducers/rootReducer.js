import { combineReducers } from 'redux';

import { paymentsReducer } from './paymentsReducer';
import navigatorReducer from './navigatorReducer';

export default combineReducers({
  payments: paymentsReducer,
  navigator: navigatorReducer,
});
