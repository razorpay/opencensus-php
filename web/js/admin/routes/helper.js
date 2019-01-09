import { matchPath } from 'react-router-dom';
import { showWhenUtil, ShowWhenRoute } from 'admin/components/ShowWhen';
import { isOrgRazorpay } from 'admin/user';

import RazorX, { Sidebar as RazorXSidebar } from 'admin/razorx';

const fullPageRoutes = {
  '/razorx': {
    component: RazorX,
    sidebar: RazorXSidebar,
    permission: 'view-razorx',
    additionalCondition: _ => isOrgRazorpay(),
  },
};

export const matchFullPageView = pathname => matcher(fullPageRoutes, pathname);

function matcher(routeMap, pathname) {
  for (let route in routeMap) {
    const match = matchPath(pathname, route);
    const { component, sidebar, ...rest } = routeMap[route];

    if (match && showWhenUtil({ ...rest })) {
      return {
        match,
        component,
        sidebar,
      };
    }
  }
}

export function RZPRoute(props) {
  return (
    <ShowWhenRoute {...props} additionalCondition={_ => isOrgRazorpay()} />
  );
}
