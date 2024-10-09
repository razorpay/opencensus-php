import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import DeviceMappingManualComponent from '../DeviceMappingManualComponent';
import { getModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'P5IFHHN6RTRXDe',
    step: 'deviceDeployment',
    component: 'deviceDetails',
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
      <DeviceMappingManualComponent />
    </QueryClientProvider>,
  );
};

describe('Test POS Device Mapping Manual screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should throw error toast if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Failed to fetch modular config/i)).toBeInTheDocument();
    });
  });

  test('should render Device Mapping Manual page if modular config data is available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Enter Device Details Manually/i)).toBeInTheDocument();
      expect(screen.getByText(/Device Serial Number/i)).toBeInTheDocument();
      expect(screen.getByText(/Model Details/i)).toBeInTheDocument();
      expect(screen.getByText(/Confirm Serial Number/i)).toBeInTheDocument();
    });
  });
});
