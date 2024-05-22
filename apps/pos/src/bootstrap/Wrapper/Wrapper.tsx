import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import App from '../../app';

const Wrapper = (): JSX.Element => {
  React.useEffect(() => {
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
      <App />
    </BladeProvider>
  );
};

export default Wrapper;
