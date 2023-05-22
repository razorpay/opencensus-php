import ActivationDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/ActivationDetails';
import { render, screen, server, waitFor, userEvent } from 'test-utils';
import { adminAsMerchantHandler } from 'merchant/views/AccountAndSettings/BusinessSettings/__test__/fixtures/handlers';
import { analyticsTrack } from 'common/utils/analytics';
import { testNewStylesUsingFlowRevamped } from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';

jest.mock('common/ui/ProgressBar', () => ({
  ProgressBar: ({ value }) => <span>Progress Bar - {value}</span>,
}));

jest.mock('merchant/components/Home/data', () => ({
  isMobileDevice: jest.fn().mockReturnValueOnce(true),
}));

describe('ActivationDetails', () => {
  const renderApp = ({ userInfo, isFlowRevamped } = {}) => {
    return render(<ActivationDetails isFlowRevamped={isFlowRevamped} />, {
      initialState: {
        session: {
          user: {
            instantActivation: {
              isL1Submitted: false,
            },
            ...userInfo,
          },
        },
      },
    });
  };

  test('should show loading and account activated, account access, form status are hidden on mount', () => {
    renderApp();
    expect(screen.getByTestId('loader-dots')).toBeInTheDocument();
    expect(screen.getByText('Activation Form Status')).toBeInTheDocument();

    ['Account Activated On', 'Account Access'].forEach((text) => {
      expect(screen.queryByText(text)).not.toBeInTheDocument();
    });
  });

  test('should render account activated info', () => {
    renderApp({
      userInfo: {
        activated: true,
        activated_at: 1672531201,
      },
    });
    expect(screen.getByText('Account Activated On')).toBeInTheDocument();
    expect(screen.getByText('Jan 01 2023, 12:00 am')).toBeInTheDocument();
  });

  describe('Account Access Info when user is activated', () => {
    test('should render appropriate messages when key access is false', () => {
      renderApp({
        userInfo: {
          isActivated: true,
          has_key_access: false,
        },
      });
      expect(screen.getByText('Account Access')).toBeInTheDocument();
      expect(screen.getByText('Limited')).toBeInTheDocument();
      expect(
        screen.getByText(
          'You can only access Payment Links and Invoices. Please provide website/app link to get access to our API’s and other products such as Route, Subscriptions, etc.',
        ),
      ).toBeInTheDocument();
    });

    test('should render appropriate messages when key access is true', () => {
      renderApp({
        userInfo: {
          isActivated: true,
          has_key_access: true,
        },
      });
      expect(screen.getByText('Account Access')).toBeInTheDocument();
      expect(screen.getByText('Complete')).toBeInTheDocument();
      expect(
        screen.getByText(
          /You have access to all products and API keys. Integrate using our robust APIs or request access to products such as Subscriptions, Route, and Smart Collect/i,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('Activation Form Status when user has submitted L1 and instant activation is not shown', () => {
    test('should render activation status label', () => {
      renderApp({
        userInfo: {
          activation_status: 'activated',
          instantActivation: {
            isL1Submitted: true,
          },
          showInstantActivation: true,
        },
      });
      expect(screen.getByText('KYC Form Status')).toBeInTheDocument();
      expect(screen.getByText('Activated')).toBeInTheDocument();
    });

    test("should render progress text when activation_status doesn't exist ", () => {
      renderApp({
        userInfo: {
          instantActivation: {
            isL1Submitted: true,
          },
          activation_progress: 90,
          showInstantActivation: true,
        },
      });
      expect(screen.getByText('KYC Form Status')).toBeInTheDocument();
      expect(screen.getByText('90% Completed')).toBeInTheDocument();
      expect(screen.getByText('Progress Bar - 90')).toBeInTheDocument();
    });

    test('should be hidden when showInstantActivation is true and isL1Submitted is false', () => {
      renderApp({
        userInfo: {
          showInstantActivation: true,
        },
      });
      expect(screen.queryByText('KYC Form Status')).not.toBeInTheDocument();
      expect(screen.queryByText('Activation Form Status')).not.toBeInTheDocument();
    });
  });

  describe('Account Activation when user is admin', () => {
    beforeAll(() => {
      window.rzp_user = {
        verification: {
          status: 'activation',
        },
      };
    });

    beforeEach(() => {
      server.use(adminAsMerchantHandler);
    });

    afterAll(() => {
      window.rzp_user = undefined;
    });

    const renderAppWithAdminAsMerchant = async (userInfo) => {
      renderApp({ userInfo });
      await waitFor(() => {
        expect(screen.queryByTestId('loader-dots')).not.toBeInTheDocument();
      });
    };

    test('should use onboarding/steps as activation link if its a mobile device', async () => {
      await renderAppWithAdminAsMerchant();
      const activationLink = screen.getByRole('link', { name: 'Fill Activation Form' });
      // isMobileDevice returns true only once for the first time
      expect(activationLink).toHaveAttribute('href', '/onboarding/steps');
    });

    test('should render link to activation form', async () => {
      await renderAppWithAdminAsMerchant();
      const activationLink = screen.getByRole('link', { name: 'Fill Activation Form' });
      expect(activationLink).toBeInTheDocument();
      expect(activationLink).toHaveAttribute('href', '/activation');
      await userEvent.click(activationLink);
      expect(analyticsTrack).toHaveBeenCalledWith({
        objectName: 'view KYC form',
        actionName: 'clicked',
        screen: 'my account',
        properties: {
          status: window.rzp_user.verification.status,
        },
      });
    });

    test('should use kyc as activation link in activationFormFullView', async () => {
      await renderAppWithAdminAsMerchant({
        isActivationFormFullView: true,
      });
      const activationLink = screen.getByRole('link', { name: 'Fill Activation Form' });
      expect(activationLink).toHaveAttribute('href', '/kyc');
    });

    test('should render View activation form as link text when user is activated/submitted/locked', async () => {
      await renderAppWithAdminAsMerchant({
        activated: true,
      });
      const activationLink = screen.getByRole('link', { name: 'View Activation Form' });
      expect(activationLink).toBeInTheDocument();
    });

    test('should render Submit activation form as link text when user activation progress is 100 and user has not submitted', async () => {
      await renderAppWithAdminAsMerchant({
        submitted: false,
        activation_progress: 100,
      });
      const activationLink = screen.getByRole('link', { name: 'Submit Activation Form' });
      expect(activationLink).toBeInTheDocument();
    });
  });

  testNewStylesUsingFlowRevamped(renderApp, 'account-details-section');
});
