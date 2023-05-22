import '@testing-library/jest-dom/extend-expect';
import {
  getState,
  state,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/__tests__/mocks/fixtures/verification';
import Verification from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/Verification';
import React from 'react';
import { render, screen } from 'test-utils';

describe('Verification', () => {
  const renderApp = ({ initialState = state, props = {} } = {}) =>
    render(<Verification {...props} />, {
      initialState,
    });

  test('should render two step verification heading', () => {
    const initialState = getState();
    renderApp({
      initialState,
    });
    const title = screen.getByText(/2-step verification/i);
    expect(title).toBeInTheDocument();
  });

  test('should render 2fa tooltip with description', () => {
    const initialState = getState();
    renderApp({
      props: {
        isMobile: true,
      },
      initialState,
    });
    const description =
      'Secure your account by using a one-time verification code each time you log in.';
    const tooltipDesc = screen.getByText(description);
    expect(tooltipDesc).toBeInTheDocument();
  });

  test('should render 2fa settings module', () => {
    const initialState = getState();
    renderApp({
      initialState,
    });
    expect(screen.getByText('User 2FA Settings Module')).toBeInTheDocument();
  });

  test('should not render anything if 2fa mofile signup disabled', () => {
    const initialState = getState({
      userData: {
        is2FAMobileSignupEnabled: false,
      },
    });
    renderApp({
      initialState,
    });
    expect(screen.queryByText(/2-step verification/i)).not.toBeInTheDocument();
  });
});
