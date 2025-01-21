import React from 'react';
import { BladeProvider, ToastContainer } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { QueryClient, QueryClientProvider, MutationFunction } from '@tanstack/react-query';

import {
  graphqlClient,
  graphqlRequestQuery,
  graphqlRequestMutation,
} from '@apps/digital-bills/src/utils/graphql';
import App from '@apps/digital-bills/src/app';
import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import { DIGITAL_BILLS, ERROR_PAGE_DESCRIPTION } from '@apps/digital-bills/src/utils/constants';

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

// set 'apollographql-client-name' header for Apollo studio to automatically categorize the requests
graphqlClient.setHeader(
  'apollographql-client-name',
  process.env.UNIVERSE_PUBLIC_APP_NAME as string,
);

const Wrapper = (): React.ReactElement => {
  return (
    <QueryClientProvider client={queryClient}>
      <BladeProvider themeTokens={bladeTheme} colorScheme="light">
        <ErrorBoundary
          rank={errorService.ErrorRank.P0}
          tags={{ module: DIGITAL_BILLS }}
          fallbackComponent={<ErrorPage description={ERROR_PAGE_DESCRIPTION} />}
        >
          <App />
        </ErrorBoundary>
        <ToastContainer />
      </BladeProvider>
    </QueryClientProvider>
  );
};

export default Wrapper;
