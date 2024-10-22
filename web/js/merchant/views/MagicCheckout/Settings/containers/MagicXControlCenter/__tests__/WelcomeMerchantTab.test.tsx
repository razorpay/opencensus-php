import React from 'react';
import { render as renderMain, screen } from 'test-utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { storeWithInitialState } from 'merchant/store';

import WelcomeMerchantTab from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/WelcomeMerchantTab';
import { C360_ONBOARDING_CTA } from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/constants';

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

describe('WelcomeMerchantTab', () => {
  const App = () => (
    <BladeProvider themeTokens={bladeTheme}>
      <WelcomeMerchantTab />
    </BladeProvider>
  );

  test('should render', () => {
    render(<App />);
    const title = screen.getByText(/Boost buyer intent, reduce fake orders/i);
    expect(title).toBeInTheDocument();
  });

  test('should render resume button when C360 onboarding is in progress', () => {
    const user = { ...initState.session.user, isC360OnboardingToBeResumed: true };
    render(<App />, { session: { user } });
    const resumeBtn = screen.getByText(C360_ONBOARDING_CTA.RESUME);
    expect(resumeBtn).toBeInTheDocument();
  });
});
