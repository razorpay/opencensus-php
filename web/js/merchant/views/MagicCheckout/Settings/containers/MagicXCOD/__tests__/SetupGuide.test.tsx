import React from 'react';
import { render, screen } from 'test-utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { SetupGuide } from '../Components/SetupGuide';

describe('SetupGuide Component', () => {
  const App = () => (
    <BladeProvider themeTokens={bladeTheme}>
      <SetupGuide />
    </BladeProvider>
  );

  beforeEach(() => {
    render(<App />);
  });

  test('should render', () => {
    const title = screen.getByText(/setup guide/i);
    expect(title).toBeInTheDocument();
  });
});
