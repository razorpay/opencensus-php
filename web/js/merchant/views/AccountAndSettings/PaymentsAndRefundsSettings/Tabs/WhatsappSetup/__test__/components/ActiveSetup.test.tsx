import '@testing-library/jest-dom/extend-expect';
import ActiveSetup from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/ActiveSetup';
import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import {
  getMerchantFeaturesHandler,
  getFeatureHandler,
} from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/__test__/mocks/handlers';
import { BusinessServiceProvider } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/DeleteAccount',
  () => ({
    __esModule: true,
    default: () => <h1>Delete Account</h1>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/StatusNotification',
  () => ({
    __esModule: true,
    default: () => <h1>Success Status Notification</h1>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/TurnoffNotify',
  () => ({
    __esModule: true,
    default: () => <h1>Toggle Whatsapp PL</h1>,
  }),
);

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

const businessProvider = BusinessServiceProvider[0];

const intitialFeatureState = {
  notify_via_whatsapp_plink: true,
};

describe('WhatsappSetup - ActiveSetup', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = (
    props = {},
    user = {},
    initialEntries = ['/'],
    state = intitialFeatureState,
  ) => {
    server.use(getMerchantFeaturesHandler());
    server.use(getFeatureHandler());
    render(<ActiveSetup {...props} />, {
      initialState: {
        session: {
          user: {
            isFeatureEnabled: () => true,
            ...user,
          },
        },
        genericFeature: {
          features: state,
        },
      },
      initialEntries,
    });
  };

  test('should render active whatsapp setup', () => {
    renderApp({
      businessProvider,
    });
    expect(
      screen.getByRole('heading', { name: 'Your Whatsapp Business Account' }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('heading', { name: 'Send all payment links on Whatsapp' }),
    ).toBeInTheDocument();
  });

  test('should render active whatsapp setup on mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp({
      businessProvider,
    });
    expect(
      screen.getByRole('heading', { name: 'Your Whatsapp Business Account' }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('heading', { name: 'Send all payment links on Whatsapp' }),
    ).toBeInTheDocument();
  });

  test('should render business provider and its details', () => {
    renderApp({
      businessProvider,
    });

    expect(screen.getByText('Business Service Provider:')).toBeInTheDocument();
    expect(screen.getByText(businessProvider.title)).toBeInTheDocument();
  });

  test('should render business provider and its details on mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp({
      businessProvider,
    });

    expect(screen.getByText('Business Service Provider:')).toBeInTheDocument();
    expect(screen.getByText(businessProvider.title)).toBeInTheDocument();
  });

  test('should show delete button to disconnect WABA', async () => {
    renderApp({
      businessProvider,
    });
    const deleteBtn = screen.getByRole('button', {
      name: 'Delete',
    });
    expect(deleteBtn).toBeInTheDocument();
    await userEvent.click(deleteBtn);
    await waitFor(() => {
      expect(
        screen.getByRole('heading', {
          name: 'Delete Account',
        }),
      ).toBeInTheDocument();
    });
  });

  test('should show switch button and on click open modal to toggle PL Links on whatsapp', async () => {
    renderApp({
      businessProvider,
    });
    const toggleBtn = screen.getByRole('switch', {
      name: 'Toggle notifications',
    });
    expect(toggleBtn).toBeInTheDocument();
    await userEvent.click(toggleBtn);
    await waitFor(() => {
      expect(
        screen.getByRole('heading', {
          name: 'Toggle Whatsapp PL',
        }),
      ).toBeInTheDocument();
    });
  });

  test('should show toggle button but not open toggle modal if feature is not enabled', async () => {
    renderApp(
      {
        businessProvider,
      },
      {
        isFeatureEnabled: () => false,
      },
      ['/'],
      {
        notify_via_whatsapp_plink: false,
      },
    );
    const toggleBtn = screen.getByRole('switch', {
      name: 'Toggle notifications',
    });
    expect(toggleBtn).toBeInTheDocument();
    await userEvent.click(toggleBtn);
    await waitFor(() => {
      expect(
        screen.queryByRole('heading', {
          name: 'Toggle Whatsapp PL',
        }),
      ).not.toBeInTheDocument();
    });
  });

  test('should show switch button and on click open modal to toggle PL Links on whatsapp on mobile', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp({
      businessProvider,
    });
    const toggleBtn = screen.getByRole('switch', {
      name: 'Toggle notifications',
    });
    expect(toggleBtn).toBeInTheDocument();
    await userEvent.click(toggleBtn);
    await waitFor(() => {
      expect(
        screen.getByRole('heading', {
          name: 'Toggle Whatsapp PL',
        }),
      ).toBeInTheDocument();
    });
  });

  test('should show success modal when url search params has isWhatsappSetupCompleted', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp(
      {
        businessProvider,
      },
      {
        contact_mobile: '9999999999',
      },
      ['?isWhatsappSetupCompleted=true'],
    );
    expect(
      screen.getByRole('heading', {
        name: 'Success Status Notification',
      }),
    ).toBeInTheDocument();
  });
});
