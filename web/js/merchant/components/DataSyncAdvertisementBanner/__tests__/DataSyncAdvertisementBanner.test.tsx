import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen, fireEvent } from '@testing-library/react';

import { analyticsTrack } from 'common/utils/analytics';
import { getItem, setItem } from 'common/utils/localStorage';

import { DataSyncAdvertisementBanner } from '../index';

jest.mock('common/utils/localStorage', () => ({
  getItem: jest.fn(),
  setItem: jest.fn(),
}));
jest.mock('common/utils/analytics', () => ({
  analyticsTrack: jest.fn(),
}));

describe('DataSyncAdvertisementBanner', () => {
  const renderApp = () => {
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <DataSyncAdvertisementBanner />
      </BladeProvider>,
    );
  };

  test('renders banner if local storage value is falsy', () => {
    getItem.mockReturnValueOnce(undefined);
    renderApp();
    expect(screen.getByText(/Unlock Real-Time Data Access with/i)).toBeInTheDocument();
  });

  test('does not render banner if value set in local storage', () => {
    getItem.mockReturnValue('true');
    renderApp();
    expect(screen.queryByText(/Unlock Real-Time Data Access with/i)).not.toBeInTheDocument();
  });

  test('hide banner and set local storage on close', () => {
    getItem.mockReturnValue('false');
    renderApp();

    const closeButton = screen.getByRole('button', { name: /close/i });
    fireEvent.click(closeButton);

    expect(screen.queryByText(/Unlock Real-Time Data Access with/i)).not.toBeInTheDocument();
    expect(setItem).toHaveBeenCalledWith('hideDataSyncBanner', 'true');
  });

  test('track analytics when banner and button is clicked', () => {
    getItem.mockReturnValue('false');
    renderApp();

    const banner = screen.getByText(/Unlock Real-Time Data Access with/i);
    fireEvent.click(banner);
    expect(analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        actionName: 'Data Sync Advertisement Banner Clicked',
      }),
    );

    const getStartedButton = screen.getByRole('button', { name: /get started/i });
    fireEvent.click(getStartedButton);
    expect(analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        actionName: 'Get Started Button Clicked',
      }),
    );
  });
});
