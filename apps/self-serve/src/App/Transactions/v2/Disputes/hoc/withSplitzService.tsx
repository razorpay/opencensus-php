import React, { ComponentType, useContext } from 'react';
import { SpiltzContextState, SpiltzContext } from '@federated/dashboards/payments/services/splitzService';

export default function withSplitzService<
  T extends {
    splitz: SpiltzContextState;
  },
>(Component: ComponentType<T>) {
  (props: T): JSX.Element => {
    const splitz = useContext(SpiltzContext);
    return <Component {...props} splitz={splitz} />;
  };
}
