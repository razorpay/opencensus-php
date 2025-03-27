import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import * as devices from 'merchant/components/Home/data';
import SidebarV2 from 'merchant/components/SidebarV2';
import { FALLBACK_PRODUCTS } from 'merchant/components/SidebarV2/utils/Fallback';
import * as SidebarUtils from 'merchant/components/SidebarV2/utils/Sidebar';
import * as fetchNavigationItems from 'merchant/reducers/leftNav';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { render, screen, waitFor, userEvent, within } from 'test-utils';

import { navigationApi, state } from './mocks/fixtures/Sidebar';
import { RZP_LOGO_URL_DARK } from '../constants/constants';
import * as showUtils from 'merchant/components/ShowWhen';
import * as rtuxUtils from 'merchant/containers/Home/RTUX/utils';

jest.mock(
  'merchant/components/SidebarV2/components/ActivationProgress',
  () =>
    ({ onSidebarActivationClick }) =>
      (
        <>
          <div>Activation Progress Bar</div>
          <button onClick={onSidebarActivationClick}>Click Activation</button>
        </>
      ),
);

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

const renderApp = ({ initialState = state, props } = {}) =>
  render(<SidebarV2 {...props} />, {
    initialState,
  });

describe('SidebarV2', () => {
  const fetchNavigationSpy = jest.spyOn(fetchNavigationItems, 'fetchLeftNavItems');
  const fetchNavItemsCacheSpy = jest.spyOn(SidebarUtils, 'getLeftNavItemsCache');
  window.open = jest.fn();

  beforeEach(() => {
    fetchNavigationSpy.mockClear();
    fetchNavItemsCacheSpy.mockClear();
    window.rzp_user = {};
    window.EASY_ONBOARDING_URL = 'EASY_ONBOARDING_URL';
  });

  test('should call fetch items on mount', async () => {
    fetchNavItemsCacheSpy.mockReturnValue(null);
    renderApp({
      initialState: {
        session: {
          user: {
            isAllowedView: () => true,
            isAllowedMultiple: () => true,
            findTag: () => false,
          },
        },
      },
    });
    await waitFor(() => {
      expect(fetchNavigationSpy).toHaveBeenCalledTimes(1);
    });
  });
  describe('SidebarItems', () => {
    test('should render razorpay logo', async () => {
      const logoUrl = 'https://cdn.razorpay.com/logo_invert.svg';
      renderApp({
        logoUrl,
        initialState: {
          session: {
            user: {
              isAllowedView: () => true,
              isAllowedMultiple: () => true,
              findTag: () => false,
            },
          },
        },
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
              ...state.session.user,
              isOnboardingV2Enabled: true,
              isAllowedView: () => true,
              isAllowedMultiple: () => true,
              findTag: () => false,
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
              ...state.session.user,
              isOnboardingV2Enabled: true,
              isAllowedView: () => true,
              isAllowedMultiple: () => true,
              findTag: () => false,
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
              ...state.session.user,
              isActivationFormFullView: true,
              isAllowedView: () => true,
              isAllowedMultiple: () => true,
              findTag: () => false,
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
              ...state.session.user,
              isActivationFormFullView: false,
              isAllowedView: () => true,
              isAllowedMultiple: () => true,
              findTag: () => false,
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
              ...state.session.user,
              isActivationFormFullView: false,
              isAllowedMultiple: () => true,
              isAllowedView: () => true,
              findTag: () => false,
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

    test('should redirect merchant to phantom NC flow if merchant signed up via phantom_onboarding', async () => {
      const signup_campaign = 'phantom_onboarding';
      window.rzp_user = {
        user: {
          signup_campaign,
        },
      };
      renderApp({
        initialState: {
          ...state,
          session: {
            user: {
              ...state.session.user,
              activation_status: 'needs_clarification',
              isActivationFormFullView: false,
              isAllowedMultiple: () => true,
              isAllowedView: () => true,
              findTag: () => false,
              user: {
                signup_campaign,
              },
            },
          },
          leftNav: {
            loading: false,
            error: 'Error',
            data: [],
          },
          home: {
            instantActivations: {
              showAcceptPayments: false,
            },
            isNcEligibile: true,
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
      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith(
          `${window.EASY_ONBOARDING_URL}/sub-merchant/onboarding/needs-clarification`,
          '_self',
          'noopener',
        );
      });
    });
  });
});

describe('SidebarV2 -> Blade designs', () => {
  beforeEach(() => {
    jest.spyOn(showUtils, 'showWhenUtil').mockImplementation(() => true);
  });

  test('should render razorpay logo', async () => {
    jest.spyOn(rtuxUtils, 'useIsRTUXHomepageEnabled').mockReturnValue(true);
    renderApp({
      initialState: {
        session: {
          user: {
            isAllowedView: () => true,
            isAllowedMultiple: () => true,
            findTag: () => false,
          },
        },
      },
    });
    await waitFor(() => {
      expect(screen.getByRole('img')).toBeInTheDocument();
    });
    expect(screen.getByRole('img')).toHaveAttribute('src', RZP_LOGO_URL_DARK);
  });

  test('should open/collapse sidebar items on click', async () => {
    renderApp({
      initialState: {
        session: {
          user: {
            isAllowedView: () => true,
            isAllowedMultiple: () => true,
            findTag: () => false,
            isConfigTagEnabled: () => true,
            isFeatureEnabled: () => false,
          },
          isTagsLoaded: true,
        },
      },
    });

    await waitFor(() => {
      expect(screen.queryByTestId('shimmer-group')).not.toBeInTheDocument();
    });

    const firstNavLinkProduct = screen.getAllByTestId('navlink-product')[0];
    expect(firstNavLinkProduct).toBeVisible();

    expect(screen.getByText(navigationApi.sections[0].section_name)).toBeInTheDocument();
    expect(
      within(firstNavLinkProduct).getByText(navigationApi.sections[0].product_options[0].title),
    ).toBeInTheDocument();
    expect(
      within(firstNavLinkProduct).queryByText(navigationApi.sections[0].product_options[5].title),
    ).not.toBeInTheDocument();

    const showMoreRegex = new RegExp('show all', 'i');
    const showLessRegex = new RegExp('show less', 'i');
    const showAllButton = within(firstNavLinkProduct).getByText(showMoreRegex);
    expect(showAllButton).toBeVisible();

    await userEvent.click(showAllButton);

    await waitFor(() => {
      expect(within(firstNavLinkProduct).queryByText(showMoreRegex)).not.toBeInTheDocument();
    });
    expect(within(firstNavLinkProduct).getByText(showLessRegex)).toBeVisible();
  });
});
