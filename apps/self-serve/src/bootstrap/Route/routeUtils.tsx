import React, { useMemo } from 'react';
import { useRoutes } from 'react-router-dom';
import { Route } from './typing';

export function RouterConfig({ routes }: { routes: Route[] }): React.ReactElement | null {
  const routeList = useRoutes(routes);
  return routeList;
}

// create local routing build for transactions app
export const useRouteLocalBuild = ({
  localBuild = false,
  routes,
}: {
  localBuild?: boolean;
  routes: Route[];
}): Route[] => {
  const __routes = useMemo(() => {
    // if (localBuild) {
    //   const _routes = [...routes];
    //   _routes[0].path = path;
    //   return _routes;
    // }
    return routes;
  }, [localBuild]);

  return __routes;
};
