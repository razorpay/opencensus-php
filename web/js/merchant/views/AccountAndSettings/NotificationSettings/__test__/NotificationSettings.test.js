import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import NotificationSettings from 'merchant/views/AccountAndSettings/NotificationSettings';
import { render, screen } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';

jest.mock('merchant/views/AccountAndSettings/styled', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/AccountAndSettings/styled'),
  StyledConfiguration: ({
    showEmailNotifications,
    showSmsNotifications,
    showWhatsappNotifications,
    path,
  }) => {
    return (
      <div data-testid={`styled-${path}`}>
        {showEmailNotifications && <>Email Notifications</>}
        {showSmsNotifications && <>SMS Notifications</>}
        {showWhatsappNotifications && <>Whatsapp Notifications</>}
      </div>
    );
  },
}));

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  isSmsNotificationEnabled: jest.fn(),
  isWhatsappNotificationEnabled: jest.fn(),
  isEmailNotificationEnabled: jest.fn(),
}));

const renderApp = ({ pathname, user } = {}) => {
  return render(
    <NotificationSettings location={{ pathname: pathname ?? ROUTES_INFO.EMAIL_NOTIFICATIONS }} />,
    {
      initialState: {
        session: {
          user: {
            isAccountAndSettingsRevampEnabled: true,
            ...user,
          },
          org: {},
        },
      },
    },
  );
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

  test.each([
    ['Email Notifications', ROUTES_INFO.EMAIL_NOTIFICATIONS],
    ['SMS Notifications', ROUTES_INFO.SMS_NOTIFICATIONS],
    ['Whatsapp Notifications', ROUTES_INFO.WHATSAPP_NOTIFICATIONS],
  ])('should render %s component for %s route', (componentText, route) => {
    renderApp();
    const routeComponent = screen.getByTestId(`styled-${route}`);
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
