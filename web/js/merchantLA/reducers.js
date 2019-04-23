import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';

import modalReducer from 'rzp/modules/modals';
import sliderReducer from 'rzp/modules/slider';
import notificationsReducer from 'rzp/modules/notifications';

import teamReducer from 'merchantLA/modules/team';
import sessionReducer from 'merchantLA/modules/session';
import appReducer from 'merchantLA/modules/app';
import homeReducer from 'merchantLA/modules/home';
import settlementReducer from 'merchantLA/modules/settlements/details';
import transferReducer from 'merchantLA/modules/marketplace/transfer';
import reversalReducer from 'merchantLA/modules/marketplace/reversal';
import creditsReducer from 'merchantLA/modules/credits';
import {
  batchesReducer,
  batchDetailsReducer,
} from 'merchantLA/modules/batches';

import {
  reversalsReducer,
  settlementsReducer,
  transfersReducer,
} from 'merchantLA/modules/collection';

import { reportsReducer } from 'merchantLA/modules/reports';

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
