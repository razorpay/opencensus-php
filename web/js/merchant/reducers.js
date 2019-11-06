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
import disputeReducer from 'merchant/modules/disputes/details';
import settlementReducer from 'merchant/modules/settlements/details';
import webhooksReducer from 'merchant/modules/webhooks';
import keysReducer from 'merchant/modules/keys';
import creditsReducer from 'merchant/modules/credits';
import configReducer from 'merchant/modules/config';
import activationReducer from 'merchant/modules/activation';
import refundReducer from 'merchant/modules/refunds/details';
import paymentReducer from 'merchant/modules/payments/details';
import transferReducer from 'merchant/modules/marketplace/transfer';
import reversalReducer from 'merchant/modules/marketplace/reversal';
import mpAccountsReducer from 'merchant/modules/marketplace/accounts';
import referralsReducer from 'merchant/modules/referrals';
import applicationsReducer from 'merchant/modules/applications';
import {
  virtualAccountsReducer,
  virtualAccountReducer,
} from 'merchant/modules/virtualaccounts';
import submerchantReducer from 'merchant/modules/submerchant';
import commissionReducer, {
  commAggSingleDayReducer,
} from 'merchant/modules/commission';
import statesReducer from 'merchant/modules/states';
import taxesReducer from 'merchant/modules/taxes';
import tokenReducer from 'merchant/modules/token';
import onboardingReducer from 'merchant/modules/onboarding';
import remindersReducer from 'merchant/modules/reminders';

import authLinkReducer from 'merchant/modules/auth_link';

import {
  refundBatchesReducer,
  PaymentBatchIdsReducer,
  batchDetailsReducer,
  batchesReducer,
} from 'merchant/modules/batches';

import {
  paymentsReducer,
  ordersReducer,
  transfersReducer,
  reversalsReducer,
  mpPaymentsReducer,
  refundsReducer,
  settlementsReducer,
  disputesReducer,
  submerchantsReducer,
  authLinksReducer,
  tokensReducer,
  commissionsReducer,
  commissionsAggregateReducer,
  invitationsReducer,
} from 'merchant/modules/collection';

import { teamReducer } from 'merchant/modules/team';

import {
  subscriptionsReducer,
  subscriptionReducer,
} from 'merchant/modules/subscriptions';
import { plansReducer, planReducer } from 'merchant/modules/plans';
import { addOnsReducer } from 'merchant/modules/addons';
import { reportsReducer } from 'merchant/modules/reports';

import wysiwygReducer from 'merchant/modules/wysiwyg';

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
  paymentBatchIds: PaymentBatchIdsReducer,
  refundbatches: refundBatchesReducer,
  batchDetails: batchDetailsReducer,
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
  disputes: disputesReducer,
  dispute: disputeReducer,
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
  reversal: reversalReducer,
  reversals: reversalsReducer,
  virtualaccounts: virtualAccountsReducer,
  virtualaccount: virtualAccountReducer,
  states: statesReducer,
  taxes: taxesReducer,
  reports: reportsReducer,
  submerchants: submerchantsReducer,
  submerchant: submerchantReducer,
  commisions: commissionsReducer,
  commission: commissionReducer,
  commissionsAggregate: commissionsAggregateReducer,
  commAggSingleDay: commAggSingleDayReducer,
  wysiwyg: wysiwygReducer,
  authLinks: authLinksReducer,
  authLink: authLinkReducer,
  tokens: tokensReducer,
  token: tokenReducer,
  batches: batchesReducer,
  invitations: invitationsReducer,
  onboarding: onboardingReducer,
  reminders: remindersReducer,
});
