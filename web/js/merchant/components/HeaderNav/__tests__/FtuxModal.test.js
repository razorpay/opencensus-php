import { screen, render, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

import { FtuxModal } from 'merchant/components/HeaderNav/FtuxModal';

describe('FtuxModal', () => {
  it('should render the component', () => {
    render(<FtuxModal />);
    const button = screen.getByRole('button', {
      name: 'Stay on dashboard',
    });
    expect(button).toBeInTheDocument();
    expect(
      screen.getByText('Collect a payment to complete your account setup'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Go back to the account setup page or choose a payment product from the main menu to to collect a payment',
      ),
    ).toBeInTheDocument();
  });

  it('should redirect to easy onboarding on click of back to setup button', async () => {
    const handleModalVisibilty = jest.fn();
    window.open = jest.fn();
    const closeModal = jest.fn();
    render(<FtuxModal handleModalVisibilty={handleModalVisibilty} closeModal={closeModal} />);
    const accountSetup = screen.getByRole('button', {
      name: 'Back to account setup',
    });
    expect(accountSetup).toBeInTheDocument();
    await fireEvent.click(accountSetup);
    expect(handleModalVisibilty).toHaveBeenCalledTimes(1);

    await waitFor(() => {
      expect(window.open).toHaveBeenCalledWith(
        `${window.EASY_ONBOARDING_URL}/overview`,
        '_self',
        'noopener',
      );
    });
  });

  it('should stay in dashboard and close the modal when stay on dashboard is clicked', () => {
    const handleModalVisibilty = jest.fn();
    const closeModal = jest.fn();
    render(<FtuxModal handleModalVisibilty={handleModalVisibilty} closeModal={closeModal} />);
    const stayOnDashboard = screen.getByRole('button', {
      name: 'Stay on dashboard',
    });
    expect(stayOnDashboard).toBeInTheDocument();
    fireEvent.click(stayOnDashboard);
    expect(handleModalVisibilty).toHaveBeenCalledTimes(1);
    expect(closeModal).toHaveBeenCalledTimes(1);
  });
});
