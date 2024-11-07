import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter, useNavigate } from 'react-router-dom';

import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { WhatsNewCard } from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew/Components/Card';

import { newOffering } from './mocks/NewOffering';

type ReactRouterDom = {
  useNavigate: () => jest.Mock;
};

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as ReactRouterDom),
  useNavigate: jest.fn(),
}));

describe('whatsnew card', () => {
  test('should render WhatsNewCard with item data', () => {
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <BrowserRouter>
          <WhatsNewCard item={newOffering} isRCODEnabled={true} />
        </BrowserRouter>
      </BladeProvider>,
    );

    expect(screen.getByRole('heading')).toHaveTextContent('New Feature');
    expect(screen.getByText('This is a description of the new feature.')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /know more/i })).toHaveAttribute(
      'href',
      'https://example.com/know-more',
    );
    expect(screen.getByRole('link', { name: /documentation/i })).toHaveAttribute(
      'href',
      'https://example.com/doc-link',
    );
  });
});

describe('CheckItOut CTA Button', () => {
  const mockNavigate = jest.fn();

  beforeEach(() => {
    (jest.requireMock('react-router-dom').useNavigate as jest.Mock).mockReturnValue(mockNavigate);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });
  test('should call navigate with correct path when button is clicked', () => {
    const mockNavigate = useNavigate();
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <BrowserRouter>
          <WhatsNewCard item={newOffering} isRCODEnabled={false} />
        </BrowserRouter>
      </BladeProvider>,
    );

    const button = screen.getByRole('button', { name: /check it out/i });
    fireEvent.click(button);

    expect(mockNavigate).toHaveBeenCalledWith('/cta-link');
  });
});
