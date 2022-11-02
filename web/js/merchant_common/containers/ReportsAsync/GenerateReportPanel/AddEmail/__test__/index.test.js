import { render, screen } from 'common/services/test/test-utils';
import AddEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail';
import * as analytics from 'common/utils/analytics';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

jest.mock('merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/OTPModal', () => ({
  ...jest.requireActual(
    'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/OTPModal',
  ),
  __esModule: true,
  default: ({ otpMethod, onSubmit, phone, email, onClose }) => {
    return (
      <div>
        <button type="button" onClick={() => onSubmit('token')}>
          Submit
        </button>
        {otpMethod === 1 ? (
          <p>
            Phone Number<strong>{phone}</strong>
          </p>
        ) : (
          <p>
            Email<strong>{email}</strong>
          </p>
        )}
        <input type="number" name="otp" aria-label="OTP" />

        <button type="button" onClick={onClose}>
          Close
        </button>
      </div>
    );
  },
}));

const EMAIL = 'test@razorpay.com';

jest.mock(
  'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/NewEmailModal',
  () => ({
    ...jest.requireActual(
      'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/NewEmailModal',
    ),
    __esModule: true,
    default: ({ onSubmit, onClose }) => {
      return (
        <div>
          Add your email address
          <button type="button" onClick={() => onSubmit({ email: EMAIL })}>
            Submit
          </button>
          <input type="email" name="email" aria-label="Email Address" />
          <button type="button" onClick={onClose}>
            Close
          </button>
        </div>
      );
    },
  }),
);

jest.mock(
  'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/SuccessModal',
  () => ({
    ...jest.requireActual(
      'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/SuccessModal',
    ),
    __esModule: true,
    default: ({ heading, note, onClose, successButtonText }) => {
      return (
        <div>
          <div className="merchant-heading">{heading}</div>
          <div className="merchant-note email-merchant-note">{note}</div>
          <button type="button">{successButtonText}</button>
          <button type="button" onClick={onClose}>
            Close
          </button>
        </div>
      );
    },
  }),
);

describe('Add Email Modal', () => {
  const defaultProps = {
    screen: 'test',
    successButtonText: 'successButtonText',
    successButtonLink: 'successButtonLink',
  };

  const storeState = {
    session: {
      user: {
        user: {
          contact_mobile: '9999999999',
        },
      },
    },
  };

  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  beforeEach(() => {
    analyticsTrackMock.mockClear();
  });

  const App = (props) => {
    return (
      <Provider store={storeWithInitialState(storeState)}>
        <AddEmailModal {...defaultProps} {...props} />
      </Provider>
    );
  };

  const clickOnCloseAndExpectAnalytics = async () => {
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    expect(analyticsTrackMock).toHaveBeenLastCalledWith({
      objectName: 'add email pop up cancel',
      actionName: 'clicked',
      screen: 'add email',
      properties: {},
    });
  };

  const clickSubmit = async () => {
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }));
  };

  test('should show otp modal content', async () => {
    render(<App />);
    // Step Verify Mobile OTP - OTPModal
    expect(screen.getByText(storeState.session.user.user.contact_mobile)).toBeInTheDocument();
    await clickOnCloseAndExpectAnalytics();
    await userEvent.type(screen.getByRole('spinbutton', { name: 'OTP' }), '123456');
    await clickSubmit();
    // Step ENTER_EMAIL - NewEmailModal
    await clickOnCloseAndExpectAnalytics();
    await userEvent.type(screen.getByRole('textbox', { name: 'Email Address' }), EMAIL);
    await clickSubmit();
    // Step VERIFY_EMAIL_OTP - OTPModal
    expect(screen.getByText(EMAIL)).toBeInTheDocument();
    await userEvent.type(screen.getByRole('spinbutton', { name: 'OTP' }), '123456');
    await clickSubmit();
    // Step SUCCESS - SuccessModal
    expect(screen.getByText(/Email added successfully/i)).toBeInTheDocument();
    expect(screen.getByText(`Your email ${EMAIL} has been added successfully`)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: defaultProps.successButtonText }));
    await clickOnCloseAndExpectAnalytics();
  });
});
