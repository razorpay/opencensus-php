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
import profileReducer from 'merchant/modules/profile';
import customersReducer from 'merchant/modules/customers';
import itemsReducer from 'merchant/modules/items';
import orderReducer from 'merchant/modules/orders/details';
import settlementReducer from 'merchant/modules/settlements/details';
import webhooksReducer from 'merchant/modules/webhooks';
import keysReducer from 'merchant/modules/keys';
import creditsReducer from 'merchant/modules/credits';
import teamReducer from 'rzp/modules/team';
import configReducer from 'merchant/modules/config';
import activationReducer from 'merchant/modules/activation';
import refundReducer from 'merchant/modules/refunds/details';
import paymentReducer from 'merchant/modules/payments/details';
import transferReducer from 'merchant/modules/marketplace/transfer';
import mpAccountsReducer from 'merchant/modules/marketplace/accounts';
import referralsReducer from 'merchant/modules/referrals';
import applicationsReducer from 'merchant/modules/applications';
import {
  virtualAccountsReducer,
  virtualAccountReducer,
} from 'merchant/modules/virtualaccounts';

import {
  refundBatchesReducer,
  paymentLinkBatchesReducer,
  PaymentBatchIdsReducer,
} from 'merchant/modules/batches';

import {
  paymentsReducer,
  ordersReducer,
  transfersReducer,
  reversalsReducer,
  mpPaymentsReducer,
  refundsReducer,
  settlementsReducer,
} from 'rzp/modules/collection';

import {
  subscriptionsReducer,
  subscriptionReducer,
} from 'merchant/modules/subscriptions';
import { plansReducer, planReducer } from 'merchant/modules/plans';
import { addOnsReducer } from 'merchant/modules/addons';

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
  paymentBatchIds: PaymentBatchIdsReducer,
  refundbatches: refundBatchesReducer,
  subscriptions: subscriptionsReducer,
  subscription: subscriptionReducer,
  plans: plansReducer,
  plan: planReducer,
  addons: addOnsReducer,
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
  applications: applicationsReducer,
  referrals: referralsReducer,
  accounts: mpAccountsReducer,
  mpPayments: mpPaymentsReducer,
  transfers: transfersReducer,
  transfer: transferReducer,
  reversals: reversalsReducer,
  virtualaccounts: virtualAccountsReducer,
  virtualaccount: virtualAccountReducer,
});
