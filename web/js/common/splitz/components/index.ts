import { lazy } from 'react';

export const SplitzEvalLoader = lazy(
  () => import(/* webpackChunkName: "SplitzEvalLoader" */ './SplitzEvalLoader'),
);
