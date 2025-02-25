import React from 'react';
import { Hydrate, QueryClientProvider } from '@tanstack/react-query';
import { StaticRouter } from 'react-router-dom/server';
import { HelmetProvider } from 'react-helmet-async';
import { ErrorBoundary } from '@libs/shared-ui';
import { ShellCommonProvider } from '@apps/shell/src/shared/contexts/ShellCommonProvider';

export const ShellServerProvider = ({
  helmetContext,
  queryClient,
  dehydratedState,
  requestURL,
  children,
}) => {
  return (
    <ErrorBoundary>
      <HelmetProvider context={helmetContext}>
        <QueryClientProvider client={queryClient}>
          <Hydrate state={dehydratedState}>
            <StaticRouter location={requestURL}>
              <ShellCommonProvider>{children}</ShellCommonProvider>
            </StaticRouter>
          </Hydrate>
        </QueryClientProvider>
      </HelmetProvider>
    </ErrorBoundary>
  );
};
