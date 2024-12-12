import InternationalSettings from 'merchant/views/AccountAndSettings/InternationalSettings/InternationalSettings';
import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  shouldShowFIRCSection: jest.fn(),
  isExporterRewardsEnabled: jest.fn(),
}));

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

jest.mock('merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/FIRS', () => ({
  __esModule: true,
  default: () => <>Foreign inward remittance statement</>,
}));

jest.mock('merchant/views/Account/Profile/components/FIRC/FIRCSection', () => ({
  __esModule: true,
  default: () => <>International payments codes</>,
}));

jest.mock('merchant/views/AccountAndSettings/InternationalSettings/ExporterRewards', () => ({
  __esModule: true,
  default: () => <>Exporter rewards</>,
}));

const isOrgAllowedFunctionality = jest.fn();

const renderApp = ({ pathname, user } = {}) => {
  return render(<InternationalSettings location={{ pathname: pathname ?? ROUTES_INFO.FIRS }} />, {
    initialEntries: [pathname ?? ROUTES_INFO.FIRS],
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          isOrgAllowedFunctionality,
          ...user,
        },
        org: {},
      },
    },
  });
};

describe('International Settings', () => {
  test('should render international settings', () => {
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    [
      'Foreign inward remittance statement (FIRS)',
      'International payments codes',
      'Exporter rewards',
    ].forEach((linkLabel) => {
      expect(screen.queryByRole('link', { name: linkLabel })).not.toBeInTheDocument();
    });
  });

  testBreadCrumb(renderApp, 'Forward inwards remittance statement', ROUTES_INFO.FIRS);

  describe('Conditional links', () => {
    beforeAll(() => {
      conditionalUtils.shouldShowFIRCSection.mockReturnValue(true);
      conditionalUtils.isExporterRewardsEnabled.mockReturnValue(true);
    });

    afterAll(() => {
      conditionalUtils.shouldShowFIRCSection.mockReturnValue(false);
      conditionalUtils.isExporterRewardsEnabled.mockReturnValue(false);
    });

    testConditionalLinks(renderApp, [
      ['Foreign inward remittance statement', 'shouldShowFIRCSection', ROUTES_INFO.FIRS],
      [
        'International payments codes',
        'shouldShowFIRCSection',
        ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES,
      ],
      ['Exporter rewards', 'isExporterRewardsEnabled', ROUTES_INFO.EXPORTER_REWARDS],
    ]);

    test.each([
      ['Foreign inward remittance statement', ROUTES_INFO.FIRS],
      ['International payments codes', ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES],
      ['Exporter rewards', ROUTES_INFO.EXPORTER_REWARDS],
    ])('should render %s component for %s route', (componentText, route) => {
      renderApp();
      const routeComponent = screen.getByTestId(
        `${route.replace('/international-settings/', '')}/*`,
      );
      expect(routeComponent).toBeInTheDocument();
      expect(routeComponent).toHaveTextContent(componentText);
    });
  });

  describe('When is Account And Settings Revamp is not enabled', () => {
    testRedirectionWhenAccountAndSettingsIsNotEnabled(renderApp, [
      ['/profile', ROUTES_INFO.FIRS],
      ['/profile', ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES],
      ['/profile', ROUTES_INFO.EXPORTER_REWARDS],
      ['/dashboard', '/international-settings/some-route'],
    ]);
  });
});
