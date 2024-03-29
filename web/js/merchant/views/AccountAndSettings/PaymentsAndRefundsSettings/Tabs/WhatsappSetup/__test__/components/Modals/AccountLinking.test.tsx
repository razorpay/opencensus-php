import '@testing-library/jest-dom/extend-expect';
import AccountLinking from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/AccountLinking';
import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import 'jest-location-mock';
import {
  BusinessServiceProvider,
  SERVICE_PROVIDER_LOGIN_HREF,
} from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';

const onCloseMock = jest.fn();
const setModalStateMock = jest.fn();

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

describe('WhatsappSetup - AccountLinking', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = () => {
    const defaultProps = {
      modalState: { isOpen: true },
      onClose: onCloseMock,
      setModalState: setModalStateMock,
    };
    render(<AccountLinking {...defaultProps} />);
  };

  test('should render Account Linking Modal view', () => {
    renderApp();
    expect(screen.getByText('Link your existing Whatsapp Account')).toBeInTheDocument();
    expect(
      screen.getByRole('combobox', { name: 'Select your Business Service Provider' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Continue' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
  });

  test('should render Account Linking Modal view in mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    expect(screen.getByText('Link your existing Whatsapp Account')).toBeInTheDocument();
    expect(
      screen.getByRole('combobox', { name: 'Select your Business Service Provider' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Continue' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
  });

  test('should call onClose fn passed as prop on cancel', async () => {
    renderApp();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledWith({ action: 'close' });
    });
  });

  test('should call onClose fn passed as prop on cancel in mobile', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledWith({ action: 'close' });
    });
  });

  test('should cddd djbd all needs clarification modal in international transactions in mobile', async () => {
    window.open = jest.fn();
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    const continueBtn = screen.getByRole('button', { name: 'Continue' });
    const select = screen.getByRole('combobox', { name: 'Select your Business Service Provider' });
    expect(select).toBeInTheDocument();
    await userEvent.click(select);

    const optionToSelect = BusinessServiceProvider[0];

    await userEvent.click(screen.getByRole('option', { name: optionToSelect.title }));
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(setModalStateMock).toHaveBeenCalled();
    });
    await waitFor(() => {
      expect(window.open).toHaveBeenCalledWith(
        SERVICE_PROVIDER_LOGIN_HREF[optionToSelect.value],
        '_blank',
      );
    });
  });

  test('should cddd djbf efrfer d all needs clarification modal in international transactions in mobile', async () => {
    window.open = jest.fn();
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    const continueBtn = screen.getByRole('button', { name: 'Continue' });
    const select = screen.getByRole('combobox', { name: 'Select your Business Service Provider' });
    expect(select).toBeInTheDocument();
    await userEvent.click(select);

    const optionToSelect = BusinessServiceProvider[0];

    await userEvent.click(screen.getByRole('option', { name: optionToSelect.title }));
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(setModalStateMock).toHaveBeenCalled();
    });
    await waitFor(() => {
      expect(window.open).toHaveBeenCalledWith(
        SERVICE_PROVIDER_LOGIN_HREF[optionToSelect.value],
        '_blank',
      );
    });
  });
});
