import store from 'merchant/store';
import {
  matchDetail as matchDetailx,
  matchModal as matchModalx,
  matchFullPageView as matchFullPageViewx,
} from 'merchant_common/routes';

import lazy from './LazyLoader';

const SettlementDetails = lazy(() =>
  import(
    /* webpackChunkName: "SettlementsDetails" */ 'merchant/views/Settlements/Settlements/Details'
  ),
);

const InstantSettlementDetails = lazy(() =>
  import(
    /* webpackChunkName: "InstantSettlementDetails" */ 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails'
  ),
);

const PaymentLinkDetails = lazy(() =>
  import(
    /* webpackChunkName: "PaymentLinksDetails" */ 'merchant/views/PaymentLinks/PaymentLinks/Details'
  ),
);

const PaymentLinkCreate = lazy(() =>
  import(
    /* webpackChunkName: "PaymentLinkCreate" */ 'merchant/views/PaymentLinks/PaymentLinks/New'
  ),
);

const PaymentPagesWysiwyg = lazy(() =>
  import(
    /* webpackChunkName: "PaymentPagesWysiwyg" */ 'merchant/views/PaymentPages/PaymentPages/Wysiwyg'
  ),
);
const PaymentPagesSuccess = lazy(() =>
  import(
    /* webpackChunkName: "PaymentPagesSuccess" */ 'merchant/views/PaymentPages/PaymentPages/Success'
  ),
);
const PaymentButtonCreate = lazy(() =>
  import(
    /* webpackChunkName: "PaymentButtonCreate" */ 'merchant/views/PaymentButton/PaymentButton/Create'
  ),
);
const SubscriptionButtonCreate = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionButtonCreate" */ 'merchant/views/PaymentButton/SubscriptionButton/Create'
  ),
);
const PaymentsDetails = lazy(() =>
  import(/* webpackChunkName: "PaymentsDetails" */ 'merchant/views/Transactions/Payments/Details'),
);
const RefundDetails = lazy(() =>
  import(/* webpackChunkName: "RefundsDetails" */ 'merchant/views/Transactions/Refunds/Details'),
);
const OrderDetails = lazy(() =>
  import(/* webpackChunkName: "OrdersDetails" */ 'merchant/views/Transactions/Orders/Details'),
);

const VirtualAccountDetails = lazy(() =>
  import(
    /* webpackChunkName: "VirtualAccountsDetails" */ 'merchant/views/SmartCollect/VirtualAccounts/Details'
  ),
);
const VirtualAccountCreate = lazy(() =>
  import(
    /* webpackChunkName: "VirtualAccountsCreate" */ 'merchant/views/SmartCollect/VirtualAccounts/Create/index'
  ),
);
const QRCodeCreate = lazy(() =>
  import(/* webpackChunkName: "QRCodeCreate" */ 'merchant/views/QRCodes/QRCodes/Create'),
);
const QRCodeDetails = lazy(() =>
  import(/* webpackChunkName: "QRCodeCreate" */ 'merchant/views/QRCodes/QRCodes/Details'),
);
const OffersNew = lazy(() =>
  import(/* webpackChunkName: "OffersNew" */ 'merchant/views/Offers/New'),
);
const OfferEntity = lazy(() =>
  import(/* webpackChunkName: "OffersEntity)," */ 'merchant/views/Offers/Entity'),
);
const PlanDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionsPlansDetails" */ 'merchant/views/Subscriptions/Plans/Details'
  ),
);
const SubscriptionDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionsDetails" */ 'merchant/views/Subscriptions/Subscriptions/Details'
  ),
);
const TransferDetails = lazy(() =>
  import(/* webpackChunkName: "TransfersDetails" */ 'merchant/views/Marketplace/Transfers/Details'),
);
const DirectTransfers = lazy(() =>
  import(
    /* webpackChunkName: "DirectTransfers" */ 'merchant/views/Marketplace/Transfers/DirectTransfers'
  ),
);
const ReversalDetails = lazy(() =>
  import(/* webpackChunkName: "ReversalsDetails" */ 'merchant/views/Marketplace/Reversals/Details'),
);
const MarketplaceBatchDetails = lazy(() =>
  import(/* webpackChunkName: "ReversalsDetails" */ 'merchant/views/Marketplace/Batch/Details'),
);
const DisputeDetails = lazy(() =>
  import(/* webpackChunkName: "DisputesDetails" */ 'merchant/views/Transactions/Disputes/Details'),
);
const SubmerchantDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubMerchantDetails" */ 'merchant/views/PartnerDashboard/SubMerchant/Details'
  ),
);
const EarningTransactionalDetails = lazy(() =>
  import(
    /* webpackChunkName: "EarningsTransactional" */ 'merchant/views/PartnerDashboard/Earnings/Transactional/Details'
  ),
);
const EarningDailyDetails = lazy(() =>
  import(
    /* webpackChunkName: "EarningsDaily" */ 'merchant/views/PartnerDashboard/Earnings/Daily/Details'
  ),
);
const CommissionInvoiceDetails = lazy(() =>
  import(
    /* webpackChunkName: "EarningsInvoices" */ 'merchant/views/PartnerDashboard/Earnings/Invoices/Details'
  ),
);
const SubventionTransactionalDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubventionTransactional" */ 'merchant/views/PartnerDashboard/Subvention/Transactional/Details'
  ),
);
const SubventionDailyDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubventionDaily" */ 'merchant/views/PartnerDashboard/Subvention/Daily/Details'
  ),
);
const RegistrationLinkDetails = lazy(() =>
  import(
    /* webpackChunkName: "RegistrationLinksDetails" */ 'merchant/views/Subscriptions/RegistrationLinks/Details'
  ),
);
const UploadNACHForm = lazy(() =>
  import(
    /* webpackChunkName: "componentsUploadNACHForm" */ 'merchant/views/Subscriptions/components/UploadNACHForm'
  ),
);
const AccountDetailsNew = lazy(() =>
  import(
    /* webpackChunkName: "AccountsDetailsNew" */ 'merchant/views/Marketplace/Accounts/DetailsNew'
  ),
);

