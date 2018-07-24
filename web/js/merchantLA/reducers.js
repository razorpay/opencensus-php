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
import mpAccountsReducer from 'merchantLA/modules/marketplace/accounts';

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
  accounts: mpAccountsReducer,
  transfers: transfersReducer,
  transfer: transferReducer,
  reversal: reversalReducer,
  reversals: reversalsReducer,
  reports: reportsReducer,
});
