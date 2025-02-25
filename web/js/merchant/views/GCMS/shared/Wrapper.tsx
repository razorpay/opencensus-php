import React, { ReactNode } from 'react';
import { connect } from 'react-redux';
import { Navigate } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';

import { GCMSSession, SessionContext } from './context';
import { isGCMSExperimentEnabled } from './utils';
import {
  graphqlRequestQuery,
  graphqlRequestMutation,
} from '@federated/apps/shell/graphql';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
  MutationFunction
} from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: graphqlRequestQuery,
    },
    mutations: {
      mutationFn: graphqlRequestMutation as MutationFunction<unknown, unknown>,
    },
  },
});

interface Props extends GCMSSession {
  children: ReactNode;
}
const GCMSWrapper: React.FC<Props> = ({ children, mode, merchantId }) => {
  const splitzService = useSplitzService();

  if (!isGCMSExperimentEnabled(splitzService)) {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <ReactQueryClientProvider client={queryClient}>
      <SessionContext.Provider value={{ mode, merchantId }}>
        <ErrorBoundary>{children}</ErrorBoundary>
      </SessionContext.Provider>
    </ReactQueryClientProvider>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(GCMSWrapper);
