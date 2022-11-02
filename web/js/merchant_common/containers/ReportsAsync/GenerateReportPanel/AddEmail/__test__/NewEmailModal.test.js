import { render, screen, waitFor } from 'common/services/test/test-utils';
import NewEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/NewEmailModal';
import * as analytics from 'common/utils/analytics';
import userEvent from '@testing-library/user-event';

describe('New Email Modal', () => {
  const defaultProps = {
    screen: 'test',
    onSubmit: jest.fn(),
    otpAuthToken: 'otpAuthToken',
    showNotification: false,
    closeModal: jest.fn(),
    onClose: jest.fn(),
  };
  const App = (props) => <NewEmailModal {...defaultProps} {...props} />;

  const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

  const typeEmailAndSubmit = async (email) => {
    render(<App />);
    const inputEmailElement = screen.getByRole('textbox', { name: 'Email Address' });
    if (email) await userEvent.type(inputEmailElement, email);
    const addEmailButton = screen.getByRole('button', { name: 'Add Email' });
    expect(addEmailButton).toBeEnabled();
    await userEvent.click(addEmailButton);
  };

  test('should show New Email Modal', () => {
    render(<App />);
    expect(screen.getByText(/add your email address/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /We will use this to send important updates and alerts. You can also use your email to recover your account in case you get locked out/i,
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('textbox', { name: 'Email Address' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Add Email' })).toBeEnabled();
  });

  test('should add email and analytics events be tracked on clicking submit', async () => {
    const email = 'test@razorpay.com';
    await typeEmailAndSubmit(email);

    expect(screen.queryByRole('button', { name: 'Add Email' })).not.toBeInTheDocument();
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'add email submit',
      actionName: 'clicked',
      screen: defaultProps.screen,
      properties: {},
    });
    expect(screen.getByRole('button', { name: /Verifying/i })).toBeInTheDocument();

    await waitFor(() => {
      expect(analyticsTrackSpy).toHaveBeenCalledWith({
        objectName: 'add email',
        actionName: 'result',
        screen: defaultProps.screen,
        properties: {
          result: 'Success',
        },
      });
      expect(screen.getByRole('button', { name: 'Add Email' })).toBeInTheDocument();
      expect(defaultProps.onSubmit).toHaveBeenCalledWith({ email });
    });
  });

  test('should show notification and analytics events be tracked on submit error email', async () => {
    // Some error message scenario
    await typeEmailAndSubmit('incorrect-email@razorpay.com');
    await waitFor(() => {
      expect(analyticsTrackSpy).toHaveBeenLastCalledWith({
        objectName: 'add email',
        actionName: 'result',
        screen: defaultProps.screen,
        properties: {
          result: 'Failure',
          failureMessage: 'incorrect-email',
        },
      });

      expect(screen.getByText('incorrect-email')).toBeInTheDocument();
    });
  });

  test("show should show some error occurred notification when there's no error message on email submit", async () => {
    // No error message scenario
    await typeEmailAndSubmit('no-error-message@razorpay.com');
    await waitFor(() => {
      expect(analyticsTrackSpy).toHaveBeenLastCalledWith({
        objectName: 'add email',
        actionName: 'result',
        screen: defaultProps.screen,
        properties: {
          result: 'Failure',
          failureMessage: null,
        },
      });
      expect(screen.getByText('Some error occured. Please refresh')).toBeInTheDocument();
    });
  });

  test('should show invalid email notification if on invalid email', async () => {
    await typeEmailAndSubmit('invalid-email-format');
    expect(screen.getByText('Invalid email')).toBeInTheDocument();
  });

  test('should call onClose on clicking close', async () => {
    render(<App />);
    await userEvent.click(screen.getByRole('button', { name: 'Close Add Email Modal' }));
    expect(defaultProps.onClose).toHaveBeenCalled();
  });

  test('should not call onClose when its undefined on clicking close', async () => {
    render(<App onClose={undefined} />);
    await userEvent.click(screen.getByRole('button', { name: 'Close Add Email Modal' }));
    expect(defaultProps.onClose).not.toHaveBeenCalled();
  });
});
