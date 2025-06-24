import React from 'react';
import type { RenderResult } from '@testing-library/react';
import { render as rTLRender } from '@testing-library/react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { MemoryRouter } from 'react-router-dom';

export const render = (ui: React.ReactElement, { route = '/' } = {}): RenderResult => {
  return rTLRender(
    <BladeProvider themeTokens={bladeTheme} colorScheme="light">
      <MemoryRouter initialEntries={[route]}>{ui}</MemoryRouter>
    </BladeProvider>,
  );
};
