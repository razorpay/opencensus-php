import * as React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { bladeTheme } from '@razorpay/blade/tokens';

import App from '../../app';

export const queryClient = new QueryClient();

const Wrapper = (): JSX.Element => {
  React.useEffect(() => {
    console.log('Injected Manifest!');
    const link = document.createElement('link');
    link.rel = 'manifest';
    link.href = `${process.env.UNIVERSE_PUBLIC_ASSETS_URL}/build/browser/manifest.json`;
    document.head.appendChild(link);
    return () => {
      document.head.removeChild(link);
    };
  }, []);
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <QueryClientProvider client={queryClient}>
        <App />
      </QueryClientProvider>
    </BladeProvider>
  );
};

export default Wrapper;
