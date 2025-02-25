/* eslint-disable */
import { matchPath } from 'react-router-dom';
import { showWhenUtil } from './components/RouteGuard';
import { validateRoute } from 'common/utils/validateRoute';
// import {withRouter} from "common/deprecated/withRouter"
export function matchDetail(store, entityDetailsMap) {
  return (pathname, extraConfig) => matcher(store)(entityDetailsMap, pathname, extraConfig);
}

export function matchModal(store, entityModalsMap) {
  return (pathname, extraConfig) => matcher(store)(entityModalsMap, pathname, extraConfig);
}

export function matchFullPageView(store, fullPageViewMap) {
  return (pathname, extraConfig) => matcher(store)(fullPageViewMap, pathname, extraConfig);
}

// export const renderWithRouteProps = () => withRouter(() => {

// })

function matcher(store) {
  return (routeMap, pathname, extraConfig) => {
    for (let route in routeMap) {
      const match = matchPath(validateRoute(route, pathname), pathname);
      const { component, ...rest } = routeMap[route];

      if (match && showWhenUtil(store)(rest, extraConfig)) {
        return {
          match,
          component: component,
        };
      }
    }
  };
}
