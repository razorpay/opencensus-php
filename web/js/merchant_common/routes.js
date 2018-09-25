import { matchPath } from 'react-router-dom';
import { showWhenUtil } from './components/ShowWhen';

export function matchDetail(store, entityDetailsMap) {
  return pathname => matcher(store)(entityDetailsMap, pathname);
}

export function matchModal(store, entityModalsMap) {
  return pathname => matcher(store)(entityModalsMap, pathname);
}

function matcher(store) {
  return function(routeMap, pathname) {
    for (let route in routeMap) {
      var match = matchPath(pathname, route);

      var { component, ...rest } = routeMap[route];

      if (match && showWhenUtil(store)(rest)) {
        return {
          match,
          component: component,
        };
      }
    }
  };
}
