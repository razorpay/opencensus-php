import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';

import modalReducer from 'rzp/modules/modals';
import sliderReducer from 'rzp/modules/slider';
import notificationsReducer from 'rzp/modules/notifications';

import teamReducer from 'merchantLA/reducers/team';
import sessionReducer from 'merchantLA/reducers/session';
import appReducer from 'merchantLA/reducers/app';
import homeReducer from 'merchantLA/reducers/home';
import settlementReducer from 'merchantLA/reducers/settlements/details';
import transferReducer from 'merchantLA/reducers/marketplace/transfer';
import reversalReducer from 'merchantLA/reducers/marketplace/reversal';
import creditsReducer from 'merchantLA/reducers/credits';
import {
  batchesReducer,
  batchDetailsReducer,
} from 'merchantLA/reducers/batches';

import {
  reversalsReducer,
  settlementsReducer,
  transfersReducer,
} from 'merchantLA/reducers/collection';

import { reportsReducer } from 'merchantLA/reducers/reports';

export default combineReducers({
  modal: modalReducer,
  slider: sliderReducer,
  notifications: notificationsReducer,
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
  batchDetails: batchDetailsReducer,
  batches: batchesReducer,
});
