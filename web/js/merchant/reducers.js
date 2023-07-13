import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';
import modalReducer from 'merchant_common/reducers/modals';
import sliderReducer from 'merchant_common/reducers/slider';
import multiSliderReducer from 'merchant_common/reducers/multiSlider';
import notificationsReducer from 'merchant_common/reducers/notifications';
import twoFactorReducer from 'merchant_common/reducers/twoFactor';
import sessionReducer from 'merchant/reducers/session';
import appReducer from 'merchant/reducers/app';
import homeReducer from 'merchant/reducers/home';
import invoicesReducer from 'merchant/reducers/invoices/list';
import paymentlinksReducer from 'merchant/reducers/paymentlinks/list';
import paymentlinkReducer from 'merchant/reducers/paymentlinks/details';
import paymentButtonsReducer from 'merchant/reducers/paymentbuttons/list';
import paymentButtonCreateReducer from 'merchant/reducers/paymentbuttons/create';
import subscriptionButtonsReducer from 'merchant/reducers/subscriptionButtons/list';
import subscriptionButtonCreateReducer from 'merchant/reducers/subscriptionButtons/create';
import qrCodesReducer from 'merchant/reducers/qrCodes/list';
import invoiceDetailsReducer from 'merchant/reducers/invoices/details';
import profileReducer from 'merchant/reducers/profile';
import trustedBadgeReducer from 'merchant/reducers/trustedBadge';
import customersReducer from 'merchant/reducers/customers';
import itemsReducer from 'merchant/reducers/items';
import orderReducer from 'merchant/reducers/orders/details';
import disputeReducer from 'merchant/reducers/disputes/details';
import successRateReducer from 'merchant/reducers/successRate';
import settlementReducer from 'merchant/reducers/settlements/details';
import instantSettlementReducer from 'merchant/reducers/instantSettlements/details';
import webhooksReducer from 'merchant/reducers/webhooks';
import keysReducer from 'merchant/reducers/keys';
import bMerchantReducer from 'merchant/reducers/b-merchants';
import creditsReducer from 'merchant/reducers/credits';
import configReducer from 'merchant/reducers/config';
import activationReducer from 'merchant/reducers/activation';
import activationWizardReducer from 'merchant/reducers/activationWizard';
import refundReducer from 'merchant/reducers/refunds/details';
import paymentReducer from 'merchant/reducers/payments/details';
import transferReducer from 'merchant/reducers/marketplace/transfers/details';
import reversalReducer from 'merchant/reducers/marketplace/reversal';
import mpAccountsReducer from 'merchant/reducers/marketplace/accounts';
import referralsReducer from 'merchant/reducers/referrals';
import applicationsReducer from 'merchant/reducers/applications';
import affordabilityWidgetReducer from 'merchant/reducers/affordability/affordabilityWidget';
import offerReducer from 'merchant/reducers/offers/offerDetails';
import offerListReducer from 'merchant/reducers/offers/offersList';
import { virtualAccountsReducer, virtualAccountReducer } from 'merchant/reducers/virtualaccounts';
import submerchantReducer from 'merchant/reducers/submerchant';
import commissionReducer, { commAggSingleDayReducer } from 'merchant/reducers/commission';
import registrationLinkReducer from 'merchant/reducers/registration_link';
import statesReducer from 'merchant/reducers/states';
import taxesReducer from 'merchant/reducers/taxes';
import tokenReducer from 'merchant/reducers/token';
import onboardingReducer from 'merchant/reducers/onboarding';
import remindersReducer from 'merchant/reducers/reminders';
import commissionInvoices from 'merchant/reducers/commissionInvoices/list';
import commissionInvoice from 'merchant/reducers/commissionInvoices/details';
import rewardsReducer from 'merchant/reducers/checkoutRewards';
import merchantReferralReducer from 'merchant/reducers/merchantReferral';
import storefrontReducer from 'merchant/reducers/storefront';
import apmFormReducer from 'merchant/reducers/apmForm/reducer';
import leftNavReducer from 'merchant/reducers/leftNav';
import {
  refundBatchesReducer,
  PaymentBatchIdsReducer,
  batchDetailsReducer,
  batchesReducer,
  virtualAccountBatchesReducer,
} from 'merchant/reducers/batches';
import { addressBatchesReducer } from 'merchant/reducers/magicCheckout/bulk_address_upload';
import { orderStatusBatchesReducer } from 'merchant/reducers/magicCheckout/bulk_order_statuses';
import magicRTOAnalyticsReducer from 'merchant/reducers/magicCheckout/rtoAnalytics/reducer';
import { magicCODOrdersAutomationReducer } from 'merchant/reducers/magicCheckout/codOrderAutomation/reducer';
import { magicPrepayCODConfigsReducer } from 'merchant/reducers/magicCheckout/prepayCOD/configDashboard/reducers';
import {
  magicPrepayCODOrdersReducer,
  magicPrepayCODOrderInfoReducer,
} from 'merchant/reducers/magicCheckout/prepayCOD/orderConversionTab/reducers';
import { rtoHistoryUploadReducer } from 'merchant/reducers/magicCheckout/rtoHistoryUpload/reducer';
import {
  magicCODOrdersReducer,
  magicCODOrderInfoReducer,
} from 'merchant/reducers/magicCheckout/codOrders/reducer';
import paymentHandleReducer from 'merchant/reducers/paymentHandle';
import newAuthReducer from 'merchant/reducers/newAuth';
import partnerReducer from 'merchant/reducers/partner';

