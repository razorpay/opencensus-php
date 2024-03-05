/* eslint-disable import/order */
import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';

// import modalReducer from 'merchant_common/reducers/modals';
import sliderReducer from 'merchant_common/reducers/slider';
// import notificationsReducer from 'merchant_common/reducers/notifications';

import teamReducer from 'merchantLA/reducers/team';
import sessionReducer from 'merchantLA/reducers/session';
import appReducer from 'merchantLA/reducers/app';
import homeReducer from 'merchantLA/reducers/home';
import settlementReducer from 'merchantLA/reducers/settlements/details';
import transferReducer from 'merchantLA/reducers/marketplace/transfer';
import reversalReducer from 'merchantLA/reducers/marketplace/reversal';
import creditsReducer from 'merchantLA/reducers/credits';
import reportsReducer from 'merchantLA/reducers/reports/home';
import { batchesReducer, batchDetailsReducer } from 'merchantLA/reducers/batches';
import { reportsReducer as reportsCoreReducer } from 'merchant_common/views/Reports/redux/reducer';

import {
  reversalsReducer,
  settlementsReducer,
  transfersReducer,
} from 'merchantLA/reducers/collection';

export default combineReducers({
  // modal: modalReducer,
  slider: sliderReducer,
  // notifications: notificationsReducer,
  form: formReducer,
  session: sessionReducer,
  app: appReducer,
  home: homeReducer,
  settlements: settlementsReducer,
  settlement: settlementReducer,
  team: teamReducer,
  transfers: transfersReducer,
  transfer: transferReducer,
  reversal: reversalReducer,
  reversals: reversalsReducer,
  credits: creditsReducer,
  reports: reportsReducer,
  reportsCore: reportsCoreReducer,
  batchDetails: batchDetailsReducer,
  batches: batchesReducer,
});
