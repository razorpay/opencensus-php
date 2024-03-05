import { combineReducers } from 'redux';

import { paymentsReducer } from './paymentsReducer';

export default combineReducers({
  payments: paymentsReducer,
});
