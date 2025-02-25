import React from 'react';
import loadable, { LoadableComponent } from '@loadable/component';

type Route = {
  path: string;
  element: React.ReactNode;
  loadableChunk: LoadableComponent<Record<string, unknown>>;
  exact?: boolean;
  cacheExpirySeconds?: number;
  isABEnabled?: boolean;
};

type Routes = Route[];

export const paths = {
  WEB: '/app/dashboard',
  SELF_SERVE: '/app/payments',
};

const HOUR = 60 * 60;

export const routes: Routes = [
  // {
  //   path: paths.WEB,
  //   element: <Web />,
  //   loadableChunk: Web,
  //   cacheExpirySeconds: 1 * HOUR,
  //   isABEnabled: true,
  // },
  // {
  //   path: paths.SELF_SERVE,
  //   element: <SelfServe />,
  //   loadableChunk: SelfServe,
  //   cacheExpirySeconds: 24 * HOUR,
  // },
];
