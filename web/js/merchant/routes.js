import store from 'merchant/store';
import {
  matchDetail as matchDetailx,
  matchModal as matchModalx,
  matchFullPageView as matchFullPageViewx,
} from '../merchant_common/routes';

import SettlementDetails from 'merchant/containers/Settlements/Details';
import PaymentLinkEntity from 'merchant/containers/PaymentLinks/Links/Entity';
import PaymentPages from 'merchant/containers/PaymentPages/Pages/Entity';
import PaymentLinksCreate from 'merchant/containers/PaymentLinks/Links/Create/index';
import PaymentPagesWysiwyg from 'merchant/containers/PaymentPages/Pages/V2/Wysiwyg';
import PaymentsDetails from 'merchant/containers/Payments/Details';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrderDetails from 'merchant/containers/Orders/Details';
import VirtualAccountDetails from 'merchant/containers/VirtualAccounts/Details';
import PlanDetails from 'merchant/containers/Plans/Details';
import SubscriptionDetails from 'merchant/containers/Subscriptions/Details';
import TransferDetails from 'merchant/containers/Marketplace/Transfers/Details';
import ReversalDetails from 'merchant/containers/Marketplace/Reversals/Details';
import DisputeDetails from 'merchant/containers/Disputes/Details';
import SubmerchantDetails from 'merchant/containers/PartnerDashboard/SubMerchant/Entity';
import TransactionalEarningDetails from 'merchant/containers/PartnerDashboard/Commissions/Transactional/Entity';
import DailyEarningDetails from 'merchant/containers/PartnerDashboard/Commissions/Daily/Entity';
import AuthLink from 'merchant/containers/Subscriptions/AuthLinks/Entity';
import AccountDetailsNew from 'merchant/containers/Marketplace/Accounts/DetailsNew';

import Token from 'merchant/containers/Subscriptions/Tokens/Entity';

import PaymentLinkBatchDetails from 'merchant/containers/PaymentLinks/BatchDetails';
import SubscriptionBatchDetails from 'merchant/containers/Subscriptions/Batch/Entity';

import PlanNew from 'merchant/containers/Plans/New';
import ActivationContainer from 'merchant/containers/Activation';
import NewAuthLink from 'merchant/containers/Subscriptions/AuthLinks/New';
import NewSubscriptionLink from 'merchant/containers/Subscriptions/SubscriptionLinks/New';
import UpdateSubscriptionLink from 'merchant/containers/Subscriptions/SubscriptionLinks/Update';
import CreditSubDetails from 'merchant/components/Credits/CreditSubDetails';
import CreditNoteDetails from 'merchant/containers/Invoices/CreditNote/Details';

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
  '/orders/:id': {
    component: OrderDetails,
    additionalCondition: user => user.isAllowedView('orders'),
  },
  '/settlements/:id': {
    component: SettlementDetails,
    additionalCondition: user => user.isAllowedView('settlements'),
  },
  '/paymentlinks/:id(inv_.+)': {
    component: PaymentLinkEntity,
    additionalCondition: user => user.isAllowedView('payment_links'),
  },
  '/paymentlinks/batchuploads/:id(batch_.+)': {
    component: PaymentLinkBatchDetails,
    additionalCondition: user =>
      user.isAllowedView('payment_links_batch_uploads'),
  },
  '/paymentpages/:id(pl_.+)': {
    component: PaymentPages,
    additionalCondition: user => user.isAllowedView('payment_pages'),
  },
  '/invoices/:id/details': {
    component: PaymentLinkEntity,
    additionalCondition: user => user.isAllowedView('invoices'),
  },

  '/route/payments/:id': { component: PaymentsDetails },
  '/route/accounts/:id': { component: AccountDetailsNew },
  '/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/plans/new': { component: PlanNew },
  '/plans/:id': { component: PlanDetails },
  '/authlinks/:id(inv_.+)': {
    component: AuthLink,
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

  '/submerchants/:id(acc_.+)/:appId': { component: SubmerchantDetails },
  '/submerchants/:id(acc_.+)': { component: SubmerchantDetails },
  '/partners/earnings/transactional/:id(comm_.+)': {
    component: TransactionalEarningDetails,
  },
  '/partners/earnings/daily/:timestamp': {
    component: DailyEarningDetails,
  },
  '/disputes/:id(disp_.+)': {
    component: DisputeDetails,
    additionalCondition: user => user.isAllowedView('payments'),
  },
  '/credits/:id(credits_.+)': { component: CreditSubDetails },
};

/*
 * Example:
 * - '/paymentlinks/new': {component: PaymentLinksCreate, featureEnabled: "randomFeature", featureEnabled: "randomFeature"}
 * */
const entityModalsMap = {
  '/activation': {
    component: ActivationContainer,
    additionalCondition: user => user.isAllowedEdit('activation'),
  },
  '/paymentlinks/new': {
    component: PaymentLinksCreate,
    additionalCondition: user => user.isAllowedEdit('payment_links'),
  },
  '/authlinks/new': {
    component: NewAuthLink,
    additionalCondition: user => user.isChargeAtWillEnabled,
  },
  '/subscriptions/new': {
    component: NewSubscriptionLink,
  },
  '/subscriptions/:id(sub_.+)/edit': {
    component: UpdateSubscriptionLink,
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
