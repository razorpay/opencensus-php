import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import App from '../../app';

const Wrapper = (): React.ReactElement => {
  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="light">
      <App />
    </BladeProvider>
  );
};

export default Wrapper;
