import '@testing-library/jest-dom/extend-expect';
import DeleteAccount from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/DeleteAccount';
import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import { BusinessServiceProvider } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { revokeOauthApplicationAccess } from 'merchant/reducers/applications';

jest.mock('merchant/reducers/applications', () => ({
  ...(jest.requireActual('merchant/reducers/applications') as any),
  revokeOauthApplicationAccess: jest.fn(),
}));

const onCloseMock = jest.fn();
const updateNotificationFeatureMock = jest.fn();
const showNotificationMock = jest.fn();
const fetchOauthConnectedApplicationsMock = jest.fn();

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

describe('WhatsappSetup - DeleteAccount', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

  beforeEach(() => {
    showNotificationSpy.mockClear();
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = () => {
    const defaultProps = {
      modalState: {
        isOpen: true,
      },
      onClose: onCloseMock,
      businessProvider: {
        ...BusinessServiceProvider[0],
        application_id: BusinessServiceProvider[0].applicationName,
      },
      fetchOauthConnectedApplications: fetchOauthConnectedApplicationsMock,
      showNotification: showNotificationMock,
      updateNotificationFeature: updateNotificationFeatureMock,
    };
    render(<DeleteAccount {...defaultProps} />);
  };

  test('should render delete account modal', () => {
    renderApp();
    expect(
      screen.getByText('Are you sure you want to delete your Whatsapp Business Profile?'),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Cancel',
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Yes, Delete',
      }),
    ).toBeInTheDocument();
  });

  test('should show cancel button and call passed onClose fn on dismiss', async () => {
    renderApp();
    const cancelBtn = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledWith({ action: 'close' });
    });
  });

  test('should show proceed button and revoke application on click and if success should show success notifcation', async () => {
    (revokeOauthApplicationAccess as jest.Mock).mockReturnValue({
      type: 'REVOKE_ACCESS_TOKEN',
      payload: Promise.resolve({
        success: true,
        status_code: 200,
      }),
    });
    renderApp();
    const continueBtn = screen.getByRole('button', {
      name: 'Yes, Delete',
    });
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Business Provider deleted successfully',
      });
    });
  });

  test('should show proceed button and revoke application on click if failed should show error notifcation', async () => {
    (revokeOauthApplicationAccess as jest.Mock).mockReturnValue({
      type: 'REVOKE_ACCESS_TOKEN',
      payload: Promise.resolve({
        success: false,
        status_code: 500,
      }),
    });
    renderApp();
    const continueBtn = screen.getByRole('button', {
      name: 'Yes, Delete',
    });
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });

  test('should render delete account modal in mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    expect(
      screen.getByText('Are you sure you want to delete your Whatsapp Business Profile?'),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Cancel',
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Yes, Delete',
      }),
    ).toBeInTheDocument();
  });

  test('should show cancel button and call passed onClose fn on dismiss in mobile', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    const cancelBtn = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledWith({ action: 'close' });
    });
  });
});