const TokenDetails = lazy(() =>
  import(/* webpackChunkName: "TokensDetails" */ 'merchant/views/Subscriptions/Tokens/Details'),
);

const PaymentLinkBatchUploadDetails = lazy(() =>
  import(
    /* webpackChunkName: "BatchUploadDetails" */ 'merchant/views/PaymentLinks/BatchUpload/Details'
  ),
);
const SubscriptionBatchDetails = lazy(() =>
  import(/* webpackChunkName: "BatchDetails" */ 'merchant/views/Subscriptions/Batch/Details'),
);

const PlanNew = lazy(() =>
  import(/* webpackChunkName: "PlansNew" */ 'merchant/views/Subscriptions/Plans/New'),
);
const ActivationContainer = lazy(() =>
  import(/* webpackChunkName: "MerchantActivation" */ 'merchant/containers/Activation'),
);
const NewRegistrationLink = lazy(() =>
  import(
    /* webpackChunkName: "RegistrationLinksNew" */ 'merchant/views/Subscriptions/RegistrationLinks/New'
  ),
);
const NewSubscriptionLink = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionLinksNew" */ 'merchant/views/Subscriptions/SubscriptionLinks/New'
  ),
);
const UpdateSubscriptionLink = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionLinksUpdate" */ 'merchant/views/Subscriptions/SubscriptionLinks/Update'
  ),
);
const CreditSubDetails = lazy(() =>
  import(
    /* webpackChunkName: "CreditsCreditSubDetails" */ 'merchant/views/Account/Credits/components/CreditSubDetails'
  ),
);
const WebhookEntity = lazy(() =>
  import(/* webpackChunkName: "WebhooksEntity" */ 'merchant/views/Settings/Webhooks/Entity'),
);
const WithdrawalDetails = lazy(() =>
  import(
    /* webpackChunkName: "CashAdvanceWithdrawalDetails" */ '../views/Capital/CashAdvance/WithdrawalDetails'
  ),
);
const RepaymentDetails = lazy(() =>
  import(
    /* webpackChunkName: "CashAdvanceWithdrawalDetails" */ '../views/Capital/CashAdvance/Repayments/RepaymentDetails'
  ),
);
const LoansRepaymentDetails = lazy(() =>
  import(
    /* webpackChunkName: "LoansRepaymentDetails" */ '../views/Capital/Loans/LoansCollections/RepaymentHistory/RepaymentDetails'
  ),
);
const RuleDetail = lazy(() =>
  import(
    /* webpackChunkName: "componentsRuleDetail" */ 'merchant/views/Navigator/components/RuleDetail'
  ),
);

