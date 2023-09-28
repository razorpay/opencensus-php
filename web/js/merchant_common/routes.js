/* eslint-disable */
import { matchPath } from 'react-router';
import { showWhenUtil } from './components/RouteGuard';
import { validateRoute } from 'common/utils/validateRoute';
// import {withRouter} from "common/deprecated/withRouter"
export function matchDetail(store, entityDetailsMap) {
  return (pathname) => matcher(store)(entityDetailsMap, pathname);
}

export function matchModal(store, entityModalsMap) {
  return (pathname) => matcher(store)(entityModalsMap, pathname);
}

export function matchFullPageView(store, fullPageViewMap) {
  return (pathname) => matcher(store)(fullPageViewMap, pathname);
}

// export const renderWithRouteProps = () => withRouter(() => {

// })

function matcher(store) {
  return (routeMap, pathname) => {
    for (let route in routeMap) {
      const match = matchPath(validateRoute(route, pathname), pathname);
      const { component, ...rest } = routeMap[route];

      if (match && showWhenUtil(store)(rest)) {
        return {
          match,
          component: component,
        };
      }
    }
  };
}