import {
  paymentsReducer,
  ordersReducer,
  transfersReducer,
  reversalsReducer,
  mpPaymentsReducer,
  smartCollectPaymentsReducer,
  qrCodePaymentsReducer,
  refundsReducer,
  settlementsReducer,
  instantSettlementsReducer,
  routeOndemandSettlementsReducer,
  disputesReducer,
  submerchantsReducer,
  registrationLinksReducer,
  tokensReducer,
  commissionsReducer,
  commissionsAggregateReducer,
  invitationsReducer,
} from 'merchant/reducers/collection';

import { teamReducer } from 'merchant/reducers/team';

import { merchantReportsReducer, partnerReportsReducer } from 'merchant/reducers/reports/home';

import { subscriptionsReducer, subscriptionReducer } from 'merchant/reducers/subscriptions';
import { plansReducer, planReducer } from 'merchant/reducers/plans';
import growthServiceReducer from 'merchant/reducers/growthService';
import bundlePricingReducer from 'merchant/reducers/bundlePricing';
import { addOnsReducer } from 'merchant/reducers/addons';
import { reportsReducer } from 'merchant/reducers/reports';
import LoanApplicationReducer from 'merchant/reducers/capital';

import wysiwygReducer from 'merchant/reducers/wysiwyg';
import paymentPagesProductsReducer from 'merchant/reducers/paymentPages/products';
import paymentPageStorefrontReducer from 'merchant/reducers/paymentPages/storefront';
import WithdrawalsReducer from 'merchant/reducers/capital/withdrawals';
import RepaymentsReducer from 'merchant/reducers/capital/repayments';
import AccountReducer from 'merchant/reducers/capital/accounts';
import MigrationReducer from 'merchant/reducers/capital/migrations';
import instrumentRequestsReducer from 'merchant/reducers/instrumentRequests';
import navigatorReducer from 'merchant/reducers/navigator/details';
import supportDetailReducer from 'merchant/reducers/support_detail';
import fetchTransactionReducer from 'merchant/reducers/fetchTransaction';
import magicCheckoutReducer from 'merchant/reducers/magicCheckout';
import shippingServiceReducer from 'merchant/reducers/magicCheckout/shipping_services/reducers';
import trackEventsReducer from './reducers/trackEvents';
import workflowReducer from './reducers/workflows';
import magicSettingsReducer from 'merchant/reducers/magicCheckout/magicSettings/reducer';
import {
  blocklistReducer,
  allowlistReducer,
} from 'merchant/reducers/magicCheckout/magicIntelligence/reducer';

import { b2bReducers } from 'merchant/reducers/b2bExports';
import non3dsCardsActivationReducer from 'merchant/reducers/non3dsCardsActivation';
import apiLogsReducer from 'merchant/reducers/developers/apiLogs';
import apiStatsReducer from 'merchant/reducers/developers/apiStats';
import webhookEventsListReducer from 'merchant/reducers/developers/webhookEventsList';
import webhooksLogsReducer from 'merchant/reducers/developers/webhookLogs';
import webhookStatsReducer from 'merchant/reducers/developers/webhookStats';
import websiteComplianceReducer from 'merchant/reducers/websitecompliance';
import pluginReducer from 'merchant/reducers/plugins';
import { paymentUploadInvoiceReducer } from 'merchant/reducers/paymentUploadInvoice';
import { reportsReducer as reportsCoreReducer } from 'merchant_common/views/Reports/redux/reducer';
import { magicCODSettingsReducer } from 'merchant/reducers/magicCheckout/codEngine/reducer';
import paymentMetricsReducer from 'merchant/reducers/paymentMetrics';

