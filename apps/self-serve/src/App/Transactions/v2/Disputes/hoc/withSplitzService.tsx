import React, { ComponentType, useContext } from 'react';
import { SpiltzContextState } from '@dashboard/shared-utils/splitz/types';
import { SpiltzContext } from 'shell/SpiltzServiceContext';

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
