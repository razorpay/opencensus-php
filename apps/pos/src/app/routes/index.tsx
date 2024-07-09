import React from 'react';
import SalesDashboard from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard';
import { RouteConfig } from 'apps/pos/src/app/types/common';

export const getParentRouteConfig = (): RouteConfig => ({
  fallback: '/pos-sales',
  child: [
    {
      route: '/',
      view: <SalesDashboard />,
    },
  ],
});
