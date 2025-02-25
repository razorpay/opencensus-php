import React from 'react';
import { useStore } from '@federated/apps/shell/commonStore';

import { ShowWhenComponent, RouteGuardComponent } from './RouteGuard';

const zustandConnect = (selector) => (WrappedComponent) => (props) => {
  const selectedProps = selector(useStore.getState());
  return <WrappedComponent {...selectedProps} {...props} />;
};

export const RouteGuard = zustandConnect(({ session }) => ({ session }))(RouteGuardComponent);

const ShowWhen = zustandConnect(({ session }) => ({ session }))(ShowWhenComponent);
export default ShowWhen;