const ProviderDetails = lazy(() =>
  import(
    /* webpackChunkName: "componentsProviderDetails" */ 'merchant/views/Navigator/components/ProviderDetails'
  ),
);

const ActivationSteps = lazy(() =>
  import(
    /* webpackChunkName: "OnboardingForm" */ 'merchant/views/onboarding/mobile/Screens/ActivationProgress'
  ),
);

const ActivationForm = lazy(() =>
  import(
    /* webpackChunkName: "OnboardingForm" */ 'merchant/views/onboarding/mobile/Screens/ActivationForm'
  ),
);
const PartnerAppStore = lazy(() =>
  import(/* webpackChunkName: "PartnerAppStore" */ 'merchant/views/PartnerAppStore'),
);

const PartnerPage = lazy(() =>
  import(/* webpackChunkName: "PartnerPage" */ 'merchant/views/PartnerAppStore/PartnerPage'),
);

const WhatsNewDetailsPage = lazy(() =>
  import(/* webpackChunkName: "WhatsNewDetailsPage" */ 'merchant/views/WhatsNew/Details'),
);

const GenerateTnC = lazy(() =>
  import(
    /* webpackChunkName: "TermsAndCondition" */ 'merchant/views/TermsAndCondition/GenerateTnc'
  ),
);

const PartnerActivationForm = lazy(() =>
  import(
    /* webpackChunkName: "PartnerActivationForm" */ 'merchant/views/PartnerDashboard/Activation'
  ),
);

const PartnerActivationFormMweb = lazy(() =>
  import(
    /* webpackChunkName: "PartnerActivationMweb" */ 'merchant/views/PartnerDashboard/Activation/Components/mweb'
  ),
);

const StoresProductsCreate = lazy(() =>
  import(/* webpackChunkName: "StoresProductsCreate" */ 'merchant/views/Stores/Create/'),
);

/*
 * NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
 * */

