import React from 'react';
import { screen } from '@testing-library/react';
import { ShellLayout } from '../ShellLayout';
import { render } from '@apps/shell/src/services/test/render';

// Mock the dependencies
jest.mock('../ThemeSwitcher', () => ({
  ThemeSwitcher: () => <div data-testid="theme-switcher">Theme Switcher</div>,
}));

jest.mock('../../Navigation', () => ({
  ConnectedNavigationContainer: ({ children }) => (
    <div data-testid="navigation-container">{children}</div>
  ),
}));

jest.mock('../../ShellNotificationsManager', () => ({
  ShellNotificationManager: () => <div data-testid="notification-manager">Notifications</div>,
}));

describe('ShellLayout', () => {
  test('renders correctly with children', () => {
    render(
      <ShellLayout>
        <div data-testid="test-child">Test Child</div>
      </ShellLayout>,
    );

    expect(screen.getByTestId('theme-switcher')).toBeInTheDocument();
    expect(screen.getByTestId('navigation-container')).toBeInTheDocument();
    expect(screen.getByTestId('notification-manager')).toBeInTheDocument();
    expect(screen.getByTestId('test-child')).toBeInTheDocument();
  });
});
