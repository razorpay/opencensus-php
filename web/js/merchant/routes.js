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
import DisputeDetails from 'merchant/containers/Disputes/Details';
import PaymentLinkBatchDetails from 'merchant/containers/PaymentLinks/BatchDetails';
import SubmerchantDetails from 'merchant/containers/PartnerDashboard/SubMerchant/Entity';

import PlanNew from 'merchant/containers/Plans/New';
import ActivationContainer from 'merchant/containers/Activation/new';

/*
* NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
* */

const entityDetailsMap = {
  '/payments/:id(pay_.+)/:entity_name(transfers)/new': PaymentsDetails,
  '/payments/:id(pay_.+)/:transfer_id(trf_.+)': PaymentsDetails,
  '/payments/:id(pay_.+)': PaymentsDetails,

  '/refunds/:id(rfnd_.+)': RefundDetails,
  '/orders/:id': OrderDetails,
  '/settlements/:id': SettlementDetails,
  '/paymentlinks/:id(inv_.+)': PaymentLinkDetails,
  '/paymentlinks/batchuploads/:id(batch_.+)': PaymentLinkBatchDetails,
  '/invoices/:id/details': PaymentLinkDetails,

  '/route/payments/:id': PaymentsDetails,
  '/virtualaccounts/:id': VirtualAccountDetails,
  '/plans/new': PlanNew,
  '/plans/:id': PlanDetails,

  '/subscriptions/:id(sub_.+)/:invoice_id(inv_.+)': SubscriptionDetails,
  '/subscriptions/:id(sub_.+)': SubscriptionDetails,

  '/route/transfers/:id': TransferDetails,

  '/disputes/:id(disp_.+)': DisputeDetails,

  '/submerchants/:id(acc_.+)': SubmerchantDetails,
};

const entityModalsMap = {
  '/activation': ActivationContainer,
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
    if (match) {
      return {
        match,
        component: routeMap[route],
      };
    }
  }
}
