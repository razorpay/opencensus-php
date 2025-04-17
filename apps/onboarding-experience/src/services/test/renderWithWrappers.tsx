import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { BrowserRouter } from 'react-router-dom';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
} from '@tanstack/react-query';

import { render } from './jest-utils';

import type { RenderResult } from './jest-utils';

const renderWithWrappers = (ui: React.ReactElement, { route = '/' } = {}): RenderResult => {
  window.history.pushState({}, 'Test page', route);
  const queryClient = new QueryClient({});

  return render(
    <ReactQueryClientProvider client={queryClient}>
      <BladeProvider themeTokens={bladeTheme} colorScheme="light">
        {ui}
      </BladeProvider>
    </ReactQueryClientProvider>,
    { wrapper: BrowserRouter },
  );
};

export default renderWithWrappers;
