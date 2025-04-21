import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { QueryClient, QueryClientProvider, MutationFunction } from '@tanstack/react-query';

import {
  graphqlClient,
  graphqlRequestQuery,
  graphqlRequestMutation,
} from '@federated/apps/shell/graphql';
import { APP_CONSTANTS } from 'apps/onboarding-experience/src/constants';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // @ts-ignore
      queryFn: graphqlRequestQuery,
    },
    mutations: {
      mutationFn: graphqlRequestMutation as MutationFunction<unknown, unknown>,
    },
  },
});

// set 'apollographql-client-name' header for Apollo studio to automatically categorize the requests
graphqlClient.setHeader('apollographql-client-name', APP_CONSTANTS.APP_NAME);

export const Wrapper = ({ children }: { children: any }): React.ReactElement => {
  return (
    <ErrorBoundary
      rank={errorService.ErrorRank.P0}
      tags={{ module: APP_CONSTANTS.APP_NAME }}
      fallbackComponent={<>Oops, Something went wrong!</>}
    >
      <QueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={bladeTheme} colorScheme="light">
          {children}
        </BladeProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
};
