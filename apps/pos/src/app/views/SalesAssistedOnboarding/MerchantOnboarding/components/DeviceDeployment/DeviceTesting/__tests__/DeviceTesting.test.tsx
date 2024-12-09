import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import DeviceTestingContainer from '../DeviceTestingContainer';
import { getModularConfig, updateModularConfig } from '../mocks/handlers';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'deviceDeployment',
    component: 'deviceTesting',
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
      <DeviceTestingContainer />
    </QueryClientProvider>,
  );
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should not render Device Testing page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should render Device Testing page if modular config data is available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Testing with Amount/i)).toBeInTheDocument();
      expect(screen.getByText(/Enter Amount/i)).toBeInTheDocument();
      expect(screen.getByText(/Send Amount/i)).toBeInTheDocument();
    });
  });

  test('CTA text should update after amount has been sent', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      const CTA = screen.getByText(/Send Amount/i);
      expect(CTA).not.toBeDisabled();
      userEvent.click(CTA);
    });

    await waitFor(
      () => {
        expect(screen.getByText(/Amount Received/i)).toBeInTheDocument();
      },
      { timeout: 10000 },
    );
  });
});
