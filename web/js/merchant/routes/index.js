import store from 'merchant/store';
import {
  matchDetail as matchDetailx,
  matchModal as matchModalx,
  matchFullPageView as matchFullPageViewx,
} from 'merchant_common/routes';

import SettlementDetails from 'merchant/views/Settlements/Details';
import PaymentLinkDetails from 'merchant/views/PaymentLinks/PaymentLinks/Details';
import PaymentLinkCreate from 'merchant/views/PaymentLinks/PaymentLinks/Create/index';
import PaymentPagesWysiwyg from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg';
import PaymentsDetails from 'merchant/views/Transactions/Payments/Details';
import RefundDetails from 'merchant/views/Transactions/Refunds/Details';
import OrderDetails from 'merchant/views/Transactions/Orders/Details';

import VirtualAccountDetails from 'merchant/views/SmartCollect/VirtualAccounts/Details';
import VirtualAccountCreate from 'merchant/views/SmartCollect/VirtualAccounts/Create/index';

import OffersNew from 'merchant/views/Offers/New';
import OfferEntity from 'merchant/views/Offers/Entity';
import PlanDetails from 'merchant/views/Subscriptions/Plans/Details';
import SubscriptionDetails from 'merchant/views/Subscriptions/Subscriptions/Details';
import TransferDetails from 'merchant/views/Marketplace/Transfers/Details';
import ReversalDetails from 'merchant/views/Marketplace/Reversals/Details';
import DisputeDetails from 'merchant/views/Transactions/Disputes/Details';
import SubmerchantDetails from 'merchant/views/PartnerDashboard/SubMerchant/Details';
import EarningTransactionalDetails from 'merchant/views/PartnerDashboard/Earnings/Transactional/Details';
import EarningDailyDetails from 'merchant/views/PartnerDashboard/Earnings/Daily/Details';
import CommissionInvoiceDetails from 'merchant/views/PartnerDashboard/Earnings/Invoices/Details';
import SubventionTransactionalDetails from 'merchant/views/PartnerDashboard/Subvention/Transactional/Details';
import SubventionDailyDetails from 'merchant/views/PartnerDashboard/Subvention/Daily/Details';
import RegistrationLinkDetails from 'merchant/views/Subscriptions/RegistrationLinks/Details';
import UploadNACHForm from 'merchant/views/Subscriptions/components/UploadNACHForm';
import AccountDetailsNew from 'merchant/views/Marketplace/Accounts/DetailsNew';

import TokenDetails from 'merchant/views/Subscriptions/Tokens/Details';

import PaymentLinkBatchUploadDetails from 'merchant/views/PaymentLinks/BatchUpload/Details';
import SubscriptionBatchDetails from 'merchant/views/Subscriptions/Batch/Details';

import PlanNew from 'merchant/views/Subscriptions/Plans/New';
import ActivationContainer from 'merchant/containers/Activation';
import NewRegistrationLink from 'merchant/views/Subscriptions/RegistrationLinks/New';
import NewSubscriptionLink from 'merchant/views/Subscriptions/SubscriptionLinks/New';
import UpdateSubscriptionLink from 'merchant/views/Subscriptions/SubscriptionLinks/Update';
import CreditSubDetails from 'merchant/views/Account/Credits/components/CreditSubDetails';

/*
 * NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
 * */

