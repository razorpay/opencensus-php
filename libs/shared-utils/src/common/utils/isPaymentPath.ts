import { ROUTE_REG, BASE_ROUTES } from '../constants';

const routeRegexMap = Object.fromEntries(
  Object.entries(BASE_ROUTES).map(([key, path]) => {
    const pattern = '^' + path.replace(/\//g, '\\/');
    return [key, new RegExp(pattern)];
  }),
);

const dashboardRoutesMap = {
  ...routeRegexMap,
  ...ROUTE_REG,
  connectedhome: /^\/home\/?$/, // added this for connected home mobile
};

type IsPaymentsPathParams = {
  currentPath: string;
  isOneHomeEnabled: boolean;
};

export const isPaymentsPath = ({ currentPath, isOneHomeEnabled }: IsPaymentsPathParams) => {
  // Create a copy of the dashboardRoutesMap
  const routesMap = { ...dashboardRoutesMap };

  // If OneHome is enabled, remove the connectedhome route from payments as it should be handled by the OneHome app
  if (isOneHomeEnabled) {
    delete routesMap.connectedhome;
  }

  return Object.values(routesMap).some((regex) => {
    return regex.test(currentPath);
  });
};
