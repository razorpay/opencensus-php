import React from 'react';
import { render as renderMain, screen } from 'test-utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { storeWithInitialState } from 'merchant/store';

import OnboardedMerchantTab from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/OnboardedMerchantTab';

const initState = {
  session: {
    user: {
      isC360OnboardingToBeResumed: false,
      isC360OnboardingCompleted: false,
    },
  },
};

const render = (ui, config = {}) => {
  return renderMain(ui, {
    reduxStore: storeWithInitialState({ ...initState, ...config }),
  });
};

describe('OnboardedMerchantTab', () => {
  const App = () => (
    <BladeProvider themeTokens={bladeTheme}>
      <OnboardedMerchantTab />
    </BladeProvider>
  );

  test('should render', () => {
    render(<App />);
    const title = screen.getByText(/Welcome to Checkout360/i);
    expect(title).toBeInTheDocument();
  });
});
