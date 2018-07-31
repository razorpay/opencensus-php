import { matchPath } from 'react-router-dom';
import { showWhenUtil } from 'merchantLA/components/ShowWhen';

import TransferDetails from 'merchantLA/containers/Marketplace/Transfers/Details';
import SettlementDetails from 'merchantLA/containers/Settlements/Details';
import ReversalDetails from 'merchantLA/containers/Marketplace/Reversals/Details';

/*
* NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
* */

const entityDetailsMap = {
  '/transfers/:id(trf_.+)': { component: TransferDetails },
  '/settlements/:id': { component: SettlementDetails },
  '/reversals/:id': { component: ReversalDetails },
};

/*
* Example:
* - '/{route_name}[/{route_sub_path}]': {component: ComponentName, featureEnabled: "whatFeature", featureEnabled: "whatFeature"}
* */
const entityModalsMap = {};

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
