import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import CheckoutSettings from 'merchant/views/AccountAndSettings/CheckoutSettings/CheckoutSettings';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { render, screen, waitFor } from 'test-utils';

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  isConfigurationViewAllowed: jest.fn(),
  isFlashCheckoutAllowed: jest.fn(),
  isSkipMandatorySummaryPageAllowed: jest.fn(),
  isTrustedBadgeAllowed: jest.fn(),
}));

jest.mock('merchant/views/AccountAndSettings/styled', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/AccountAndSettings/styled'),
  StyledConfiguration: ({ showBranding, showFlashCheckout, showSkipMandatorySummaryPage }) => {
    return (
      <div>
        {showBranding && <>Branding</>}
        {showFlashCheckout && <>Flash Checkout</>}
        {showSkipMandatorySummaryPage && <>Mandate Summary Page</>}
      </div>
    );
  },
}));

jest.mock('merchant/views/Account/TrustedBadge', () => {
  return {
    __esModule: true,
    default: () => <div>Trusted Badge Component</div>,
  };
});

const renderApp = ({ pathname, user } = {}) => {
  return render(<CheckoutSettings />, {
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          ...user,
        },
        org: {},
      },
    },
    renderViaRouteGuard: false,
    initialEntries: [pathname ?? ROUTES_INFO.BRANDING],
  });
};

describe('Checkout Settings', () => {
  test('should render checkout settings', () => {
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    ['Branding', 'Flash Checkout', 'Mandate Summary Page', 'Trusted Badge'].forEach((linkLabel) => {
      expect(screen.queryByRole('link', { name: linkLabel })).not.toBeInTheDocument();
    });
  });

  testBreadCrumb(renderApp, 'Branding', ROUTES_INFO.BRANDING);

  describe('Conditional links', () => {
    beforeAll(() => {
      conditionalUtils.isConfigurationViewAllowed.mockReturnValue(true);
    });

    afterAll(() => {
      conditionalUtils.isConfigurationViewAllowed.mockReturnValue(false);
    });

    testConditionalLinks(renderApp, [
      ['Branding', 'isConfigurationViewAllowed', ROUTES_INFO.BRANDING],
      ['Flash Checkout', 'isFlashCheckoutAllowed', ROUTES_INFO.FLASH_CHECKOUT],
      [
        'Mandate Summary Page',
        'isSkipMandatorySummaryPageAllowed',
        ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE,
      ],
      ['Trusted Badge', 'isTrustedBadgeAllowed', ROUTES_INFO.TRUSTED_BADGE],
    ]);

    test.each([
      ['Branding', ROUTES_INFO.BRANDING],
      ['Flash Checkout', ROUTES_INFO.FLASH_CHECKOUT],
      ['Mandate Summary Page', ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE],
    ])('should render %s component for %s route', (componentText, route) => {
      renderApp();
      const routeComponent = screen.getByTestId(`${route.replace('/checkout-settings/', '')}/*`);
      expect(routeComponent).toBeInTheDocument();
      expect(routeComponent).toHaveTextContent(componentText);
    });

    test('should render trustedBadge component isTrustedBadgeAllowed', async () => {
      conditionalUtils.isTrustedBadgeAllowed.mockReturnValue(true);
      renderApp();
      await waitFor(() => expect(screen.getByText('Trusted Badge Component')).toBeInTheDocument());
    });
  });

  describe('When is Account And Settings Revamp is not enabled', () => {
    testRedirectionWhenAccountAndSettingsIsNotEnabled(renderApp, [
      ['/config', ROUTES_INFO.BRANDING],
      ['/config', ROUTES_INFO.FLASH_CHECKOUT],
      ['/config', ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE],
      ['/trustedbadge', ROUTES_INFO.TRUSTED_BADGE],
      ['/dashboard', 'some-route'],
    ]);
  });
});
