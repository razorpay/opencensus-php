import React from 'react';
import DeviceMappingSuccessContainer from '../DeviceMappingSuccessContainer';
import { getModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'deviceDeployment',
    component: 'deviceMappingSuccess',
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

const renderApp = () => {
  render(<DeviceMappingSuccessContainer />);
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should not render Device Mapping Success page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should not render Device Mapping Success page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();

    await waitFor(() => {
      expect(screen.getByText(/Device Mapped Successfully/i)).toBeInTheDocument();
      expect(screen.getByText(/Model Details/i)).toBeInTheDocument();
      expect(screen.getByText(/Deploy Another Device/i)).toBeInTheDocument();
    });
  });
});
