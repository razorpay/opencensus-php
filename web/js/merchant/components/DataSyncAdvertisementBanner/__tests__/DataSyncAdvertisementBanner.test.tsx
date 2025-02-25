import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen, fireEvent } from '@testing-library/react';

import { analyticsTrack } from 'common/utils/analytics';
import * as LocalStorageUtils from 'common/utils/localStorage';

import { DataSyncAdvertisementBanner } from '../index';

const localStorageSetItemSpy = jest.spyOn(LocalStorageUtils, 'setItem');
const localStorageGetItemSpy = jest.spyOn(LocalStorageUtils, 'getItem');


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
    localStorageGetItemSpy.mockReturnValueOnce(undefined);
    renderApp();
    expect(screen.getByText(/Unlock Real-Time Data Access with/i)).toBeInTheDocument();
  });

  test('does not render banner if value set in local storage', () => {
    localStorageGetItemSpy.mockReturnValue('true');
    renderApp();
    expect(screen.queryByText(/Unlock Real-Time Data Access with/i)).not.toBeInTheDocument();
  });

  test('hide banner and set local storage on close', () => {
    localStorageGetItemSpy.mockReturnValue('false');
    renderApp();

    const closeButton = screen.getByRole('button', { name: /close/i });
    fireEvent.click(closeButton);

    expect(screen.queryByText(/Unlock Real-Time Data Access with/i)).not.toBeInTheDocument();
    expect(localStorageSetItemSpy).toHaveBeenCalledWith('hideDataSyncBanner', 'true');
  });

  test('track analytics when banner and button is clicked', () => {
    localStorageGetItemSpy.mockReturnValue('false');
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
