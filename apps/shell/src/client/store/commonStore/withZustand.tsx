import React, { ComponentType } from 'react';

import { useStore } from './zustandStore';

import type { Store } from './types';

const withZustand =
  <P extends { store: Store }>(Component: ComponentType<P>, requiredKeys: string[] = []) =>
  (props: Omit<P, 'store'>) => {
    const store = useStore((state) => {
      const storeData = requiredKeys.reduce((accumulator, key) => {
        accumulator[key] = state[key] || {};
        return accumulator;
      }, {});
      return storeData;
    });

    return <Component {...(props as P)} store={store} />;
  };

export default withZustand;
