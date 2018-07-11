import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';
import modalReducer from 'rzp/modules/modals';
import sliderReducer from 'rzp/modules/slider';
import notificationsReducer from 'rzp/modules/notifications';
import sessionReducer from 'merchant/modules/session';
import appReducer from 'merchant/modules/app';
import homeReducer from 'merchant/modules/home';
import profileReducer from 'merchant/modules/profile';
import customersReducer from 'merchant/modules/customers';
import itemsReducer from 'merchant/modules/items';
import settlementReducer from 'merchant/modules/settlements/details';
import teamReducer from 'rzp/modules/team';
import configReducer from 'merchant/modules/config';
import transferReducer from 'merchant/modules/marketplace/transfer';
import mpAccountsReducer from 'merchant/modules/marketplace/accounts';

import {
  reversalsReducer,
  settlementsReducer,
  transfersReducer,
} from 'rzp/modules/collection';

import { reportsReducer } from 'merchant/modules/reports';

export default combineReducers({
  modal: modalReducer,
  slider: sliderReducer,
  notifications: notificationsReducer,
  form: formReducer,
  session: sessionReducer,
  app: appReducer,
  home: homeReducer,
  profile: profileReducer,
  customers: customersReducer,
  items: itemsReducer,
  settlements: settlementsReducer,
  settlement: settlementReducer,
  team: teamReducer,
  config: configReducer,
  accounts: mpAccountsReducer,
  transfers: transfersReducer,
  transfer: transferReducer,
  reversals: reversalsReducer,
  reports: reportsReducer,
});
