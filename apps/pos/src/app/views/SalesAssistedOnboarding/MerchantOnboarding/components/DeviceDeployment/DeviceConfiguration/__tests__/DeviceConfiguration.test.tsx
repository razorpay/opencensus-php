import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import DeviceConfigurationContainer from '../DeviceConfigurationContainer';
import { getModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'deviceDeployment',
    component: 'deviceConfiguration',
  }),
}));

jest.mock('apps/pos/src/bootstrap/Store/index', () => ({
  __esModule: true,
  default: jest.fn(() => ({
    workflowProduct: 'PARTNER_ASSISTED_ONBOARDING',
    isPosEkycAgent: true,
    setWorkflowProduct: jest.fn(),
    setIsPosEkycAgent: jest.fn(),
  })),
}));

const queryClient = new QueryClient();
const renderApp = () => {
  render(
    <QueryClientProvider client={queryClient}>
      <DeviceConfigurationContainer />
    </QueryClientProvider>,
  );
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should not render Device Configuration page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should render Device Configuration page if modular config is available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Device Mapping/i)).toBeInTheDocument();
      expect(screen.getByText(/Language settings/i)).toBeInTheDocument();
      expect(screen.getByText(/Device Testing/i)).toBeInTheDocument();
      expect(screen.getByText(/Wifi-Configuration/i)).toBeInTheDocument();
    });
  });

  test('should open bottomsheet when wifi-configuration is clicked', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      const wifiConfigCard = screen.getByText(/Wifi-Configuration/i);
      wifiConfigCard.click();
    });

    await waitFor(() => {
      expect(screen.getByText(/Wifi Setup Instructions/i)).toBeInTheDocument();
      expect(screen.getByText(/Configure Device Wifi/i)).toBeInTheDocument();
      expect(screen.getByText(/Wifi-configuration successful/i)).toBeInTheDocument();
    });
  });
});
