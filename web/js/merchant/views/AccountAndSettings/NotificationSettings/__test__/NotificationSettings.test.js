import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import NotificationSettings from 'merchant/views/AccountAndSettings/NotificationSettings';
import { render, screen } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import rolesList from 'merchant/helpers/permissions/roles-list';

jest.mock('merchant/views/AccountAndSettings/styled', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/AccountAndSettings/styled'),
  StyledConfiguration: ({
    showEmailNotifications,
    showSmsNotifications,
    showWhatsappNotifications,
  }) => {
    return (
      <>
        {showEmailNotifications && <>Email Notifications</>}
        {showSmsNotifications && <>SMS Notifications</>}
        {showWhatsappNotifications && <>Whatsapp Notifications</>}
      </>
    );
  },
}));

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  isSmsNotificationEnabled: jest.fn(() => true),
  isWhatsappNotificationEnabled: jest.fn(() => true),
  isEmailNotificationEnabled: jest.fn(() => true),
}));

const renderApp = ({ pathname, user } = {}) => {
  return render(<NotificationSettings />, {
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          ...user,
          role: rolesList.ADMIN,
        },
        org: {},
      },
    },
    initialEntries: [pathname ?? ROUTES_INFO.EMAIL_NOTIFICATIONS],
  });
};

describe('NotificationSettings', () => {
  test('should render NotificationSettings', () => {
    conditionalUtils.isEmailNotificationEnabled.mockReturnValueOnce(true);
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    const emailLink = screen.getByRole('link', { name: 'Email' });
    expect(emailLink).toBeInTheDocument();
    expect(emailLink).toHaveAttribute('href', ROUTES_INFO.EMAIL_NOTIFICATIONS);
  });

  testBreadCrumb(renderApp, 'Email', ROUTES_INFO.EMAIL_NOTIFICATIONS);

  // TODO:
  test.skip.each([
    ['Email Notifications', ROUTES_INFO.EMAIL_NOTIFICATIONS],
    ['SMS Notifications', ROUTES_INFO.SMS_NOTIFICATIONS],
    ['Whatsapp Notifications', ROUTES_INFO.WHATSAPP_NOTIFICATIONS],
  ])('should render %s component for %s route', (componentText, route) => {
    renderApp();
    const routeComponent = screen.getByTestId(`${route.replace('/notification-settings/')}/*`);
    expect(routeComponent).toBeInTheDocument();
    expect(routeComponent).toHaveTextContent(componentText);
  });

  testConditionalLinks(renderApp, [
    ['SMS', 'isSmsNotificationEnabled', ROUTES_INFO.SMS_NOTIFICATIONS],
    ['WhatsApp', 'isWhatsappNotificationEnabled', ROUTES_INFO.WHATSAPP_NOTIFICATIONS],
    ['Email', 'isEmailNotificationEnabled', ROUTES_INFO.EMAIL_NOTIFICATIONS],
  ]);

  testRedirectionWhenAccountAndSettingsIsNotEnabled(renderApp, [
    ['/config', ROUTES_INFO.EMAIL_NOTIFICATIONS],
    ['/config', ROUTES_INFO.SMS_NOTIFICATIONS],
    ['/config', ROUTES_INFO.WHATSAPP_NOTIFICATIONS],
    ['/dashboard', 'some-route'],
  ]);
});
