import React from 'react';
import WebsiteAndAppSettings from 'merchant/views/AccountAndSettings/WebsiteAppSettings';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import {
  fetchMerchantWebsiteDetailsHandler,
  fetchConnectedApplicationsHandler,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/__tests__/mocks/handlers';
import { render, screen, server, delay, waitFor } from 'test-utils';
import { newAndOldRouteMap } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/constants/constants';
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
  isWebsiteDetailsEnabled: jest.fn(),
  isApiKeyEnabled: jest.fn(),
  isWebhookEnabled: jest.fn(),
  isApplicationEnabled: jest.fn(),
}));

jest.mock('merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/ApiKeys', () => ({
  __esModule: true,
  default: () => <>Api Keys</>,
}));

jest.mock('merchant/views/Account/WebsiteAppDetails', () => ({
  __esModule: true,
  default: () => <>Website App details</>,
}));

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails',
  () => ({
    __esModule: true,
    default: () => <>Business website details</>,
  }),
);

jest.mock('merchant/views/Settings/Webhooks/List', () => ({
  __esModule: true,
  default: () => <>Webhooks component</>,
}));

jest.mock('merchant/views/Settings/Applications', () => ({
  __esModule: true,
  default: () => <>Applications component</>,
}));

const renderApp = async ({ pathname, user } = {}) => {
  const renderOutput = render(<WebsiteAndAppSettings />, {
    initialEntries: [pathname ?? ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS],
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          isWebsiteComplianceFlowEnabled: true,
          id: 'K16F51VyNzg75l',
          ...user,
        },
      },
    },
  });
  await delay();
  return renderOutput;
};

describe('Website And Appp Settings', () => {
  beforeEach(() => {
    server.use(fetchMerchantWebsiteDetailsHandler());
  });

  test('should render Website&App settings component', async () => {
    await renderApp();
    await waitFor(() => {
      // Since components are lazy loaded
      expect(screen.queryByRole('loader')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    const businessDetailsLink = screen.getByRole('link', { name: 'Business website details' });
    expect(businessDetailsLink).toBeInTheDocument();
    expect(businessDetailsLink).toHaveAttribute('href', ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS);
    ['Webhooks', 'API keys', 'Website/App Detail', 'Applications'].forEach((linkLabel) => {
      expect(screen.queryByRole('link', { name: linkLabel })).not.toBeInTheDocument();
    });
  });

  testBreadCrumb(renderApp, 'Business website details', ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS);

  describe('Conditional link', () => {
    beforeAll(() => {
      conditionalUtils.isWebsiteDetailsEnabled.mockReturnValue(true);
      conditionalUtils.isApiKeyEnabled.mockReturnValue(true);
      conditionalUtils.isWebhookEnabled.mockReturnValue(true);
      conditionalUtils.isApplicationEnabled.mockReturnValue(true);
    });
    afterAll(() => {
      conditionalUtils.isWebsiteDetailsEnabled.mockReturnValue(false);
      conditionalUtils.isApiKeyEnabled.mockReturnValue(false);
      conditionalUtils.isWebhookEnabled.mockReturnValue(false);
      conditionalUtils.isApplicationEnabled.mockReturnValue(false);
    });
    testConditionalLinks(renderApp, [
      ['Webhooks', 'isWebhookEnabled', ROUTES_INFO.WEBHOOKS],
      ['API keys', 'isApiKeyEnabled', ROUTES_INFO.API_KEYS],
      ['API keys', 'isApiKeyEnabled', ROUTES_INFO.API_KEYS],
      ['Applications', 'isApplicationEnabled', ROUTES_INFO.APPLICATIONS],
    ]);

    test('should use appropriate component for each route', async () => {
      renderApp();
      await waitFor(() => {
        // Since components are lazy loaded
        expect(screen.queryByRole('loader')).not.toBeInTheDocument();
      });
      [{ path: ROUTES_INFO.API_KEYS, component: 'Api Keys' }].forEach(({ path, component }) => {
        expect(
          screen.getByTestId(`${path.replace('/website-app-settings/', '')}/*`),
        ).toHaveTextContent(component);
      });
    });
    test('should render Webhooks component', async () => {
      await renderApp();
      await waitFor(() => {
        // Since components are lazy loaded
        expect(screen.queryByRole('loader')).not.toBeInTheDocument();
      });
      expect(screen.getByText('Webhooks')).toBeInTheDocument();
    });

    test('should render applications when connected api response is success', async () => {
      server.use(fetchConnectedApplicationsHandler());
      await renderApp();
      await waitFor(() => {
        // Since components are lazy loaded
        expect(screen.queryByRole('loader')).not.toBeInTheDocument();
      });
      expect(screen.queryByText('Applications')).toBeInTheDocument();
    });
  });

  describe('When is Account And Settings Revamp is not enabled', () => {
    testRedirectionWhenAccountAndSettingsIsNotEnabled(
      renderApp,
      [
        [newAndOldRouteMap[ROUTES_INFO.WEBSITE_APP_SETTINGS], ROUTES_INFO.WEBSITE_APP_SETTINGS],
        [newAndOldRouteMap[ROUTES_INFO.WEBHOOKS], ROUTES_INFO.WEBHOOKS],
        [newAndOldRouteMap[ROUTES_INFO.API_KEYS], ROUTES_INFO.API_KEYS],
      ],
      true,
    );
  });
});
