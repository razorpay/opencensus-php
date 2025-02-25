import React from 'react';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { QueryClientProvider } from '@tanstack/react-query';
import { ErrorBoundary } from '@libs/shared-ui';
import { BrowserRouter } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import { ShellCommonProvider } from '@apps/shell/src/shared/contexts/ShellCommonProvider';
import { STAGE } from '@apps/shell/src/env';

export const ShellBrowserProvider = ({ children, queryClient }) => {
  return (
    <ErrorBoundary>
      <HelmetProvider>
        <QueryClientProvider client={queryClient}>
          <BrowserRouter basename="/app">
            <ShellCommonProvider>{children}</ShellCommonProvider>
          </BrowserRouter>
          {(STAGE === 'development' || STAGE === 'devstack') && (
            <ReactQueryDevtools initialIsOpen={false} />
          )}
        </QueryClientProvider>
      </HelmetProvider>
    </ErrorBoundary>
  );
};
