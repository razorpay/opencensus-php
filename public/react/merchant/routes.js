import { matchPath } from 'react-router-dom';

import SettlementDetails from 'merchant/containers/Settlements/Details';
import PaymentLinkDetails from 'merchant/containers/PaymentLinks/Details';
import PaymentsDetails from 'merchant/containers/Payments/Details';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrderDetails from 'merchant/containers/Orders/Details';
import VirtualAccountDetails from 'merchant/containers/VirtualAccounts/Details';
import PlanDetails from 'merchant/containers/Plans/Details';
import SubscriptionDetails from 'merchant/containers/Subscriptions/Details';
import TransferDetails from 'merchant/containers/Marketplace/Transfers/Details';

import PlanNew from 'merchant/containers/Plans/New';

const entityMap = {
  '/payments/:id(pay_.+)/:entity_name(transfers)/new': PaymentsDetails,
  '/payments/:id(pay_.+)/:transfer_id(trf_.+)': PaymentsDetails,
  '/payments/:id': PaymentsDetails,

  '/refunds/:id(rfnd_.+)': RefundDetails,
  '/orders/:id': OrderDetails,
  '/settlements/:id': SettlementDetails,
  '/paymentlinks/:id(inv_.+)': PaymentLinkDetails,
  '/invoices/:id/details': PaymentLinkDetails,

  '/route/payments/:id': PaymentsDetails,
  '/virtualaccounts/:id': VirtualAccountDetails,
  '/plans/new': PlanNew,
  '/plans/:id': PlanDetails,

  '/subscriptions/:id(sub_.+)/:invoice_id(inv_.+)': SubscriptionDetails,
  '/subscriptions/:id(sub_.+)': SubscriptionDetails,

  '/route/transfers/:id': TransferDetails,
};

export function matchDetail(pathname) {
  return matcher(entityMap, pathname);
}

function matcher(routeMap, pathname) {
  for (let route in routeMap) {
    var match = matchPath(pathname, route);
    if (match) {
      return {
        match,
        component: routeMap[route],
      };
    }
  }
}
