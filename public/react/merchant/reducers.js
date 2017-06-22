import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';
import modalReducer from 'rzp/modules/modals';
import sliderReducer from 'rzp/modules/slider';
import notificationsReducer from 'rzp/modules/notifications';
import sessionReducer from 'merchant/modules/session';
import appReducer from 'merchant/modules/app';
import homeReducer from 'merchant/modules/home';
import invoicesReducer from 'merchant/modules/invoices/list';
import invoiceDetailsReducer from 'merchant/modules/invoices/details';
import plansReducer from 'merchant/modules/plans';
import profileReducer from 'merchant/modules/profile';
import customersReducer from 'merchant/modules/customers';
import itemsReducer from 'merchant/modules/items';
import orderReducer from 'merchant/modules/orders/details';
import settlementReducer from 'merchant/modules/settlements/details';
import webhooksReducer from 'merchant/modules/webhooks';
import keysReducer from 'merchant/modules/keys';
import creditsReducer from 'merchant/modules/credits';
import teamReducer from 'merchant/modules/team';
import configReducer from 'merchant/modules/config';
import activationReducer from 'merchant/modules/activation';
import refundReducer from 'merchant/modules/refunds/details';
import paymentReducer from 'merchant/modules/payments/details';
import mpAccountsReducer from 'merchant/modules/marketplace/accounts';
import referralsReducer from 'merchant/modules/referrals';
import {
  virtualAccountsReducer,
  virtualAccountReducer,
} from 'merchant/modules/virtualaccounts';

import {
  refundBatchesReducer,
  paymentLinkBatchesReducer,
} from 'merchant/modules/batches';

import {
  paymentsReducer,
  ordersReducer,
  transfersReducer,
  reversalsReducer,
  mpPaymentsReducer,
  refundsReducer,
  settlementsReducer,
  subscriptionsReducer,
} from 'rzp/modules/collection';

export default combineReducers({
  modal: modalReducer,
  slider: sliderReducer,
  notifications: notificationsReducer,
  form: formReducer,
  session: sessionReducer,
  app: appReducer,
  home: homeReducer,
  invoices: invoicesReducer,
  invoice: invoiceDetailsReducer,
  paymentlinkbatches: paymentLinkBatchesReducer,
  refundbatches: refundBatchesReducer,
  subscriptions: subscriptionsReducer,
  plans: plansReducer,
  profile: profileReducer,
  customers: customersReducer,
  items: itemsReducer,
  orders: ordersReducer,
  order: orderReducer,
  payments: paymentsReducer,
  payment: paymentReducer,
  settlements: settlementsReducer,
  settlement: settlementReducer,
  webhooks: webhooksReducer,
  keys: keysReducer,
  credits: creditsReducer,
  team: teamReducer,
  config: configReducer,
  activation: activationReducer,
  refunds: refundsReducer,
  refund: refundReducer,
  referrals: referralsReducer,
  accounts: mpAccountsReducer,
  mpPayments: mpPaymentsReducer,
  transfers: transfersReducer,
  reversals: reversalsReducer,
  virtualaccounts: virtualAccountsReducer,
  virtualaccount: virtualAccountReducer,
});
