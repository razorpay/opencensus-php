import React from 'react';
import { render as renderMain, screen } from 'test-utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { storeWithInitialState } from 'merchant/store';

import ControlCenter from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter';

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
      <ControlCenter />
    </BladeProvider>
  );

  test('should render welcome screen when user has not completed C360 onboarding', () => {
    render(<App />);
    const title = screen.getByText(/Boost buyer intent, reduce fake orders/i);
    expect(title).toBeInTheDocument();
  });

  test('should render onboarded user screen when user has completed C360 onboarding', () => {
    render(<App />, { session: { user: { isC360OnboardingCompleted: true } } });
    const title = screen.getByText(/Welcome to Checkout360/i);
    expect(title).toBeInTheDocument();
  });
});
