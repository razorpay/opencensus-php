import React from 'react';
import DeviceMappingScannerContainer from '../DeviceMappingScannerContainer';
import { getModularConfig } from '../mocks/handlers';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'P5IFHHN6RTRXDe',
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
  render(<DeviceMappingScannerContainer />);
};

describe('Test POS Language Configuration screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should not render Device Mapping Scanner page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should render Device Mapping Scanner page if modular config data is not available', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Please Scan the Device Serial Number/i)).toBeInTheDocument();
      expect(screen.getByText(/Enter Details Manually/i)).toBeInTheDocument();
    });
  });

  test('should switch to QR mode when it is clicked', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      const QrButton = screen.getByText('QR-Code');
      QrButton.click();
    });

    await waitFor(() => {
      expect(screen.getByText(/Place the QR Code to Scan/i)).toBeInTheDocument();
    });
  });
});
