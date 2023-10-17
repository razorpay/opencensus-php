import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import InternationalSettings from 'merchant/views/AccountAndSettings/InternationalSettings/InternationalSettings';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  shouldShowFIRCSection: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  __esModule: true,
  ...jest.requireActual('react-router-dom'),
  Route: ({ path, element }) => {
    return <div data-testid={path}>{element}</div>;
  },
  Routes: ({ children }) => children,
}));

jest.mock('merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/FIRS', () => ({
  __esModule: true,
  default: () => <>Foreign inward remittance statement</>,
}));

jest.mock('merchant/views/Account/Profile/components/FIRC/FIRCSection', () => ({
  __esModule: true,
  default: () => <>International payments codes</>,
}));

const renderApp = ({ pathname, user } = {}) => {
  return render(<InternationalSettings location={{ pathname: pathname ?? ROUTES_INFO.FIRS }} />, {
    initialEntries: [pathname ?? ROUTES_INFO.FIRS],
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
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
    ['Foreign inward remittance statement (FIRS)', 'International payments codes'].forEach(
      (linkLabel) => {
        expect(screen.queryByRole('link', { name: linkLabel })).not.toBeInTheDocument();
      },
    );
  });

  testBreadCrumb(renderApp, 'Forward inwards remittance statement', ROUTES_INFO.FIRS);

  describe('Conditional links', () => {
    beforeAll(() => {
      conditionalUtils.shouldShowFIRCSection.mockReturnValue(true);
    });

    afterAll(() => {
      conditionalUtils.shouldShowFIRCSection.mockReturnValue(false);
    });

    testConditionalLinks(renderApp, [
      ['Foreign inward remittance statement', 'shouldShowFIRCSection', ROUTES_INFO.FIRS],
      [
        'International payments codes',
        'shouldShowFIRCSection',
        ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES,
      ],
    ]);

    test.each([
      ['Foreign inward remittance statement', ROUTES_INFO.FIRS],
      ['International payments codes', ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES],
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
      ['/dashboard', '/international-settings/some-route'],
    ]);
  });
});
