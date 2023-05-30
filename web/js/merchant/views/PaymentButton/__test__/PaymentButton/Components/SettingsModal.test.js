import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentButton/__test__/mocks/fixtures/SettingsModal';

const trackMock = {
  closeModal: jest.fn(),
  customMessage: jest.fn(),
  saveFail: jest.fn(),
  save: jest.fn(),
  hasOwnProperty: () => true,
  redirectURLCheckbox: jest.fn(),
  customMessageCheckbox: jest.fn(),
};

describe('Payment Button Component SettingsModal Component', () => {
  const renderApp = (props) => render(<App trackMock={trackMock} {...props} />);

  test('Payment Button App component should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render payment button heading should render', () => {
    renderApp();
    expect(screen.getByText('Button Settings')).toBeInTheDocument();
    expect(screen.getByText('Redirect URL')).toBeInTheDocument();
  });

  test('Payment Button SettingsModal component should have go back to dashboard', async () => {
    renderApp();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelBtn);
    expect(cancelBtn).toBeInTheDocument();
    const saveBtn = screen.getByRole('button', { name: 'Save' });
    expect(saveBtn).toBeInTheDocument();
    await userEvent.click(saveBtn);
    expect(trackMock.closeModal).toHaveBeenCalled();
  });

  test('Payment Button SettingsModal component should have go back to dashboard', async () => {
    const editPaymentButton = jest.fn(() => Promise.reject({ data: false }));
    const props = {
      editPaymentButton,
    };
    renderApp(props);
    const redirectURL = screen.getByText('Redirect URL');
    expect(redirectURL).toBeInTheDocument();
    await userEvent.click(redirectURL);
    expect(trackMock.redirectURLCheckbox).toHaveBeenCalled();
  });
});
