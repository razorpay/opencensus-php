import { ROUTE_REG, BASE_ROUTES } from 'merchant/components/SidebarV2/utils/href';

const routeRegexMap = Object.fromEntries(
  Object.entries(BASE_ROUTES).map(([key, path]) => {
    const pattern = '^' + path.replace(/\//g, '\\/') + '$';
    return [key, new RegExp(pattern)];
  }),
);

const dashboardRoutesMap = {
  ...ROUTE_REG,
  ...routeRegexMap,
  connectedhome: /^\/home\/?$/, // added this for connected home mobile
};

const isPaymentsPath = (currentPath) => {
  return Object.values(dashboardRoutesMap).some((regex) => {
    return regex.test(currentPath);
  });
};

export default isPaymentsPath;
