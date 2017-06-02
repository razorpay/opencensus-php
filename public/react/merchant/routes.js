import { matchPath } from 'react-router-dom';

import SettlementDetails from 'merchant/containers/Settlements/Details';
import PaymentLinkDetails from 'merchant/containers/PaymentLinks/Details';
import PaymentsDetails from 'merchant/containers/Payments/Details';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrderDetails from 'merchant/containers/Orders/Details';

const entityMap = {
  '/payments/:id': PaymentsDetails,
  '/refunds/:id(rfnd_.+)': RefundDetails,
  '/orders/:id': OrderDetails,
  '/settlements/:id': SettlementDetails,
  '/paymentlinks/:id': PaymentLinkDetails,
  '/invoices/:id/details': PaymentLinkDetails,
};

export function matchDetail(pathname) {
  return matcher(entityMap, pathname);
}

function matcher(routeMap, pathname) {
  for (let route in routeMap) {
    var match = matchPath(pathname, route);
    if (match) {
      var MatchedComponent = routeMap[route];
      return props => <MatchedComponent match={match} {...props} />;
    }
  }
}
