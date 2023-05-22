import '@testing-library/jest-dom/extend-expect';
import * as context from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { titleCase } from 'common/utils/rzp-utils';
import Profile from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile';
import * as modals from 'merchant_common/reducers/modals';
import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { getState } from './mocks/fixtures/Profile';

describe('Merchant Profile Section Version 2', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');

  const renderApp = ({ props = {}, initialState }) =>
    render(<Profile {...props} />, {
      initialState,
    });

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test('should render desktop profile view for merchants', async () => {
    const initialState = getState({
      userData: {
        isContactDetailsRevamp: true,
      },
    });
    renderApp({
      initialState,
    });
    await waitFor(() => {
      expect(screen.getByText('Your profile')).toBeInTheDocument();
    });
    expect(screen.getByText(titleCase(initialState.session.user.user.name))).toBeInTheDocument();
    expect(screen.getByText('Owner')).toBeInTheDocument();
    expect(screen.getByText(initialState.session.user.id)).toBeInTheDocument();
  });

  test('should render mobile profile view for merchants in case of mobile device', () => {
    const initialState = getState({
      userData: {
        display_name: '',
        user: {
          merchants: {
            ksbuindYYH: {},
            DrrhtsbYYH: {},
          },
          contact_mobile: '7798586889',
          signup_via_email: 1,
          name: 'Kamlesh J',
          email: '',
        },
        userRole: 'admin',
        isContactDetailsRevamp: true,
      },
      appConfig: {
        isMobileResolution: true,
      },
    });
    renderApp({
      initialState,
    });
    expect(screen.getByText(titleCase(initialState.session.user.user.name))).toBeInTheDocument();
    expect(screen.getByText('Admin')).toBeInTheDocument();
    expect(screen.getByText(initialState.session.user.id)).toBeInTheDocument();
  });

  test.each([
    { name: 'Login email', isHandler: false, type: 'update' },
    { name: 'Password', isHandler: false },
    { name: 'Phone number', isHandler: true },
    { name: 'Login email', isHandler: false, type: 'add' },
  ])('should call handle edit click on click of edit icon', async ({ name }) => {
    jest.spyOn(context, 'useTwoFactorVerificationContext').mockImplementation(() => {
      return {
        criticalFlow: ({ onUserTwoFaVerified, onFlowTermination }) => {
          onUserTwoFaVerified();
          onFlowTermination();
        },
      };
    });
    const initialState = getState({
      userData: {
        isContactDetailsRevamp: true,
      },
    });
    renderApp({
      initialState,
    });
    const editButton = screen.getByRole('button', { name });
    await userEvent.click(editButton);
    expect(modalsSpy).toHaveBeenCalledTimes(1);
  });
});
