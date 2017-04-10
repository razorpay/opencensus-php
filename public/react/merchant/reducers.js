import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';
import sessionReducer from 'merchant/modules/session';
import modalReducer from 'rzp/modules/modals';
import notificationsReducer from 'rzp/modules/notifications';
import homeReducer from 'merchant/modules/home'
import invoicesReducer from 'merchant/modules/invoices/list';
import invoiceDetailsReducer from 'merchant/modules/invoices/details';
import subscriptionsReducer from 'merchant/modules/subscriptions';
import plansReducer from 'merchant/modules/plans';
import customersReducer from 'merchant/modules/customers';
import itemsReducer from 'merchant/modules/items';
import ordersReducer from 'merchant/modules/orders/list';
import orderReducer from 'merchant/modules/orders/details';
import settlementsReducer from 'merchant/modules/settlements/list';
import settlementReducer from 'merchant/modules/settlements/details';
import webhooksReducer from 'merchant/modules/webhooks';
import keysReducer from 'merchant/modules/keys';
import creditsReducer from 'merchant/modules/credits';
import teamReducer from 'merchant/modules/team';
import configReducer from 'merchant/modules/config';
import refundsReducer from 'merchant/modules/refunds/list';
import refundReducer from 'merchant/modules/refunds/details';
import batchuploadsReducer from 'merchant/modules/refunds/batchuploads';

export default combineReducers({
  home: homeReducer,
  form: formReducer,
  session: sessionReducer,
  modal: modalReducer,
  notifications: notificationsReducer,
  invoices: invoicesReducer,
  invoice: invoiceDetailsReducer,
  subscriptions: subscriptionsReducer,
  plans: plansReducer,
  customers: customersReducer,
  items: itemsReducer,
  orders: ordersReducer,
  order: orderReducer,
  settlements: settlementsReducer,
  settlement: settlementReducer,
  webhooks: webhooksReducer,
  keys: keysReducer,
  credits: creditsReducer,
  team: teamReducer,
  config: configReducer,
  credits: creditsReducer,
  refunds: refundsReducer,
  refund: refundReducer,
  batchuploads: batchuploadsReducer,
});
