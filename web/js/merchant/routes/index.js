import { isMobileResolution } from 'common/utils/rzp-utils';
import store from 'merchant/store';
import {
  getIntentChannels,
  getProvidedChannels,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/utils';
import { isIntegrationAuditEnabled } from 'merchant/views/Optimizer/AddProvider/utils';
import { isPartnershipsForPosEnabled } from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import {
  matchDetail as matchDetailx,
  matchModal as matchModalx,
  matchFullPageView as matchFullPageViewx,
} from 'merchant_common/routes';
import { Box } from '@razorpay/blade/components';

import lazy from './LazyLoader';
import { isExperimentEnabled } from 'common/splitz/utils';

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

const PaymentPagesCreateEdit = lazy(() =>
  import(
    /* webpackChunkName: "PaymentPagesCreateEdit" */ 'merchant/views/PaymentPages/PaymentPages/CreateEdit/New'
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
const BatchUploadSubPage = lazy(() =>
  import(
    /* webpackChunkName: "PaymentPagesBatchUpload" */ 'merchant/views/PaymentPages/PaymentPages/BatchUploadSubPage'
  ),
);
const PaymentPagesStorefront = lazy(() =>
  import(
    /* webpackChunkName: "PaymentPagesStorefront" */ 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront'
  ),
);
const PaymentPagesStorefrontSuccess = lazy(() =>
  import(
    /* webpackChunkName: "PaymentPagesStorefrontSuccess" */ 'merchant/views/PaymentPages/PaymentPages/Success/Storefront'
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

const PdfDetails = lazy(() =>
  import(
    /* webpackChunkName: "PdfDetails" */ 'merchant/views/Transactions/v1/Payments/BouncememoRoute'
  ),
);

const OrderDetails = lazy(() =>
  import(/* webpackChunkName: "OrdersDetails" */ 'merchant/views/Transactions/v1/Orders/Details'),
);

const VirtualAccountDetails = lazy(() =>
  import(
    /* webpackChunkName: "VirtualAccountsDetails" */ 'merchant/views/SmartCollect/VirtualAccounts/Details'
  ),
);

const VirtualAccountBatchDetails = lazy(() =>
  import(
    /* webpackChunkName: "VirtualAccountBatchDetails" */ 'merchant/views/SmartCollect/BatchExpiryUpdate/components/Details'
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
const PlatformFeeDetails = lazy(() =>
  import(
    /* webpackChunkName: "PlatformFeeDetails" */ 'merchant/views/Marketplace/PlatformFee/Details'
  ),
);
const ReversalDetails = lazy(() =>
  import(/* webpackChunkName: "ReversalsDetails" */ 'merchant/views/Marketplace/Reversals/Details'),
);
const MarketplaceBatchDetails = lazy(() =>
  import(/* webpackChunkName: "ReversalsDetails" */ 'merchant/views/Marketplace/Batch/Details'),
);
const DisputeDetails = lazy(() =>
  import(
    /* webpackChunkName: "DisputesDetails" */ 'merchant/views/Transactions/v1/Disputes/Details'
  ),
);
const SubmerchantDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubMerchantDetails" */ 'merchant/views/PartnerDashboard/SubMerchant/DetailsContainer'
  ),
);
const PosSubmerchantDetails = lazy(() =>
  import('merchant/views/PartnerDashboard/SubMerchant/POS/PosSubmerchantDetails'),
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
const EasyOnboardingWrapper = lazy(() =>
  import(/* webpackChunkName: "MerchantActivation" */ 'merchant/components/EasyOnboardingWrapper'),
);
const SubmerchantActivationContainer = lazy(() =>
  import(
    /* webpackChunkName: "SubMerchantActivation" */ 'merchant/views/PartnerDashboard/SubMerchant/KYC/submerchantContainer'
  ),
);

const ActivationFullViewContainer = lazy(() =>
  import(
    /* webpackChunkName: "MerchantActivationNativeView" */ 'merchant/containers/Activation/ActivationNativeView'
  ),
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

const RizeMarketplacePage = lazy(() =>
  import(
    /* webpackChunkName: "RizeMarketplacePage" */ 'merchant/views/RizeMarketplace/Marketplace'
  ),
);

const RizeMarketplaceListingPage = lazy(() =>
  import(
    /* webpackChunkName: "RizeMarketplaceListingPage" */ 'merchant/views/RizeMarketplace/Listing'
  ),
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

const RequestLogDetails = lazy(() =>
  import(
    /* webpackChunkName: "RequestLogDetails" */ 'merchant/views/Developers/Api/RequestLogs/Details'
  ),
);

const WebhookEventDetails = lazy(() =>
  import(
    /* webpackChunkName: "WebhookEventDetails" */ 'merchant/views/Developers/Webhooks/RequestLogs/EventDetails'
  ),
);

const AppSupport = lazy(() =>
  import(/* webpackChunkName: "AppSupport" */ 'merchant/views/AppSupport'),
);

const ApiKeysAndPlugins = lazy(() =>
  import(/* webpackChunkName: "ApiKeysAndPlugins" */ 'merchant/views/ApiKeysAndPlugins'),
);

/*
 * NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
 * */
const NitroPgPricing = lazy(() =>
  import(/* webpackChunkName: "NitroPgPricing" */ 'common/ui/MwebExOfferCampaign/NitroPgPricing'),
);

const PaymentTransferNew = lazy(() =>
  import(
    /* webpackChunkName: "PaymentTransferNew" */ 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransferNew'
  ),
);

const PartnerPricingPlans = lazy(() =>
  import(
    /* webpackChunkName: "PartnerPricingPlans" */ 'merchant/views/PartnerDashboard/external/PartnerPricingPlans'
  ),
);

const ClientPOSOrderList = lazy(() =>
  import(
    /* webpackChunkName: "ClientPOSOrderList" */ 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList'
  ),
);

const MagicCheckoutConfiguration = lazy(() =>
  import(/* webpackChunkName: "MagicCheckout3.0" */ 'merchant/views/MagicCheckout'),
);

const ConfigurationHeader = lazy(() =>
  import(
    /* webpackChunkName: "OnboardingHeader" */ 'merchant/views/OnboardingConfigs/components/Header'
  ),
);

const OptimizerAccountDetails = lazy(() =>
  import(
    /* webpackChunkName: "OptimizerAccountDetails" */ 'merchant/views/Marketplace/OptimizerAccounts/Details'
  ),
);

/**
 * FPV(Full Page View) of component doesnt have header included. But we are extending FPV with Header for
 * Magic Checkout
 */
const MagicFPVWithHeader = () => (
  <>
    <ConfigurationHeader />
    <Box overflowY="auto" height="90vh">
      <MagicCheckoutConfiguration />
    </Box>
  </>
);
const entityDetailsMap = {
  '/payments/:id(pay_.+)/v2/transfers/new': {
    component: PaymentTransferNew,
    additionalCondition: (user) => user.isAllowedEdit('payments'),
  },
  '/optimizer/rules/:id': {
    component: RuleDetail,
  },
  '/exclusive-offer/:campaign': {
    component: NitroPgPricing,
  },
  '/optimizer/provider/:id': {
    component: ProviderDetails,
    additionalCondition: (_, extraConfig) => !isIntegrationAuditEnabled(extraConfig?.splitz),
  },
  '/offers/:id(offer_.+)': {
    component: OfferEntity,
    additionalCondition: (user) => user.isAllowedView('offers'),
  },
  '/orders/:id': {
    component: OrderDetails,
    additionalCondition: (user) => user.isAllowedView('orders'),
  },
  '/instantsettlement/:id': {
    component: InstantSettlementDetails,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedView('settlements') &&
      !extraConfig?.i18?.isConfigTagEnabled('settlements.settlement'),
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
  '/paymentpages/batchuploads/:id(batch_.+)': {
    component: PaymentLinkBatchUploadDetails,
    additionalCondition: (user) => user?.isPaymentPageFileUploadEnabled,
  },
  '/invoices/:id/details': {
    component: PaymentLinkDetails,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedView('invoices') && !extraConfig?.i18?.isConfigTagEnabled('invoices.invoice'),
  },
  '/route/accounts/:id': { component: AccountDetailsNew },
  '/route/optimizer/accounts/:id': {
    component: OptimizerAccountDetails,
    additionalCondition: (user) => user?.isOptimizerRouteEnabled,
  },
  '/smartcollect/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/smartcollect/batchuploads/:id': { component: VirtualAccountBatchDetails },
  // QR Code
  '/qr_codes/:id(qr_.+)': {
    component: QRCodeDetails,
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
  '/route/platformfee/:id(trf_.+)': { component: PlatformFeeDetails },
  '/route/reversals/:id(rvrsl_.+)': { component: ReversalDetails },
  '/route/batchuploads/:id(batch_.+)': {
    component: MarketplaceBatchDetails,
  },

  '/partners/submerchants/:id(acc_.+)/:appId': {
    component: SubmerchantDetails,
  },
  '/partners/submerchants/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/submerchants/x/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/submerchants/capital/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/submerchants/pos/:id(acc_.+)': { component: PosSubmerchantDetails },
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
  '/developers/apis/:id': {
    component: RequestLogDetails,
    additionalCondition: (user) =>
      !isMobileResolution() &&
      user.isAllowedView('developers_console') &&
      user.isDeveloperConsoleEnabled,
  },
  '/developers/webhooks/:id/event/:id': {
    component: WebhookEventDetails,
    additionalCondition: (user) =>
      !isMobileResolution() &&
      user.isAllowedView('developers_console') &&
      user.isDeveloperConsoleWebhooksTabEnabled,
  },
};

/*
 * Example:
 * - '/paymentlinks/new': {component: PaymentLinkCreate, featureEnabled: "randomFeature", featureEnabled: "randomFeature"}
 * */

const entityModalsMap = {
  '/activation': {
    component: (props) => (
      <EasyOnboardingWrapper>
        <ActivationContainer {...props} />
      </EasyOnboardingWrapper>
    ),
    additionalCondition: (user) => user.isAllowedEdit('activation'),
  },
  '/offers/new': {
    component: OffersNew,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('offers') && !extraConfig?.i18?.isConfigTagEnabled('offers.offers'),
  },
  '/paymentlinks/new': {
    component: PaymentLinkCreate,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('payment_links') &&
      !extraConfig?.i18?.isConfigTagEnabled('payment_links.payment_link'),
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
    additionalCondition: (user, extraConfig) =>
      !extraConfig?.i18?.isConfigTagEnabled('subscriptions.subscription'),
  },
  '/subscriptions/:id(sub_.+)/edit': {
    component: UpdateSubscriptionLink,
  },
  '/smartcollect/virtualaccounts/new': {
    component: VirtualAccountCreate,
    additionalCondition: (_, extraConfig) =>
      !extraConfig?.i18?.isConfigTagEnabled('smart_collect.virtual_accounts'),
  },
  '/virtualaccounts/new': {
    component: VirtualAccountCreate,
    additionalCondition: (_, extraConfig) =>
      !extraConfig?.i18?.isConfigTagEnabled('smart_collect.virtual_accounts'),
  },
  '/qr_codes/new': {
    component: QRCodeCreate,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('qr_codes') && !extraConfig?.i18?.isConfigTagEnabled('qr_code.qr_code'),
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
    additionalCondition: (user, extraConfig) =>
      user.isAllowedView('stores') &&
      isExperimentEnabled(extraConfig?.splitz?.abExperiments.stores) &&
      !extraConfig?.i18?.isConfigTagEnabled('stores.stores'),
  },
  '/stores/products/:product_id': {
    component: StoresProductsCreate,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedView('stores') &&
      isExperimentEnabled(extraConfig?.splitz?.abExperiments.stores),
  },
  '/partners/submerchants/:submerchantId(acc_.+)/activation': {
    component: SubmerchantActivationContainer,
    additionalCondition: (user) => user.isSubMerchantKycEnabled,
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
  '/bouncememo/:id': {
    component: PdfDetails,
    additionalCondition: (user) => user.isAllowedView('payments'),
  },
  '/paymentpages/new': {
    component: PaymentPagesCreateEdit,
    additionalCondition: (user) => user.isAllowedEdit('payment_pages'),
  },
  [`${BATCH_PAYMENT_PAGES_BASE_URL}/new`]: {
    component: (props) => <PaymentPagesWysiwyg {...props} isBatchPaymentPages />,
    additionalCondition: (user) => user?.isPaymentPageFileUploadEnabled,
  },
  [`${BATCH_PAYMENT_PAGES_BASE_URL}/:id(pl_.+)/batchuploadsubpage`]: {
    component: BatchUploadSubPage,
    additionalCondition: (user) => user?.isPaymentPageFileUploadEnabled,
  },
  [`${BATCH_PAYMENT_PAGES_BASE_URL}/:id(pl_.+)/success`]: {
    component: (props) => <PaymentPagesSuccess {...props} isBatchPaymentPages />,
    additionalCondition: (user) => user?.isPaymentPageFileUploadEnabled,
  },
  [`${BATCH_PAYMENT_PAGES_BASE_URL}/:id(pl_.+)/edit`]: {
    component: (props) => <PaymentPagesWysiwyg {...props} isBatchPaymentPages />,
    additionalCondition: (user) => user?.isPaymentPageFileUploadEnabled,
  },
  '/paymentpages/:id(pl_.+)/edit': {
    component: PaymentPagesWysiwyg,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('payment_pages') &&
      !extraConfig?.i18?.isConfigTagEnabled('payment_pages.payment_pages'),
  },
  '/paymentpages/:id(pl_.+)/success': {
    component: PaymentPagesSuccess,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('payment_pages') &&
      !extraConfig?.i18?.isConfigTagEnabled('payment_pages.payment_pages'),
  },
  '/paymentpages/storefront/:id(st_.+)/edit': {
    component: PaymentPagesStorefront,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('payment_pages') &&
      !extraConfig?.i18?.isConfigTagEnabled('payment_pages.payment_pages') &&
      user.isPaymentPageStorefrontEnabled,
  },
  '/paymentpages/storefront/:id(st_.+)/success': {
    component: PaymentPagesStorefrontSuccess,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('payment_pages') &&
      !extraConfig?.i18?.isConfigTagEnabled('payment_pages.payment_pages') &&
      user.isPaymentPageStorefrontEnabled,
  },
  '/paymentbuttons/new': {
    component: PaymentButtonCreate,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('payment_buttons') &&
      user.isPaymentButtonEnabledByRazorX &&
      !extraConfig?.i18?.isConfigTagEnabled('payment_buttons.payment_buttons'),
  },
  '/paymentbuttons/:id(pl_.+)/edit': {
    component: PaymentButtonCreate,
    additionalCondition: (user) =>
      user.isAllowedEdit('payment_buttons') && user.isPaymentButtonEnabledByRazorX,
  },
  '/subscription_buttons/new': {
    component: SubscriptionButtonCreate,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('subscription_buttons') &&
      user.isSubscriptionButtonEnabled &&
      !extraConfig?.i18?.isConfigTagEnabled('subscriptions.subscription_payment_button'),
  },
  '/subscription_buttons/:id(pl_.+)/edit': {
    component: SubscriptionButtonCreate,
    additionalCondition: (user, extraConfig) =>
      user.isAllowedEdit('subscription_buttons') &&
      user.isSubscriptionButtonEnabled &&
      !extraConfig?.i18?.isConfigTagEnabled('subscriptions.subscription_payment_button'),
  },
  '/onboarding/steps': {
    component: (props) => (
      <EasyOnboardingWrapper>
        <ActivationSteps {...props} />
      </EasyOnboardingWrapper>
    ),
    additionalCondition: (user) => user.isOnboardingV2Enabled,
  },
  '/onboarding/form': {
    component: (props) => (
      <EasyOnboardingWrapper>
        <ActivationForm {...props} />
      </EasyOnboardingWrapper>
    ),
    additionalCondition: (user) => user.isOnboardingV2Enabled,
  },
  '/configuration/magic/:rest*': {
    component: MagicFPVWithHeader,
  },
  '/kyc': {
    component: (props) => (
      <EasyOnboardingWrapper>
        <ActivationFullViewContainer {...props} />
      </EasyOnboardingWrapper>
    ),
    additionalCondition: (user) => user.isActivationFormFullView,
  },
  '/app-store/:partner': {
    component: PartnerPage,
    additionalCondition: (_, extraConfig) =>
      !extraConfig?.i18?.isConfigTagEnabled('app_store.app_store'),
  },
  '/app-store': {
    component: PartnerAppStore,
    additionalCondition: (_, extraConfig) => {
      return !extraConfig?.i18?.isConfigTagEnabled('app_store.app_store');
    },
  },
  '/rize-marketplace': {
    component: RizeMarketplacePage,
  },
  '/rize-marketplace/:slug': {
    component: RizeMarketplaceListingPage,
  },
  '/tncform': {
    component: GenerateTnC,
    additionalCondition: (user) => user.canGenerateTnCPage,
  },
  '/partners/onboarding': {
    component: PartnerActivationFormMweb,
    additionalCondition: (user) => user.isIndependentPartnerKYCEnabled,
  },
  '/partner-pricing-plans': {
    component: PartnerPricingPlans,
  },
  '/partners/submerchants/onboarding/:submerchantId(acc_.+)/steps': {
    component: ActivationSteps,
    additionalCondition: (user) => user.isSubMerchantKycEnabled,
  },
  '/partners/submerchants/onboarding/:submerchantId(acc_.+)/form': {
    component: ActivationForm,
    additionalCondition: (user) => user.isSubMerchantKycEnabled,
  },
  '/partners/submerchants/pos/:submerchantId/orders': {
    component: ClientPOSOrderList,
    additionalCondition: (user, extraConfig) =>
      isPartnershipsForPosEnabled({ abExperiments: extraConfig?.splitz?.abExperiments, user }),
  },
  '/app-support': {
    component: AppSupport,
  },
};

export const matchDetail = matchDetailx(store, entityDetailsMap);
export const matchModal = matchModalx(store, entityModalsMap);
export const matchFullPageView = matchFullPageViewx(store, fullPageViewsMap);
