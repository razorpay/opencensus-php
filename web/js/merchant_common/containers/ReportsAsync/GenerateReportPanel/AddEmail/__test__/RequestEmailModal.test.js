import RequestEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/RequestEmailModal';
import { render, screen, fireEvent } from 'common/services/test/test-utils';
import * as analytics from 'common/utils/analytics';

describe('Request Email Modal', () => {
  const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

  beforeEach(() => analyticsTrackSpy.mockClear());

  test('should render request email modal', () => {
    render(<RequestEmailModal />);
    expect(screen.getByText(/Receive important updates on email/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Update your email address on your Razorpay account to make sure you don’t miss important alerts/i,
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Later' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Update Now' })).toBeInTheDocument();
  });

  test('should call analytics on closing modal', () => {
    render(<RequestEmailModal />);
    const closeButton = screen.getByRole('button', { name: 'Later' });
    fireEvent.click(closeButton);
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'add email prompt cancel',
      actionName: 'clicked',
      screen: 'home page',
      properties: {},
    });
  });

  test('should call analytics on mounting', () => {
    render(<RequestEmailModal />);
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'add email prompt',
      actionName: 'displayed',
      screen: 'home page',
      properties: {},
    });
  });

  test('should open add email modal on clicking update', () => {
    render(<RequestEmailModal />, { showModal: true });
    const updateButton = screen.getByRole('button', { name: 'Update Now' });
    fireEvent.click(updateButton);
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'add email prompt update',
      actionName: 'clicked',
      screen: 'home page',
      properties: {},
    });

    expect(screen.getByText('Verify your Phone Number')).toBeInTheDocument();
    expect(
      screen.getByText('A SMS with a 6-digit OTP has been sent to your registered phone number'),
    ).toBeInTheDocument();
    expect(screen.getByText('Enter the code')).toBeInTheDocument();
  });
});
