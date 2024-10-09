import React from 'react';
import { Navigate, RouteObject } from 'react-router-dom';

import MerchantOnboardingRoutes, {
  MerchantOnboardingLanding,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding';
import SalesDashboard from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard';
import MerchantOnboardingStep from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/MerchantOnboardingStep';
import MerchantOnboardingComponent from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components';
import BasicInfo from 'apps/pos/src/app/views/SalesAssistedOnboarding/BasicInfo';
import PwaInstall from 'apps/pos/src/app/views/PosEkyc/PwaInstall';

export const BASE_ROUTE = 'pos-sales';
export const ONBOARDING_ROUTE = 'onboarding';

export const PARENT_ROUTE_CONFIG: RouteObject[] = [
  {
    path: '/',
    element: <SalesDashboard />,
  },
  {
    path: 'basic-info',
    element: <BasicInfo />,
  },
  {
    path: '/join',
    element: <PwaInstall />,
  },
  {
    path: 'onboarding/*',
    element: <MerchantOnboardingRoutes />,
  },
  {
    path: '*',
    element: <SalesDashboard />,
  },
];

export const MERCHANT_ONBOARDING_ROUTES: RouteObject[] = [
  {
    path: ':id',
    children: [
      {
        path: '',
        element: <MerchantOnboardingLanding />,
      },
      {
        path: ':step',
        children: [
          {
            path: '',
            element: <MerchantOnboardingStep />,
          },
          {
            path: ':component',
            element: <MerchantOnboardingComponent />,
          },
        ],
      },
      {
        path: '*',
        element: <Navigate to="." replace={true} />,
      },
    ],
  },
  {
    path: '*',
    element: <Navigate to="/pos-sales" replace={true} />,
  },
];
