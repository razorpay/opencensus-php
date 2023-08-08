import React, { ComponentType } from 'react';
import { useSplitzService } from 'common/splitz/hooks/useSplitzService';
import { SpiltzContextState } from 'common/splitz/types';

/**
 * A HOC wrapper for consuming dashboard's splitz service. On usage, you'll have `splitz` as a prop. Please make sure you use this only for class based components.
 *
 * For functional components use `useSplitzService` hook instead.
 *
 */
export const withSplitzService =
  <
    T extends {
      splitz: SpiltzContextState;
    },
  >(
    Component: ComponentType<T>,
  ) =>
  (props: T): JSX.Element => {
    const splitz = useSplitzService();
    return <Component {...props} splitz={splitz} />;
  };
