import '@testing-library/jest-dom/extend-expect';
import WhatsappSetup from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup';
import { oauthApplications } from 'merchant/views/Settings/Applications/__tests__/mocks/fixtures';
import { getApplications } from 'merchant/views/Settings/Applications/__tests__/mocks/handlers';
import React from 'react';
import { render, screen, server, waitFor } from 'test-utils';
import { BusinessServiceProvider } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { useMobile } from 'common/hooks/useMobile';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/ActiveSetup',
  () => {
    return {
      __esModule: true,
      default: () => <h1>Whatsapp setup already active</h1>,
    };
  },
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/InitiateSetup',
  () => {
    return {
      __esModule: true,
      default: () => <h1>Whatsapp setup yet to be initiated</h1>,
    };
  },
);

describe('WhatsappSetup Landing Page', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = (props = {}, initialState = {}) => {
    render(<WhatsappSetup {...props} />, {
      initialState,
    });
  };

  test('should render whatsapp setup landing page', () => {
    renderApp({});
    expect(screen.getByRole('heading', { name: 'Whatsapp Account Set-up' })).toBeInTheDocument();
    expect(
      screen.getByText(
        'To send payment requests via WhatsApp, please set-up your WABA account with Razorpay',
      ),
    ).toBeInTheDocument();
  });

  test('should render whatsapp setup landing page in mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp({});
    expect(screen.getByRole('heading', { name: 'Whatsapp Account Set-up' })).toBeInTheDocument();
    expect(
      screen.getByText(
        'To send payment requests via WhatsApp, please set-up your WABA account with Razorpay',
      ),
    ).toBeInTheDocument();
  });

  test('should show loading shimmer when API is loading', async () => {
    renderApp({});
    expect(screen.getByTestId('loading-shimmer')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.queryByTestId('loading-shimmer')).not.toBeInTheDocument();
    });
  });

  test('should render active setup when application is already connected', async () => {
    const appicationsMockData = [
      {
        ...oauthApplications,
        application_name: BusinessServiceProvider[0].applicationName,
      },
    ];

    renderApp(
      {},
      {
        applications: {
          tokens: appicationsMockData,
        },
      },
    );
    await waitFor(() => {
      expect(
        screen.getByRole('heading', { name: 'Whatsapp setup already active' }),
      ).toBeInTheDocument();
    });
  });

  test('should render initate setup when application is not yet connected', async () => {
    server.use(getApplications([]));
    renderApp({});
    await waitFor(() => {
      expect(
        screen.getByRole('heading', { name: 'Whatsapp setup yet to be initiated' }),
      ).toBeInTheDocument();
    });
  });
});