const entityDetailsMap = {
  '/payments/:id(pay_.+)/:entity_name(transfers)/new': {
    component: PaymentsDetails,
    additionalCondition: user => user.isAllowedEdit('payments'),
  },
  '/payments/:id(pay_.+)': {
    component: PaymentsDetails,
    additionalCondition: user => user.isAllowedView('payments'),
  },

  '/refunds/:id(rfnd_.+)': {
    component: RefundDetails,
    additionalCondition: user => user.isAllowedView('refunds'),
  },
  '/offers/:id(offer_.+)': {
    component: OfferEntity,
    additionalCondition: user => user.isAllowedView('offers'),
  },
  '/orders/:id': {
    component: OrderDetails,
    additionalCondition: user => user.isAllowedView('orders'),
  },
  '/settlements/:id': {
    component: SettlementDetails,
    additionalCondition: user => user.isAllowedView('settlements'),
  },
  '/paymentlinks/:id(inv_.+|plink_.+)': {
    component: PaymentLinkDetails,
    additionalCondition: user => user.isAllowedView('payment_links'),
  },
  '/paymentlinks/batchuploads/:id(batch_.+)': {
    component: PaymentLinkBatchUploadDetails,
    additionalCondition: user =>
      user.isAllowedView('payment_links_batch_uploads') &&
      (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole),
  },
  '/invoices/:id/details': {
    component: PaymentLinkDetails,
    additionalCondition: user => user.isAllowedView('invoices'),
  },

  '/route/payments/:id': { component: PaymentsDetails },
  '/route/accounts/:id': { component: AccountDetailsNew },
  '/smartcollect/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/plans/new': { component: PlanNew },
  '/plans/:id': { component: PlanDetails },
  '/registration_links/:id(inv_.+)': {
    component: RegistrationLinkDetails,
    additionalCondition: user => user.isChargeAtWillEnabled,
  },

  '/tokens/:id(token_.+)': {
    component: TokenDetails,
    additionalCondition: user => user.isChargeAtWillEnabled,
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

  '/partners/submerchants/:id(acc_.+)/:appId': {
    component: SubmerchantDetails,
  },
  '/partners/submerchants/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/earnings/transactional/:id(comm_.+)': {
    component: EarningTransactionalDetails,
    additionalCondition: user =>
      user.isAllowedView('earnings') && user.isHavingPartnerConfigs,
  },
  '/partners/subventions/transactional/:id(comm_.+)': {
    component: SubventionTransactionalDetails,
    additionalCondition: user =>
      user.isAllowedView('earnings') && user.isHavingSubventionConfigs,
  },
  '/partners/earnings/daily/:timestamp': {
    component: EarningDailyDetails,
    additionalCondition: user =>
      user.isAllowedView('earnings') && user.isHavingPartnerConfigs,
  },
  '/partners/subventions/daily/:timestamp': {
    component: SubventionDailyDetails,
    additionalCondition: user =>
      user.isAllowedView('earnings') && user.isHavingSubventionConfigs,
  },
  '/partners/earnings/invoices/:id': {
    component: CommissionInvoiceDetails,
    additionalCondition: user =>
      user.isAllowedView('earnings') &&
      user.isCommissionInvoicesEnabled &&
      user.isHavingPartnerConfigs,
  },
  '/disputes/:id(disp_.+)': {
    component: DisputeDetails,
    additionalCondition: user => user.isAllowedView('payments'),
  },
  '/credits/:id(credits_.+)': { component: CreditSubDetails },
};

/*
 * Example:
 * - '/paymentlinks/new': {component: PaymentLinkCreate, featureEnabled: "randomFeature", featureEnabled: "randomFeature"}
 * */
const entityModalsMap = {
  '/activation': {
    component: ActivationContainer,
    additionalCondition: user => user.isAllowedEdit('activation'),
  },
  '/offers/new': {
    component: OffersNew,
    additionalCondition: user => user.isAllowedEdit('offers'),
  },
  '/paymentlinks/new': {
    component: PaymentLinkCreate,
    additionalCondition: user => user.isAllowedEdit('payment_links'),
  },
  '/registration_links/:id(inv_.+)/upload_nach': {
    component: UploadNACHForm,
    additionalCondition: user => user.isChargeAtWillEnabled,
  },
  '/registration_links/new': {
    component: NewRegistrationLink,
    additionalCondition: user => user.isChargeAtWillEnabled,
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
    additionalCondition: user => user.isAllowedEdit('payment_pages'),
  },
  '/paymentpages/:id(pl_.+)/edit': {
    component: PaymentPagesWysiwyg,
    additionalCondition: user => user.isAllowedEdit('payment_pages'),
  },
};

export const matchDetail = matchDetailx(store, entityDetailsMap);
export const matchModal = matchModalx(store, entityModalsMap);
export const matchFullPageView = matchFullPageViewx(store, fullPageViewsMap);
