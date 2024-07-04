export const convertMagicRoutesToConfigurationFlow = (routes) => {
  return routes.map((_route) => {
    const route = { ..._route };
    route.path = route.path.replace('/magic/', '/configuration/magic/');
    return route;
  });
};

export const checkMagicConfigurationFlow = () => location.pathname.includes('configuration/magic');
