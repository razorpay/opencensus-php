import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'test-utils';
import { state } from './mocks/fixtures/Sidebar';
import SidebarV2 from 'merchant/components/SidebarV2';
import * as fetchNavigationItems from 'merchant/reducers/leftNav';
import * as devices from 'merchant/components/Home/data';
import { FALLBACK_PRODUCTS } from 'merchant/components/SidebarV2/utils/Fallback';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

describe('SidebarV2', () => {
  const fetchNavigationSpy = jest.spyOn(fetchNavigationItems, 'fetchLeftNavItems');
  window.open = jest.fn();

  const renderApp = ({ initialState = state, props } = {}) =>
    render(<SidebarV2 {...props} />, {
      initialState,
    });

  beforeEach(() => {
    fetchNavigationSpy.mockClear();
  });

  test('should call fetch items on mount', async () => {
    renderApp();
    await waitFor(() => {
      expect(fetchNavigationSpy).toHaveBeenCalledTimes(1);
    });
  });
  describe('SidebarItems', () => {
    test('should render razorpay logo', async () => {
      const logoUrl = 'https://cdn.razorpay.com/logo_invert.svg';
      renderApp({
        logoUrl,
      });
      await waitFor(() => {
        expect(screen.getByRole('img')).toBeInTheDocument();
      });
      expect(screen.getByRole('img')).toHaveAttribute('src', logoUrl);
    });
    test('should render activation progress when hide activation form disabled', async () => {
      renderApp({
        initialState: {
          ...state,
          session: {
            user: {
              isOnboardingV2Enabled: true,
              isAllowedView: () => true,
            },
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('Activation Progress Bar')).toBeInTheDocument();
      });
    });

    test('should redirect merchant to onboarding steps on click when v2 onboarding enabled and mobile device', async () => {
      jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => true);
      const { history } = renderApp({
        initialState: {
          ...state,
          session: {
            user: {
              isOnboardingV2Enabled: true,
              isAllowedView: () => true,
            },
          },
          leftNav: {
            loading: true,
            error: null,
            data: [],
          },
          app: {
            isMobileResolution: true,
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('Activation Progress Bar')).toBeInTheDocument();
      });
      const activationBtn = screen.getByRole('button', {
        name: 'Click Activation',
      });
      await userEvent.click(activationBtn);
      expect(history.location.pathname).toEqual('/onboarding/steps');
    });

    test('should redirect merchant to kyc on click when activation full view enabled', async () => {
      const { history } = renderApp({
        initialState: {
          ...state,
          session: {
            user: {
              isActivationFormFullView: true,
              isAllowedView: () => true,
            },
          },
          leftNav: {
            loading: false,
            error: null,
            data: [...FALLBACK_PRODUCTS],
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('Activation Progress Bar')).toBeInTheDocument();
      });
      const activationBtn = screen.getByRole('button', {
        name: 'Click Activation',
      });
      await userEvent.click(activationBtn);
      expect(history.location.pathname).toEqual('/kyc');
    });

    test('should redirect merchant to activation on click when activation full view disabled', async () => {
      const { history } = renderApp({
        initialState: {
          ...state,
          session: {
            user: {
              isActivationFormFullView: false,
              isAllowedView: () => true,
            },
          },
          leftNav: {
            loading: false,
            error: 'Error',
            data: [],
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('Activation Progress Bar')).toBeInTheDocument();
      });
      const activationBtn = screen.getByRole('button', {
        name: 'Click Activation',
      });
      await userEvent.click(activationBtn);
      expect(history.location.pathname).toEqual('/activation');
    });

    test('should redirect merchant to easy-dashboard if merchant signup via easy_onboarding', async () => {
      renderApp({
        initialState: {
          ...state,
          session: {
            user: {
              isActivationFormFullView: false,
              isAllowedView: () => true,
              user: {
                signup_campaign: EASY_ONBOARDING,
              },
            },
          },
          leftNav: {
            loading: false,
            error: 'Error',
            data: [],
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('Activation Progress Bar')).toBeInTheDocument();
      });
      const activationBtn = screen.getByRole('button', {
        name: 'Click Activation',
      });
      await userEvent.click(activationBtn);
      await new Promise((r) => setTimeout(r, 1000));
      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith(window.EASY_ONBOARDING_URL, '_self', 'noopener');
      });
    });
  });
});
