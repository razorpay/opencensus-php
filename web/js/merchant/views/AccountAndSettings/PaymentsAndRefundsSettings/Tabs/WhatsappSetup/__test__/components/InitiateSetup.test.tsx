import '@testing-library/jest-dom/extend-expect';
import IntitateSetup from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/InitiateSetup';
import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import 'jest-location-mock';
import { getMerchantFeaturesHandler } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/__test__/mocks/handlers';

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/AccountLinking',
  () => ({
    __esModule: true,
    default: () => <h1>Link Account</h1>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/StatusNotification',
  () => ({
    __esModule: true,
    default: () => <h1>Success Status Notification</h1>,
  }),
);

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

describe('WhatsappSetup - InitiateSetup', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = (props = {}) => {
    server.use(getMerchantFeaturesHandler());
    render(<IntitateSetup {...props} />);
  };

  test('should show connecting existing WABA and create new account CTA', () => {
    renderApp();
    expect(
      screen.getByRole('heading', { name: 'Continue by linking your existing WABA account' }),
    ).toBeInTheDocument();
    expect(screen.getByText('Don’t have an account?')).toBeInTheDocument();
    expect(
      screen.getByRole('link', { name: 'Create Whatsapp Business Account' }),
    ).toBeInTheDocument();
  });

  test('should show connecting existing WABA and create new account CTA in mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    expect(
      screen.getByRole('heading', { name: 'Continue by linking your existing WABA account' }),
    ).toBeInTheDocument();
    expect(screen.getByText('Don’t have an account?')).toBeInTheDocument();
    expect(
      screen.getByRole('link', { name: 'Create Whatsapp Business Account' }),
    ).toBeInTheDocument();
  });

  test('should show continue CTA and open connecting flow', async () => {
    renderApp();
    const linkBtn = screen.getByRole('button', {
      name: 'Proceed',
    });
    expect(linkBtn).toBeInTheDocument();
    await userEvent.click(linkBtn);
    await waitFor(() => {
      expect(
        screen.getByRole('heading', {
          name: 'Link Account',
        }),
      ).toBeInTheDocument();
    });
  });

  test('should show continue CTA and redirect to create flow', async () => {
    renderApp();
    const createNewBtn = screen.getByRole('link', { name: 'Create Whatsapp Business Account' });
    expect(createNewBtn).toBeInTheDocument();
    await userEvent.click(createNewBtn);
  });
});
