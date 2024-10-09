import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import DeviceDeploymentList from '../DeviceDeploymentListContainer';
import { getModularConfig, updateModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'P5IFHHN6RTRXDe',
    step: 'deviceDeployment',
    component: 'deviceDeploymentList',
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
      <DeviceDeploymentList />
    </QueryClientProvider>,
  );
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should not render Device Deployment list page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Failed to fetch modular config/i)).toBeInTheDocument();
    });
  });

  test('should render Device Deployment list page if modular config data is available', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Choose Devices to Deploy/i)).toBeInTheDocument();
      expect(screen.getByText(/All Devices/i)).toBeInTheDocument();
      expect(screen.getByText(/Deployed Devices/i)).toBeInTheDocument();
    });
  });
});