export default combineReducers({
  modal: modalReducer,
  slider: sliderReducer,
  multiSlider: multiSliderReducer,
  notifications: notificationsReducer,
  form: formReducer,
  session: sessionReducer,
  app: appReducer,
  home: homeReducer,
  invoices: invoicesReducer,
  invoice: invoiceDetailsReducer,
  paymentlinks: paymentlinksReducer,
  paymentlink: paymentlinkReducer,
  paymentBatchIds: PaymentBatchIdsReducer,
  paymentbuttons: paymentButtonsReducer,
  payment_button_create: paymentButtonCreateReducer,
  subscription_buttons: subscriptionButtonsReducer,
  subscription_button_create: subscriptionButtonCreateReducer,
  refundbatches: refundBatchesReducer,
  addressbatches: addressBatchesReducer,
  orderStatusBatches: orderStatusBatchesReducer,
  batchDetails: batchDetailsReducer,
  bMerchant: bMerchantReducer,
  subscriptions: subscriptionsReducer,
  subscription: subscriptionReducer,
  plans: plansReducer,
  trustedBadge: trustedBadgeReducer,
  plan: planReducer,
  growthService: growthServiceReducer,
  bundlePricing: bundlePricingReducer,
  addons: addOnsReducer,
  profile: profileReducer,
  customers: customersReducer,
  items: itemsReducer,
  offer: offerReducer,
  affordabilityWidget: affordabilityWidgetReducer,
  offers: offerListReducer,
  orders: ordersReducer,
  order: orderReducer,
  payments: paymentsReducer,
  payment: paymentReducer,
  settlements: settlementsReducer,
  settlement: settlementReducer,
  instantSettlements: instantSettlementsReducer,
  instantSettlement: instantSettlementReducer,
  routeOndemandSettlements: routeOndemandSettlementsReducer,
  disputes: disputesReducer,
  dispute: disputeReducer,
  successRate: successRateReducer,
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
  scPayments: smartCollectPaymentsReducer,
  qrCodePayments: qrCodePaymentsReducer,
  storefront: storefrontReducer,
  transfers: transfersReducer,
  navigator: navigatorReducer,
  transfer: transferReducer,
  reversal: reversalReducer,
  reversals: reversalsReducer,
  virtualaccounts: virtualAccountsReducer,
  virtualaccount: virtualAccountReducer,
  states: statesReducer,
  taxes: taxesReducer,
  reports: reportsReducer,
  reportsCore: reportsCoreReducer,
  merchantReports: merchantReportsReducer,
  partnerReports: partnerReportsReducer,
  submerchants: submerchantsReducer,
  submerchant: submerchantReducer,
  commisions: commissionsReducer,
  commission: commissionReducer,
  commissionsAggregate: commissionsAggregateReducer,
  commAggSingleDay: commAggSingleDayReducer,
  wysiwyg: wysiwygReducer,
  paymentPagesProducts: paymentPagesProductsReducer,
  paymentPageStorefront: paymentPageStorefrontReducer,
  registrationLinks: registrationLinksReducer,
  registrationLink: registrationLinkReducer,
  tokens: tokensReducer,
  token: tokenReducer,
  batches: batchesReducer,
  invitations: invitationsReducer,
  onboarding: onboardingReducer,
  reminders: remindersReducer,
  commissionInvoices,
  commissionInvoice,
  loanApplicationDetails: LoanApplicationReducer,
  twoFactor: twoFactorReducer,
  withdrawals: WithdrawalsReducer,
  instrumentRequests: instrumentRequestsReducer,
  supportdetails: supportDetailReducer,
  transactionAmount: fetchTransactionReducer,
  repayments: RepaymentsReducer,
  productConfig: AccountReducer,
  rewards: rewardsReducer,
  qr_codes: qrCodesReducer,
  migrations: MigrationReducer,
  magicCheckout: magicCheckoutReducer,
  magic_settings: magicSettingsReducer,
  magicRTOAnalytics: magicRTOAnalyticsReducer,
  magicPrepayCODOrders: magicPrepayCODOrdersReducer,
  magicPrepayCODOrderInfo: magicPrepayCODOrderInfoReducer,
  magicCODOrders: magicCODOrdersReducer,
  magicCODOrderInfo: magicCODOrderInfoReducer,
  magicCODOrdersAutomation: magicCODOrdersAutomationReducer,
  magicPrepayCODConfigs: magicPrepayCODConfigsReducer,
  magicCODEngine: magicCODSettingsReducer,
  magicBlocklist: blocklistReducer,
  magicAllowlist: allowlistReducer,
  rtoHistoryUpload: rtoHistoryUploadReducer,
  shippingService: shippingServiceReducer,
  merchantReferral: merchantReferralReducer,
  trackEvents: trackEventsReducer,
  workflows: workflowReducer,
  virtualAccountBatches: virtualAccountBatchesReducer,
  b2bExportsTransactions: b2bReducers.b2bExportsTransactionsReducer,
  b2bExportsAccounts: b2bReducers.b2bExportsAccountsReducer,
  b2bExportsAccountBalance: b2bReducers.b2bExportsAccountBalanceReducer,
  b2bExportsBeneficiary: b2bReducers.b2bExportsBeneficiaryReducer,
  apmForm: apmFormReducer,
  non3dsCardsActivation: non3dsCardsActivationReducer,
  apiLogs: apiLogsReducer,
  apiStats: apiStatsReducer,
  webhookEventsList: webhookEventsListReducer,
  webhookLogs: webhooksLogsReducer,
  webhookStats: webhookStatsReducer,
  websiteCompliance: websiteComplianceReducer,
  plugins: pluginReducer,
  paymentUploadInvoice: paymentUploadInvoiceReducer,
  leftNav: leftNavReducer,
  paymentHandle: paymentHandleReducer,
  newAuth: newAuthReducer,
  partnerDashboard: partnerReducer,
  paymentMetrics: paymentMetricsReducer,
});
