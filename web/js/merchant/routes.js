import { matchPath } from 'react-router-dom';
import { showWhenUtil } from 'merchant/components/ShowWhen';

import SettlementDetails from 'merchant/containers/Settlements/Details';
import PaymentLinkEntity from 'merchant/containers/PaymentLinks/Links/Entity';
import PaymentPages from 'merchant/containers/PaymentPages/Pages/Entity';
import PaymentLinksCreate from 'merchant/containers/PaymentLinks/Links/Create/index';
import PaymentPagesCreate from 'merchant/containers/PaymentPages/Pages/Create/index';
import PaymentsDetails from 'merchant/containers/Payments/Details';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrderDetails from 'merchant/containers/Orders/Details';
import VirtualAccountDetails from 'merchant/containers/VirtualAccounts/Details';
import PlanDetails from 'merchant/containers/Plans/Details';
import SubscriptionDetails from 'merchant/containers/Subscriptions/Details';
import TransferDetails from 'merchant/containers/Marketplace/Transfers/Details';
import ReversalDetails from 'merchant/containers/Marketplace/Reversals/Details';
import DisputeDetails from 'merchant/containers/Disputes/Details';
import PaymentLinkBatchDetails from 'merchant/containers/PaymentLinks/BatchDetails';

import PlanNew from 'merchant/containers/Plans/New';
import ActivationContainer from 'merchant/containers/Activation/new';

/*
* NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
* */

const entityDetailsMap = {
  '/payments/:id(pay_.+)/:entity_name(transfers)/new': {
    component: PaymentsDetails,
  },
  '/payments/:id(pay_.+)/:transfer_id(trf_.+)': { component: PaymentsDetails },
  '/payments/:id(pay_.+)': { component: PaymentsDetails },

  '/refunds/:id(rfnd_.+)': { component: RefundDetails },
  '/orders/:id': { component: OrderDetails },
  '/settlements/:id': { component: SettlementDetails },
  '/paymentlinks/:id(inv_.+)': { component: PaymentLinkEntity },
  '/paymentlinks/batchuploads/:id(batch_.+)': {
    component: PaymentLinkBatchDetails,
  },
  '/paymentpages/:id(pl_.+)': { component: PaymentPages },
  '/invoices/:id/details': { component: PaymentLinkEntity },

  '/route/payments/:id': { component: PaymentsDetails },
  '/virtualaccounts/:id': { component: VirtualAccountDetails },
  '/plans/new': { component: PlanNew },
  '/plans/:id': { component: PlanDetails },

  '/subscriptions/:id(sub_.+)/:invoice_id(inv_.+)': {
    component: SubscriptionDetails,
  },
  '/subscriptions/:id(sub_.+)': { component: SubscriptionDetails },

  '/route/transfers/:id': { component: TransferDetails },
  '/route/reversals/:id': { component: ReversalDetails },

  '/disputes/:id(disp_.+)': { component: DisputeDetails },
};

/*
* Example:
* - '/paymentlinks/new': {component: PaymentLinksCreate, featureEnabled: "randomFeature", featureEnabled: "randomFeature"}
* */
const entityModalsMap = {
  '/activation': { component: ActivationContainer },
  '/paymentlinks/new': { component: PaymentLinksCreate },
  '/paymentpages/new': {
    component: PaymentPagesCreate,
    featureEnabled: 'paymentpages',
  },
};

export function matchDetail(pathname) {
  return matcher(entityDetailsMap, pathname);
}

export function matchModal(pathname) {
  return matcher(entityModalsMap, pathname);
}

function matcher(routeMap, pathname) {
  for (let route in routeMap) {
    var match = matchPath(pathname, route);

    var { component, ...rest } = routeMap[route];

    if (match && showWhenUtil(rest)) {
      return {
        match,
        component: component,
      };
    }
  }
}
