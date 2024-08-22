import React from 'react';
import type { RenderResult } from '@testing-library/react';
import { render } from '@testing-library/react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { BrowserRouter } from 'react-router-dom';

const renderWithWrappers = (ui: React.ReactElement, { route = '/' } = {}): RenderResult => {
  window.history.pushState({}, 'Test page', route);

  return render(
    <BladeProvider themeTokens={bladeTheme} colorScheme="light">
      {ui}
    </BladeProvider>,
    { wrapper: BrowserRouter },
  );
};

export default renderWithWrappers;