const entityDetailsMap = {
  '/payments/:id(pay_.+)/:entity_name(transfers|disputes)/:entity_id': {
    component: PaymentsDetails,
    additionalCondition: (user) => user.isAllowedEdit('payments'),
  },
  '/payments/:id(pay_.+)': {
    component: PaymentsDetails,
    additionalCondition: (user) => user.isAllowedView('payments'),
  },

  '/refunds/:id(rfnd_.+)': {
    component: RefundDetails,
    additionalCondition: (user) => user.isAllowedView('refunds'),
  },
  '/optimizer/rules/:id': {
    component: RuleDetail,
  },
  '/optimizer/provider/:id': {
    component: ProviderDetails,
  },
  '/offers/:id(offer_.+)': {
    component: OfferEntity,
    additionalCondition: (user) => user.isAllowedView('offers'),
  },
  '/orders/:id': {
    component: OrderDetails,
    additionalCondition: (user) => user.isAllowedView('orders'),
  },
  '/settlements/:id': {
    component: SettlementDetails,
    additionalCondition: (user) =>
      !user.isUxRevampPhase2Enabled && user.isAllowedView('settlements'),
  },
  '/instantsettlement/:id': {
    component: InstantSettlementDetails,
    additionalCondition: (user) => user.isAllowedView('settlements'),
  },
  '/paymentlinks/:id(inv_.+|plink_.+)': {
    component: PaymentLinkDetails,
    additionalCondition: (user) => user.isAllowedView('payment_links'),
  },
  '/paymentlinks/batchuploads/:id(batch_.+)': {
    component: PaymentLinkBatchUploadDetails,
    additionalCondition: (user) =>
      user.isAllowedView('payment_links_batch_uploads') &&
      user.isPLBatchUploadEnabled &&
      (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole),
  },
  '/invoices/:id/details': {
    component: PaymentLinkDetails,
    additionalCondition: (user) => user.isAllowedView('invoices'),
  },

  '/route/payments/:id': { component: PaymentsDetails },
  '/route/accounts/:id': { component: AccountDetailsNew },
  '/smartcollect/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/virtualaccounts/:id': { component: VirtualAccountDetails },
  // QR Code
  '/qr_codes/:id(qr_.+)': {
    component: QRCodeDetails,
    additionalCondition: (user) => user.isQRCodesEnabled,
  },
  '/plans/new': { component: PlanNew },
  '/plans/:id': { component: PlanDetails },
  '/registration_links/:id(inv_.+)': {
    component: RegistrationLinkDetails,
    additionalCondition: (user) => user.isChargeAtWillEnabled,
  },

  '/tokens/:id(token_.+)': {
    component: TokenDetails,
    additionalCondition: (user) => user.isChargeAtWillEnabled,
  },

  '/capital/cash-advance/withdrawals/:id': {
    component: WithdrawalDetails,
    additionalCondition: (user) => user.isLOCEnabled,
  },
  '/capital/cash-advance/repayments/:id': {
    component: RepaymentDetails,
    additionalCondition: (user) => user.isLOCEnabled,
  },
  '/capital/loans/history/:id': {
    component: LoansRepaymentDetails,
    additionalCondition: (user) => user.isLoansEnabled,
  },
  '/subscriptions/:id(sub_.+)/:invoice_id(inv_.+)': {
    component: SubscriptionDetails,
  },
  '/subscriptions/:id(sub_.+)/:credit_note_id(crnt_.+)': {
    component: SubscriptionDetails,
  },
  '/subscriptions/:id(sub_.+)': { component: SubscriptionDetails },
  '/subscriptions/batchuploads/:id(batch_.+)': {
    component: SubscriptionBatchDetails,
  },

  '/route/transfers/:id(trf_.+)/:reversal_id(rvrsl_.+)': {
    component: TransferDetails,
  },
  '/route/transfers/:id(trf_.+)': { component: TransferDetails },
  '/route/reversals/:id(rvrsl_.+)': { component: ReversalDetails },
  '/route/batchuploads/:id(batch_.+)': {
    component: MarketplaceBatchDetails,
  },

  '/partners/submerchants/:id(acc_.+)/:appId': {
    component: SubmerchantDetails,
  },
  '/partners/submerchants/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/submerchants/x/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/earnings/transactional/:id(comm_.+)': {
    component: EarningTransactionalDetails,
    additionalCondition: (user) => user.isAllowedView('earnings') && user.isHavingPartnerConfigs,
  },
  '/partners/subventions/transactional/:id(comm_.+)': {
    component: SubventionTransactionalDetails,
    additionalCondition: (user) => user.isAllowedView('earnings') && user.isHavingSubventionConfigs,
  },
  '/partners/earnings/daily/:timestamp': {
    component: EarningDailyDetails,
    additionalCondition: (user) => user.isAllowedView('earnings') && user.isHavingPartnerConfigs,
  },
  '/partners/subventions/daily/:timestamp': {
    component: SubventionDailyDetails,
    additionalCondition: (user) => user.isAllowedView('earnings') && user.isHavingSubventionConfigs,
  },
  '/partners/earnings/invoices/:id': {
    component: CommissionInvoiceDetails,
    additionalCondition: (user) =>
      user.isAllowedView('earnings') &&
      user.isCommissionInvoicesEnabled &&
      user.isHavingPartnerConfigs,
  },
  '/disputes/:id(disp_.+)/:entity_name(payments)/:entity_id': {
    component: DisputeDetails,
    additionalCondition: (user) => user.isAllowedView('payments'),
  },
  '/disputes/:id(disp_.+)': {
    component: DisputeDetails,
    additionalCondition: (user) => user.isAllowedView('payments'),
  },
  '/credits/:id(credits_.+)': { component: CreditSubDetails },
  '/webhooks/:id': { component: WebhookEntity },
  '/announcements/:id': {
    component: WhatsNewDetailsPage,
  },
};

/*
 * Example:
 * - '/paymentlinks/new': {component: PaymentLinkCreate, featureEnabled: "randomFeature", featureEnabled: "randomFeature"}
 * */
