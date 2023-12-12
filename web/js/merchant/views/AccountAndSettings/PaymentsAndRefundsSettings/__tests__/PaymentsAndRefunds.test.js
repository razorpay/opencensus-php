import React from 'react';
import PaymentsAndRefunds from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import { fetchFeatureByNameHandler } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/__tests__/mocks/handlers';
import { render, screen, server, delay, waitFor } from 'test-utils';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';

jest.mock('react-router-dom', () => ({
  __esModule: true,
  ...jest.requireActual('react-router-dom'),
  Route: ({ path, element }) => {
    return <div data-testid={path}>{element}</div>;
  },
  Routes: ({ children }) => children,
}));

jest.mock('common/ui/DashboardBanner', () => ({
  __esModule: true,
  default: () => <>Dashboard Banner</>,
}));

jest.mock('merchant/components/TestModeBanner', () => ({
  __esModule: true,
  default: () => <>Test Mode Banner</>,
}));

jest.mock('common/components/Breadcrumb', () => ({
  __esModule: true,
  default: () => <></>,
}));

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  shouldShowFeeBearerSelfServe: jest.fn(),
  isFailedPaymentRetryEnabled: jest.fn(),
  isPaymentCaptureAndRefundEnabled: jest.fn(),
  isReminderEnabled: jest.fn(),
  isCreditsEnabled: jest.fn(),
  isBalancesEnabled: jest.fn(),
  isWhatsAppAccountSetupEnabled: jest.fn(),
}));

jest.mock('merchant/views/Account/Balances', () => ({
  __esModule: true,
  default: () => <>Balances component</>,
}));

jest.mock('merchant/views/Account/Credits/List', () => ({
  __esModule: true,
  default: () => <>Credits component</>,
}));

jest.mock('merchant/views/Settings/Reminders', () => ({
  __esModule: true,
  default: () => <>Reminders component</>,
}));

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/TransactionLimits',
  () => ({
    __esModule: true,
    default: () => <>Transaction limits component</>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup',
  () => ({
    __esModule: true,
    default: () => <>Whatsapp Setup component</>,
  }),
);

jest.mock('merchant/views/Settings/Configuration/MissedOrderPaymentLink', () => ({
  __esModule: true,
  default: () => <>Missed order component</>,
}));

jest.mock('merchant/views/AccountAndSettings/styled', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/AccountAndSettings/styled'),
  StyledConfiguration: () => <>Component</>,
}));

const renderApp = async ({ pathname, user } = {}) => {
  const renderOutput = render(<PaymentsAndRefunds />, {
    initialEntries: [pathname ?? ROUTES_INFO.BALANCES],
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          id: 'K16F51VyNzg75l',
          ...user,
        },
      },
    },
  });
  await delay();
  return renderOutput;
};

describe('Payments And Refunds', () => {
  beforeEach(() => {
    server.use(fetchFeatureByNameHandler('K16F51VyNzg75l', 'allow_cfb_international'));
  });

  test('should render Payments and refunds component', async () => {
    await renderApp();
    await waitFor(() => {
      expect(screen.queryByRole('loader')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    [
      'Balances',
      'Credits',
      'Reminders',
      'Fee bearer',
      'Capture and refund settings',
      'Failed payments recovery',
      'Whatsapp Account Setup',
    ].forEach((linkLabel) => {
      expect(screen.queryByRole('link', { name: linkLabel })).not.toBeInTheDocument();
    });
  });

  testBreadCrumb(renderApp, 'Balances', ROUTES_INFO.BALANCES, true);

  describe('Conditional link', () => {
    beforeAll(() => {
      conditionalUtils.isReminderEnabled.mockReturnValue(true);
      conditionalUtils.isCreditsEnabled.mockReturnValue(true);
      conditionalUtils.isBalancesEnabled.mockReturnValue(true);
      conditionalUtils.isWhatsAppAccountSetupEnabled.mockReturnValue(true);
    });
    afterAll(() => {
      conditionalUtils.isReminderEnabled.mockReturnValue(false);
      conditionalUtils.isCreditsEnabled.mockReturnValue(false);
      conditionalUtils.isBalancesEnabled.mockReturnValue(false);
      conditionalUtils.isWhatsAppAccountSetupEnabled.mockReturnValue(true);
    });
    testConditionalLinks(
      renderApp,
      [
        ['Balances', 'isBalancesEnabled', ROUTES_INFO.BALANCES],
        ['Credits', 'isCreditsEnabled', ROUTES_INFO.CREDITS],
        ['Reminders', 'isReminderEnabled', ROUTES_INFO.REMINDERS],
        [
          'Whatsapp Account Setup',
          'isWhatsAppAccountSetupEnabled',
          ROUTES_INFO.WHATSAPP_ACCOUNT_SETUP,
        ],
      ],
      false,
      true,
    );

    test('should use appropriate component for each route', async () => {
      await renderApp();
      await waitFor(() => {
        expect(screen.queryByRole('loader')).not.toBeInTheDocument();
      });
      [{ path: ROUTES_INFO.BALANCES, component: 'Balances component' }].forEach(
        ({ path, component }) => {
          expect(
            screen.getByTestId(`${path.replace('/payments-and-refunds-settings/', '')}/*`),
          ).toHaveTextContent(component);
        },
      );
    });

    test('should render Balances component', async () => {
      await renderApp();
      await waitFor(() => {
        expect(screen.queryByRole('loader')).not.toBeInTheDocument();
      });
      expect(screen.getByText('Balances')).toBeInTheDocument();
    });
  });

  describe('When is Account And Settings Revamp is not enabled', () => {
    testRedirectionWhenAccountAndSettingsIsNotEnabled(
      renderApp,
      [
        ['/addfunds', ROUTES_INFO.BALANCES],
        ['/credits', ROUTES_INFO.CREDITS],
        ['/reminders', ROUTES_INFO.REMINDERS],
      ],
      true,
    );
  });
});
