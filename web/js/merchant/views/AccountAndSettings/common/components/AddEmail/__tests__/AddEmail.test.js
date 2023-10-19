import { userEvent, screen } from 'test-utils';
import { renderApp } from './mocks/AddEmail';
import { analyticsTrack } from 'common/utils/analytics';

describe('AddEmail', () => {
  test('should render the AddEmail component with phone OTP method', () => {
    renderApp();
    expect(screen.getByText('Phone OTPModal')).toBeInTheDocument();
  });

  test('should render follow-up modal when Phone OTP is submitted', async () => {
    renderApp();
    expect(screen.getByText('Phone OTPModal')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Submit' }));
    expect(screen.getByText('AccountDetailsUpdate')).toBeInTheDocument();
    await userEvent.click(screen.getAllByRole('button', { name: 'Submit' })[0]);
    expect(screen.getByText('Email OTPModal')).toBeInTheDocument();
    await userEvent.click(screen.getAllByRole('button', { name: 'Submit' })[1]);
    expect(screen.getByText('SuccessModal')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'add email pop up cancel',
      actionName: 'clicked',
      screen: 'add email',
      properties: expect.any(Object),
    });
  });
});
