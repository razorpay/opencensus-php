import store from 'merchant/store';
import {
  matchDetail as matchDetailx,
  matchModal as matchModalx,
  matchFullPageView as matchFullPageViewx,
} from '../merchant_common/routes';

import SettlementDetails from 'merchant/views/Settlements/Details';
import PaymentLinkDetails from 'merchant/containers/PaymentLinks/Links/Details';
import PaymentPageDetails from 'merchant/views/PaymentPages/PaymentPages/Details';
import PaymentLinkCreate from 'merchant/containers/PaymentLinks/Links/Create/index';
import PaymentPagesWysiwyg from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg';
import PaymentsDetails from 'merchant/views/Transactions/Payments/Details';
import RefundDetails from 'merchant/views/Transactions/Refunds/Details';
import OrderDetails from 'merchant/views/Transactions/Orders/Details';
import VirtualAccountEntity from 'merchant/containers/VirtualAccounts/Entity';
import VirtualAccountCreate from 'merchant/containers/VirtualAccounts/CreateVirtualAccount';

import OffersNew from 'views/Offers/New';
import OfferEntity from 'views/Offers/Entity';
import PlanDetails from 'merchant/containers/Plans/Details';
import SubscriptionDetails from 'merchant/containers/Subscriptions/Details';
import TransferDetails from 'merchant/containers/Marketplace/Transfers/Details';
import ReversalDetails from 'merchant/containers/Marketplace/Reversals/Details';
import DisputeDetails from 'merchant/views/Transactions/Disputes/Details';
import SubmerchantDetails from 'merchant/containers/PartnerDashboard/SubMerchant/Entity';
import EarningTransactionalDetails from 'merchant/containers/PartnerDashboard/Earnings/Transactional/Entity';
import EarningDailyDetails from 'merchant/containers/PartnerDashboard/Earnings/Daily/Entity';
import SubventionTransactionalDetails from 'merchant/containers/PartnerDashboard/Subvention/Transactional/Entity';
import SubventionDailyDetails from 'merchant/containers/PartnerDashboard/Subvention/Daily/Entity';
import RegistrationLink from 'merchant/containers/Subscriptions/RegistrationLinks/Entity';
import UploadNACHForm from 'merchant/components/Subscriptions/UploadNACHForm';
import AccountDetailsNew from 'merchant/containers/Marketplace/Accounts/DetailsNew';

import Token from 'merchant/containers/Subscriptions/Tokens/Entity';

import PaymentLinkBatchDetails from 'merchant/containers/PaymentLinks/BatchDetails';
import SubscriptionBatchDetails from 'merchant/containers/Subscriptions/Batch/Entity';

import PlanNew from 'merchant/containers/Plans/New';
import ActivationContainer from 'merchant/containers/Activation';
import NewRegistrationLink from 'merchant/containers/Subscriptions/RegistrationLinks/New';
import NewSubscriptionLink from 'merchant/containers/Subscriptions/SubscriptionLinks/New';
import UpdateSubscriptionLink from 'merchant/containers/Subscriptions/SubscriptionLinks/Update';
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
  '/paymentlinks/:id(inv_.+)': {
    component: PaymentLinkDetails,
    additionalCondition: user => user.isAllowedView('payment_links'),
  },
  '/paymentlinks/batchuploads/:id(batch_.+)': {
    component: PaymentLinkBatchDetails,
    additionalCondition: user =>
      user.isAllowedView('payment_links_batch_uploads') &&
      (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole),
  },
  '/paymentpages/:id(pl_.+)': {
    component: PaymentPageDetails,
    additionalCondition: user =>
      user.isAllowedView('payment_pages') && !user.isPPMLIEnabled,
  },
  '/invoices/:id/details': {
    component: PaymentLinkDetails,
    additionalCondition: user => user.isAllowedView('invoices'),
  },

  '/route/payments/:id': { component: PaymentsDetails },
  '/route/accounts/:id': { component: AccountDetailsNew },
  '/virtualaccounts/:id': { component: VirtualAccountEntity },
  '/plans/new': { component: PlanNew },
  '/plans/:id': { component: PlanDetails },
  '/registration_links/:id(inv_.+)': {
    component: RegistrationLink,
    additionalCondition: user => user.isChargeAtWillEnabled,
  },

  '/tokens/:id(token_.+)': {
    component: Token,
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