const entityModalsMap = {
  '/activation': {
    component: ActivationContainer,
    additionalCondition: (user) => user.isAllowedEdit('activation'),
  },
  '/offers/new': {
    component: OffersNew,
    additionalCondition: (user) => user.isAllowedEdit('offers'),
  },
  '/paymentlinks/new': {
    component: PaymentLinkCreate,
    additionalCondition: (user) => user.isAllowedEdit('payment_links'),
  },
  '/registration_links/:id(inv_.+)/upload_nach': {
    component: UploadNACHForm,
    additionalCondition: (user) => user.isChargeAtWillEnabled,
  },
  '/registration_links/new': {
    component: NewRegistrationLink,
    additionalCondition: (user) => user.isChargeAtWillEnabled,
  },
  '/subscriptions/new': {
    component: NewSubscriptionLink,
  },
  '/subscriptions/:id(sub_.+)/edit': {
    component: UpdateSubscriptionLink,
  },
  '/smartcollect/virtualaccounts/new': {
    component: VirtualAccountCreate,
  },
  '/virtualaccounts/new': {
    component: VirtualAccountCreate,
  },
  '/qr_codes/new': {
    component: QRCodeCreate,
    additionalCondition: (user) => user.isAllowedEdit('qr_codes'),
  },
  '/route/transfers/direct_transfer': {
    component: DirectTransfers,
  },
  '/partners/activation': {
    component: PartnerActivationForm,
    additionalCondition: (user) => user.isIndependentPartnerKYCEnabled,
  },
  '/stores/products/new': {
    component: StoresProductsCreate,
    additionalCondition: (user) => user.isAllowedView('stores') && user.isStoresEnabled,
  },
  '/stores/products/:product_id': {
    component: StoresProductsCreate,
    additionalCondition: (user) => user.isAllowedView('stores') && user.isStoresEnabled,
  },
  '/partners/submerchants/:submerchantId(acc_.+)/activation': {
    component: ActivationContainer,
    additionalCondition: (user) => user.isSubMerchantKycResellerEnabled,
  },
};

export const supportHashMapping = {
  '#request': '#support',
  '#ticket': '#ticket',
};

/*
 * Certain views are stand alone views with no Header or Siderbar
 * Example: payment pages
 *
 * */
const fullPageViewsMap = {
  '/paymentpages/new': {
    component: PaymentPagesWysiwyg,
    additionalCondition: (user) => user.isAllowedEdit('payment_pages'),
  },
  '/paymentpages/:id(pl_.+)/edit': {
    component: PaymentPagesWysiwyg,
    additionalCondition: (user) => user.isAllowedEdit('payment_pages'),
  },
  '/paymentpages/:id(pl_.+)/success': {
    component: PaymentPagesSuccess,
    additionalCondition: (user) => user.isAllowedEdit('payment_pages') && user.isPPSuccessPage,
  },
  '/paymentbuttons/new': {
    component: PaymentButtonCreate,
    additionalCondition: (user) =>
      user.isAllowedEdit('payment_buttons') && user.isPaymentButtonEnabledByRazorX,
  },
  '/paymentbuttons/:id(pl_.+)/edit': {
    component: PaymentButtonCreate,
    additionalCondition: (user) =>
      user.isAllowedEdit('payment_buttons') && user.isPaymentButtonEnabledByRazorX,
  },
  '/subscription_buttons/new': {
    component: SubscriptionButtonCreate,
    additionalCondition: (user) =>
      user.isAllowedEdit('subscription_buttons') && user.isSubscriptionButtonEnabled,
  },
  '/subscription_buttons/:id(pl_.+)/edit': {
    component: SubscriptionButtonCreate,
    additionalCondition: (user) =>
      user.isAllowedEdit('subscription_buttons') && user.isSubscriptionButtonEnabled,
  },
  '/onboarding/steps': {
    component: ActivationSteps,
    additionalCondition: (user) => user.isOnboardingV2Enabled,
  },
  '/onboarding/form': {
    component: ActivationForm,
    additionalCondition: (user) => user.isOnboardingV2Enabled,
  },
  '/app-store/:partner': {
    component: PartnerPage,
  },
  '/app-store': {
    component: PartnerAppStore,
  },
  '/tncform': {
    component: GenerateTnC,
    additionalCondition: (user) => user.canGenerateTnCPage,
  },
  '/partners/onboarding': {
    component: PartnerActivationFormMweb,
    additionalCondition: (user) => user.isIndependentPartnerKYCEnabled,
  },
  '/partners/submerchants/onboarding/:submerchantId(acc_.+)/steps': {
    component: ActivationSteps,
    additionalCondition: (user) => user.isSubMerchantKycResellerEnabled,
  },
  '/partners/submerchants/onboarding/:submerchantId(acc_.+)/form': {
    component: ActivationForm,
    additionalCondition: (user) => user.isSubMerchantKycResellerEnabled,
  },
};

export const matchDetail = matchDetailx(store, entityDetailsMap);
export const matchModal = matchModalx(store, entityModalsMap);
export const matchFullPageView = matchFullPageViewx(store, fullPageViewsMap);
