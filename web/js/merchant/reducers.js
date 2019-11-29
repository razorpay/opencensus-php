import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';
import modalReducer from 'merchant_common/reducers/modals';
import sliderReducer from 'merchant_common/reducers/slider';
import notificationsReducer from 'merchant_common/reducers/notifications';
import sessionReducer from 'merchant/reducers/session';
import appReducer from 'merchant/reducers/app';
import homeReducer from 'merchant/reducers/home';
import invoicesReducer from 'merchant/reducers/invoices/list';
import invoiceDetailsReducer from 'merchant/reducers/invoices/details';
import profileReducer from 'merchant/reducers/profile';
import customersReducer from 'merchant/reducers/customers';
import itemsReducer from 'merchant/reducers/items';
import orderReducer from 'merchant/reducers/orders/details';
import disputeReducer from 'merchant/reducers/disputes/details';
import settlementReducer from 'merchant/reducers/settlements/details';
import webhooksReducer from 'merchant/reducers/webhooks';
import keysReducer from 'merchant/reducers/keys';
import bMerchantReducer from 'merchant/reducers/b-merchants';
import creditsReducer from 'merchant/reducers/credits';
import configReducer from 'merchant/reducers/config';
import activationReducer from 'merchant/reducers/activation';
import activationWizardReducer from 'merchant/reducers/activationWizard';
import refundReducer from 'merchant/reducers/refunds/details';
import paymentReducer from 'merchant/reducers/payments/details';
import transferReducer from 'merchant/reducers/marketplace/transfer';
import reversalReducer from 'merchant/reducers/marketplace/reversal';
import mpAccountsReducer from 'merchant/reducers/marketplace/accounts';
import referralsReducer from 'merchant/reducers/referrals';
import applicationsReducer from 'merchant/reducers/applications';
import {
  virtualAccountsReducer,
  virtualAccountReducer,
} from 'merchant/reducers/virtualaccounts';
import submerchantReducer from 'merchant/reducers/submerchant';
import commissionReducer, {
  commAggSingleDayReducer,
} from 'merchant/reducers/commission';
import registrationLinkReducer from 'merchant/reducers/registration_link';
import statesReducer from 'merchant/reducers/states';
import taxesReducer from 'merchant/reducers/taxes';
import tokenReducer from 'merchant/reducers/token';
import onboardingReducer from 'merchant/reducers/onboarding';
import remindersReducer from 'merchant/reducers/reminders';

import {
  refundBatchesReducer,
  PaymentBatchIdsReducer,
  batchDetailsReducer,
  batchesReducer,
} from 'merchant/reducers/batches';

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
  registrationLinksReducer,
  tokensReducer,
  commissionsReducer,
  commissionsAggregateReducer,
  invitationsReducer,
} from 'merchant/reducers/collection';

import { teamReducer } from 'merchant/reducers/team';
import reportsAsyncReducer from 'merchant/reducers/reports/home';

import {
  subscriptionsReducer,
  subscriptionReducer,
} from 'merchant/reducers/subscriptions';
import { plansReducer, planReducer } from 'merchant/reducers/plans';
import { addOnsReducer } from 'merchant/reducers/addons';
import { reportsReducer } from 'merchant/reducers/reports';

import wysiwygReducer from 'merchant/reducers/wysiwyg';

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
  bMerchant: bMerchantReducer,
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
  activationWizard: activationWizardReducer,
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
  reportsAsync: reportsAsyncReducer,
  submerchants: submerchantsReducer,
  submerchant: submerchantReducer,
  commisions: commissionsReducer,
  commission: commissionReducer,
  commissionsAggregate: commissionsAggregateReducer,
  commAggSingleDay: commAggSingleDayReducer,
  wysiwyg: wysiwygReducer,
  registrationLinks: registrationLinksReducer,
  registrationLink: registrationLinkReducer,
  tokens: tokensReducer,
  token: tokenReducer,
  batches: batchesReducer,
  invitations: invitationsReducer,
  onboarding: onboardingReducer,
  reminders: remindersReducer,
});
